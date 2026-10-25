<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\JadwalPelajaranSeeder;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalPelajaranTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $guruRangkap;
    protected User $guruLain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->seed(JadwalPelajaranSeeder::class);

        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->first();
        $this->guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->first();
        $this->guruRangkap = User::where('email', 'guru.rangkap@almuttaqin.id')->first();
        $this->guruLain = User::where('email', 'guru.mat.smp@almuttaqin.id')->first();
    }

    public function test_halaman_master_jadwal_dapat_diakses_oleh_admin_dan_guru(): void
    {
        // Admin
        $respAdmin = $this->actingAs($this->admin)->get(route('jadwal-pelajaran.index', ['jenjang' => 'SMP']));
        $respAdmin->assertStatus(200);
        $respAdmin->assertSee('Matriks Jadwal Pelajaran SMP Al-Muttaqin');

        // Guru
        $respGuru = $this->actingAs($this->guru)->get(route('jadwal-pelajaran.index', ['jenjang' => 'SMA']));
        $respGuru->assertStatus(200);
        $respGuru->assertSee('Matriks Jadwal Pelajaran SMA Al-Muttaqin');
    }

    public function test_halaman_jadwal_saya_dapat_diakses_oleh_guru(): void
    {
        $response = $this->actingAs($this->guru)->get(route('jadwal-pelajaran.saya'));
        $response->assertStatus(200);
        $response->assertSee('Jadwal Mengajar Saya');
        $response->assertSee('Input Jadwal Mengajar');
        $response->assertSee('Kalender Mengajar Mingguan Saya');
    }

    public function test_guru_dapat_menginput_jadwal_mengajar_mandiri(): void
    {
        $classroom = Classroom::forJenjang('SMP')->where('nama_kelas', '7A')->first();
        $subject = Subject::forJenjang('SMP')->where('mata_pelajaran', 'Matematika')->first();

        // Cari slot jam ke-1 hari Selasa
        $slot = TimeSlot::kbm()->where('hari', 'Selasa')->where('jam_ke', 1)->first();
        $this->assertNotNull($slot);

        // Pastikan kosong terlebih dahulu
        Schedule::where('classroom_id', $classroom->id)
            ->where('time_slot_id', $slot->id)
            ->delete();

        $payload = [
            'classroom_id' => $classroom->id,
            'subject_id'   => $subject->id,
            'hari'         => 'Selasa',
            'jam_mulai'    => 1,
            'jam_selesai'  => 1,
            'catatan'      => 'Kelas Reguler',
        ];

        $response = $this->actingAs($this->guru)->post(route('jadwal-pelajaran.saya.store'), $payload);

        $response->assertRedirect(route('jadwal-pelajaran.saya'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $classroom->id,
            'subject_id'   => $subject->id,
            'teacher_id'   => $this->guru->id,
            'time_slot_id' => $slot->id,
            'catatan'      => 'Kelas Reguler',
        ]);
    }

    public function test_guru_dapat_menginput_rentang_jam_sekaligus(): void
    {
        $classroom = Classroom::forJenjang('SMA')->where('nama_kelas', '10A')->first();
        $subject = Subject::forJenjang('SMA')->where('mata_pelajaran', 'like', '%PAI%')->first();

        // Bersihkan jadwal Rabu jam 2 dan 3 di kelas 10A
        $slot2 = TimeSlot::kbm()->where('hari', 'Rabu')->where('jam_ke', 2)->first();
        $slot3 = TimeSlot::kbm()->where('hari', 'Rabu')->where('jam_ke', 3)->first();

        Schedule::where('classroom_id', $classroom->id)
            ->whereIn('time_slot_id', [$slot2->id, $slot3->id])
            ->delete();

        $payload = [
            'classroom_id' => $classroom->id,
            'subject_id'   => $subject->id,
            'hari'         => 'Rabu',
            'jam_mulai'    => 2,
            'jam_selesai'  => 3,
            'catatan'      => 'Blok 2 Jam',
        ];

        $response = $this->actingAs($this->guruRangkap)->post(route('jadwal-pelajaran.saya.store'), $payload);

        $response->assertRedirect(route('jadwal-pelajaran.saya'));
        $response->assertSessionHas('success');

        // Harus ada 2 record
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $classroom->id,
            'teacher_id'   => $this->guruRangkap->id,
            'time_slot_id' => $slot2->id,
        ]);
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $classroom->id,
            'teacher_id'   => $this->guruRangkap->id,
            'time_slot_id' => $slot3->id,
        ]);
    }

    public function test_validasi_menolak_bentrok_ruang_kelas(): void
    {
        $classroom = Classroom::forJenjang('SMP')->where('nama_kelas', '8A')->first();
        $subject = Subject::forJenjang('SMP')->where('mata_pelajaran', 'Matematika')->first();

        $slot = TimeSlot::kbm()->where('hari', 'Kamis')->where('jam_ke', 2)->first();

        // Buat jadwal yang sudah ada terlebih dahulu
        Schedule::create([
            'classroom_id' => $classroom->id,
            'time_slot_id' => $slot->id,
            'subject_id'   => $subject->id,
            'teacher_id'   => $this->guru->id,
            'tahun_ajaran' => '2026/2027',
            'semester'     => 'Gasal',
            'is_locked'    => false,
        ]);

        // Guru lain mencoba booking di kelas & slot yang sama
        $payload = [
            'classroom_id' => $classroom->id,
            'subject_id'   => $subject->id,
            'hari'         => 'Kamis',
            'jam_mulai'    => 2,
            'jam_selesai'  => 2,
        ];

        $response = $this->actingAs($this->guruLain)->post(route('jadwal-pelajaran.saya.store'), $payload);

        $response->assertSessionHasErrors('jadwal');
        $this->assertTrue(session('errors')->first('jadwal') !== null);
    }

    public function test_validasi_ketat_menolak_bentrok_guru_lintas_jenjang(): void
    {
        // Guru rangkap mengajar di SMP 7A pada hari Jumat jam 1
        $smpClass = Classroom::forJenjang('SMP')->where('nama_kelas', '7A')->first();
        $smaClass = Classroom::forJenjang('SMA')->where('nama_kelas', '10A')->first();
        $smpSubj = Subject::forJenjang('SMP')->where('mata_pelajaran', 'like', '%PAI%')->first();
        $smaSubj = Subject::forJenjang('SMA')->where('mata_pelajaran', 'like', '%PAI%')->first();

        $slotJumat1 = TimeSlot::kbm()->where('hari', 'Jumat')->where('jam_ke', 1)->first();

        Schedule::create([
            'classroom_id' => $smpClass->id,
            'time_slot_id' => $slotJumat1->id,
            'subject_id'   => $smpSubj->id,
            'teacher_id'   => $this->guruRangkap->id,
            'tahun_ajaran' => '2026/2027',
            'semester'     => 'Gasal',
            'is_locked'    => false,
        ]);

        // Guru rangkap yang sama mencoba input jadwal di SMA pada hari Jumat jam 1 juga!
        $payload = [
            'classroom_id' => $smaClass->id,
            'subject_id'   => $smaSubj->id,
            'hari'         => 'Jumat',
            'jam_mulai'    => 1,
            'jam_selesai'  => 1,
        ];

        $response = $this->actingAs($this->guruRangkap)->post(route('jadwal-pelajaran.saya.store'), $payload);

        // Harus ditolak karena bentrok guru lintas jenjang!
        $response->assertSessionHasErrors('jadwal');
        $errorMsg = session('errors')->first('jadwal');
        $this->assertStringContainsString('bentrok', strtolower($errorMsg));
    }

    public function test_validasi_menolak_guru_menginput_mapel_yang_bukan_diampu_di_guru_sekolah(): void
    {
        // $this->guru mengampu 'Matematika'
        $classroom = Classroom::forJenjang('SMP')->first();
        $bukanMapelGuru = Subject::forJenjang('SMP')->where('mata_pelajaran', 'Bahasa Arab')->first();

        $payload = [
            'classroom_id' => $classroom->id,
            'subject_id'   => $bukanMapelGuru->id,
            'hari'         => 'Senin',
            'jam_mulai'    => 3,
            'jam_selesai'  => 3,
        ];

        $response = $this->actingAs($this->guru)->post(route('jadwal-pelajaran.saya.store'), $payload);

        $response->assertSessionHasErrors(['subject_id', 'jadwal']);
        $errorMsg = session('errors')->first('subject_id');
        $this->assertStringContainsString('hanya berhak', strtolower($errorMsg));
    }

    public function test_validasi_menolak_mapel_yang_tidak_sesuai_dengan_jenjang_kelas(): void
    {
        // Ambil kelas SMP tapi pilih subject SMA
        $smpClassroom = Classroom::forJenjang('SMP')->first();
        $smaSubject = Subject::forJenjang('SMA')->where('mata_pelajaran', 'Fisika')->first();

        $payload = [
            'classroom_id' => $smpClassroom->id,
            'subject_id'   => $smaSubject->id,
            'hari'         => 'Senin',
            'jam_mulai'    => 4,
            'jam_selesai'  => 4,
        ];

        // Lakukan sebagai admin agar lolos pemeriksaan pengampu guru
        $response = $this->actingAs($this->admin)->post(route('jadwal-pelajaran.saya.store'), $payload);

        $response->assertSessionHasErrors(['subject_id', 'jadwal']);
        $errorMsg = session('errors')->first('subject_id');
        $this->assertStringContainsString('tidak dapat dijadwalkan pada kelas', strtolower($errorMsg));
    }

    public function test_validasi_menolak_input_pada_slot_khusus_non_kbm(): void
    {
        $classroom = Classroom::forJenjang('SMP')->first();
        $subject = Subject::forJenjang('SMP')->first();

        // Hari Senin jam ke-0 adalah Upacara (is_break = true)
        // Jika user mencoba submit jam_mulai = 0 (meskipun dibatasi di dropdown)
        $payload = [
            'classroom_id' => $classroom->id,
            'subject_id'   => $subject->id,
            'hari'         => 'Senin',
            'jam_mulai'    => 0, // di luar min 1
            'jam_selesai'  => 0,
        ];

        $response = $this->actingAs($this->guru)->post(route('jadwal-pelajaran.saya.store'), $payload);
        $response->assertSessionHasErrors('jam_mulai');
    }

    public function test_guru_dapat_menghapus_jadwal_sendiri_selama_belum_dikunci(): void
    {
        $schedule = Schedule::where('teacher_id', $this->guru->id)->where('is_locked', false)->first();
        if (!$schedule) {
            $classroom = Classroom::first();
            $slot = TimeSlot::kbm()->whereDoesntHave('schedules', fn($q) => $q->where('classroom_id', $classroom->id))->first();
            $subject = Subject::first();
            $schedule = Schedule::create([
                'classroom_id' => $classroom->id,
                'time_slot_id' => $slot->id,
                'subject_id'   => $subject->id,
                'teacher_id'   => $this->guru->id,
                'tahun_ajaran' => '2026/2027',
                'semester'     => 'Gasal',
                'is_locked'    => false,
            ]);
        }

        $response = $this->actingAs($this->guru)->delete(route('jadwal-pelajaran.destroy', $schedule->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_guru_tidak_dapat_menghapus_jadwal_guru_lain(): void
    {
        $schedule = Schedule::where('teacher_id', $this->guru->id)->where('is_locked', false)->first();
        if (!$schedule) {
            $classroom = Classroom::first();
            $slot = TimeSlot::kbm()->whereDoesntHave('schedules', fn($q) => $q->where('classroom_id', $classroom->id))->first();
            $subject = Subject::first();
            $schedule = Schedule::create([
                'classroom_id' => $classroom->id,
                'time_slot_id' => $slot->id,
                'subject_id'   => $subject->id,
                'teacher_id'   => $this->guru->id,
                'tahun_ajaran' => '2026/2027',
                'semester'     => 'Gasal',
                'is_locked'    => false,
            ]);
        }

        $response = $this->actingAs($this->guruLain)->delete(route('jadwal-pelajaran.destroy', $schedule->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_admin_dapat_mengunci_dan_membuka_jadwal(): void
    {
        $responseLock = $this->actingAs($this->admin)->post(route('jadwal-pelajaran.toggle-lock'), [
            'jenjang'   => 'SMP',
            'is_locked' => '1',
        ]);

        $responseLock->assertRedirect();
        $responseLock->assertSessionHas('success');

        // Pastikan jadwal SMP terkunci
        $smpSchedules = Schedule::forJenjang('SMP')->get();
        foreach ($smpSchedules as $sched) {
            $this->assertTrue((bool)$sched->is_locked);
        }

        // Buka kunci kembali
        $responseUnlock = $this->actingAs($this->admin)->post(route('jadwal-pelajaran.toggle-lock'), [
            'jenjang'   => 'SMP',
            'is_locked' => '0',
        ]);
        $responseUnlock->assertRedirect();
        $responseUnlock->assertSessionHas('success');

        $smpSchedulesUnlocked = Schedule::forJenjang('SMP')->get();
        foreach ($smpSchedulesUnlocked as $sched) {
            $this->assertFalse((bool)$sched->is_locked);
        }
    }

    public function test_guru_tidak_dapat_menginput_saat_jadwal_terkunci(): void
    {
        // Kunci SMP
        Schedule::forJenjang('SMP')->update(['is_locked' => true]);

        $classroom = Classroom::forJenjang('SMP')->first();
        $subject = Subject::forJenjang('SMP')->first();

        $payload = [
            'classroom_id' => $classroom->id,
            'subject_id'   => $subject->id,
            'hari'         => 'Selasa',
            'jam_mulai'    => 4,
            'jam_selesai'  => 4,
        ];

        $response = $this->actingAs($this->guru)->post(route('jadwal-pelajaran.saya.store'), $payload);
        $response->assertSessionHasErrors('jadwal');
        $this->assertStringContainsString('dikunci', strtolower(session('errors')->first('jadwal')));
    }

    public function test_halaman_cetak_jadwal_dapat_diakses_dan_menampilkan_matriks(): void
    {
        $responseSMP = $this->actingAs($this->admin)->get(route('jadwal-pelajaran.cetak', ['jenjang' => 'SMP']));
        $responseSMP->assertStatus(200);
        $responseSMP->assertSee('JADWAL PELAJARAN MULTI-JENJANG TINGKAT SMP');
        $responseSMP->assertSee('SEMESTER GASAL TAHUN AJARAN 2026/2027');

        $responseSMA = $this->actingAs($this->guru)->get(route('jadwal-pelajaran.cetak', ['jenjang' => 'SMA']));
        $responseSMA->assertStatus(200);
        $responseSMA->assertSee('JADWAL PELAJARAN MULTI-JENJANG TINGKAT SMA');
    }

    public function test_endpoint_ajax_cascading_kelas_dan_mapel(): void
    {
        $response = $this->actingAs($this->guru)->getJson(route('jadwal-pelajaran.ajax-classes-subjects', ['jenjang' => 'SMP']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'jenjang',
            'classrooms' => [
                '*' => ['id', 'nama_kelas', 'tingkat'],
            ],
            'subjects' => [
                '*' => ['id', 'kode', 'nama_pelajaran'],
            ],
        ]);
        $this->assertEquals('SMP', $response->json('jenjang'));
    }
}
