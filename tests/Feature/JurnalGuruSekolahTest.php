<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurnalGuruSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_guru_dropdown_only_lists_teachers_registered_in_guru_sekolah_for_active_lembaga(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $sma = Lembaga::where('kode', 'sma')->firstOrFail();

        // 1. Guru SMP terdaftar di menu Guru Sekolah (Disetujui)
        $guruSMP1 = User::factory()->create([
            'name'               => 'Adi Kara Prabowo, S.Pd.',
            'email'              => 'adikara@almuttaqin.id',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'PENDIDIKAN PANCASILA',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMP1->lembagas()->sync([$smp->id]);

        $guruSMP2 = User::factory()->create([
            'name'               => 'Anggun Wahyu Setyorini, M.Pd.',
            'email'              => 'anggun@almuttaqin.id',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'Matematika, Informatika',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMP2->lembagas()->sync([$smp->id]);

        // 2. Guru SMA terdaftar di menu Guru Sekolah (HANYA SMA)
        $guruSMA = User::factory()->create([
            'name'               => 'Guru Khusus SMA, M.Si.',
            'email'              => 'guru.sma@almuttaqin.id',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $sma->id,
            'mata_pelajaran'     => 'Fisika',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMA->lembagas()->sync([$sma->id]);

        // 3. User yang belum didaftarkan di Guru Sekolah
        $userNonGuruSekolah = User::factory()->create([
            'name'               => 'User Belum Terdaftar Guru Sekolah',
            'email'              => 'belum.guru@almuttaqin.id',
            'role'               => 'guru',
            'is_guru_sekolah'    => false,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => null,
            'mapel_status'       => 'belum_mengajukan',
            'is_active'          => true,
        ]);

        // Akses halaman jurnal SMP
        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('sekolah.jurnal'));

        $response->assertStatus(200);

        /** @var \Illuminate\Database\Eloquent\Collection $gurusView */
        $gurusView = $response->viewData('gurus');

        // Pastikan guru SMP resmi muncul
        $this->assertTrue($gurusView->contains('id', $guruSMP1->id), 'Guru SMP 1 harus muncul');
        $this->assertTrue($gurusView->contains('id', $guruSMP2->id), 'Guru SMP 2 harus muncul');

        // Pastikan guru SMA TIDAK muncul di jurnal SMP
        $this->assertFalse($gurusView->contains('id', $guruSMA->id), 'Guru SMA tidak boleh muncul di SMP');

        // Pastikan admin SMP (akun login yang bukan guru sekolah) TIDAK muncul di daftar guru pengajar
        $this->assertFalse($gurusView->contains('id', $adminSMP->id), 'Akun admin tidak boleh masuk daftar guru');

        // Pastikan akun yang belum terdaftar di menu Guru Sekolah TIDAK muncul
        $this->assertFalse($gurusView->contains('id', $userNonGuruSekolah->id), 'User belum terdaftar guru sekolah tidak boleh muncul');

        // Pastikan data mapel ter-render di atribut opsi
        $response->assertSee('Adi Kara Prabowo, S.Pd.');
        $response->assertSee('PENDIDIKAN PANCASILA');
        $response->assertSee('Anggun Wahyu Setyorini, M.Pd.');
        $response->assertSee('Matematika, Informatika');
    }

    public function test_guru_dropdown_in_sma_only_lists_sma_teachers(): void
    {
        $adminSMA = User::where('email', 'admin.sma@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $sma = Lembaga::where('kode', 'sma')->firstOrFail();

        $guruSMP = User::factory()->create([
            'name'               => 'Guru SMP Murni',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'IPS',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMP->lembagas()->sync([$smp->id]);

        $guruSMA = User::factory()->create([
            'name'               => 'Guru SMA Murni',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $sma->id,
            'mata_pelajaran'     => 'Biologi',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMA->lembagas()->sync([$sma->id]);

        $response = $this->actingAs($adminSMA)
            ->withSession(['active_lembaga' => 'sma'])
            ->get(route('sekolah.jurnal'));

        $response->assertStatus(200);

        $gurusView = $response->viewData('gurus');
        $this->assertTrue($gurusView->contains('id', $guruSMA->id), 'Guru SMA harus muncul di SMA');
        $this->assertFalse($gurusView->contains('id', $guruSMP->id), 'Guru SMP tidak boleh muncul di SMA');
    }

    public function test_store_jurnal_saves_with_teacher_and_subject(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $guru = User::factory()->create([
            'name'               => 'Hesky Ferdinan Dani, S.Pd.',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'Pendidikan Jasmani Olahraga dan Kesehatan',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);

        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri Budi',
            'kelas'        => 'VII A',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);

        $postData = [
            'kelas'           => 'VII A',
            'semester'        => 'Semester Ganjil',
            'tahun_pelajaran' => '2026/2027',
            'hari'            => 'Rabu',
            'tanggal'         => date('Y-m-d'),
            'guru_id'         => $guru->id,
            'mata_pelajaran'  => 'Pendidikan Jasmani Olahraga dan Kesehatan',
            'jam_ke'          => 'Jam 1 - 2 (07:30 - 09:00)',
            'materi'          => 'Kebugaran Jasmani dan Atletik',
            'catatan'         => 'Berjalan lancar di lapangan',
            'absensi'         => [
                $siswa->id => [
                    'siswa_id' => $siswa->id,
                    'status'   => 'hadir',
                    'catatan'  => '',
                ],
            ],
        ];

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('sekolah.jurnal.store'), $postData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('jurnals', [
            'guru_id'        => $guru->id,
            'nama_guru'      => 'Hesky Ferdinan Dani, S.Pd.',
            'mata_pelajaran' => 'Pendidikan Jasmani Olahraga dan Kesehatan',
            'kelas'          => 'VII A',
            'total_hadir'    => 1,
        ]);
    }
}

