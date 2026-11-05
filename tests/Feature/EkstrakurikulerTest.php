<?php

namespace Tests\Feature;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerSiswa;
use App\Models\JurnalEkskul;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EkstrakurikulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_admin_can_access_plotting_ekskul_and_create_ekskul(): void
    {
        $admin = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        // 1. Buka halaman Plotting Ekskul
        $response = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-ekskul.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Ekstrakurikuler');
        $response->assertSee('Belum Ikut Ekskul');

        // Filter kelas disesuaikan dengan nama kelas yang ada di plotting kelas (VII A, VII B, VIII A, VIII B, IX A, IX B)
        $response->assertSee('Kelas VII A');
        $response->assertSee('Kelas VIII A');
        $response->assertSee('Kelas IX A');
        $response->assertSee('Kelas IX B');

        // Form mutasi dan modal lihat anggota tidak memuat peran
        $response->assertDontSee('Peran dalam Ekskul');
        $response->assertDontSee('<th class="fi-ta-th">Peran</th>', false);

        // Kolom tabel sesuai permintaan: Nama Ekskul, Pembina, Peserta, Aksi
        $response->assertSee('Nama Ekskul');
        $response->assertSee('Pembina');
        $response->assertSee('Peserta');
        $response->assertSee('Aksi');
        $response->assertDontSee('JADWAL & TEMPAT');

        // Form hanya nama ekskul dan pembina
        $response->assertSee('Nama Pembina');
        $response->assertDontSee('Hari Latihan');
        $response->assertDontSee('Jam Mulai');
        $response->assertDontSee('Tempat / Lokasi Latihan');

        // 2. Tambah Ekskul Baru (Hanya berisi nama ekskul dan pembina ekskul)
        $storeResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-ekskul.store-ekskul'), [
                'lembaga_id'   => $smp->id,
                'jenjang'      => 'SMP',
                'nama_ekskul'  => 'Pramuka Penggalang',
                'pembina_id'   => $admin->id,
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('ekstrakurikulers', [
            'nama_ekskul'  => 'Pramuka Penggalang',
            'pembina_id'   => $admin->id,
            'nama_pembina' => $admin->name,
            'jenjang'      => 'SMP',
        ]);
    }

    public function test_pembina_dropdown_only_lists_teachers_registered_in_guru_sekolah_for_active_lembaga(): void
    {
        $admin = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $sma = Lembaga::where('kode', 'sma')->firstOrFail();

        $guruSMP = User::factory()->create([
            'name'               => 'Guru Pembina SMP, S.Pd.',
            'email'              => 'pembina.smp@almuttaqin.id',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMP->lembagas()->sync([$smp->id]);

        $guruSMA = User::factory()->create([
            'name'               => 'Guru Pembina SMA, M.Pd.',
            'email'              => 'pembina.sma@almuttaqin.id',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $sma->id,
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);
        $guruSMA->lembagas()->sync([$sma->id]);

        // Cek halaman SMP
        $responseSMP = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-ekskul.index', ['lembaga' => 'smp']));

        $responseSMP->assertStatus(200);
        $responseSMP->assertSee('Guru Pembina SMP, S.Pd.');
        $responseSMP->assertDontSee('Guru Pembina SMA, M.Pd.');
    }

    public function test_admin_can_assign_and_unassign_student_to_ekskul(): void
    {
        $admin = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $ekskul = Ekstrakurikuler::create([
            'lembaga_id'   => $smp->id,
            'jenjang'      => 'SMP',
            'nama_ekskul'  => 'Futsal Club',
            'kode'         => 'FTSL',
            'hari'         => 'Sabtu',
            'is_active'    => true,
        ]);

        $siswa = Siswa::create([
            'nama_lengkap' => 'Ahmad Futsalis',
            'nisn'         => '1234567890',
            'jenjang'      => 'SMP',
            'kelas'        => 'VII A',
            'status'       => 'aktif',
        ]);

        // 1. Tambah Anggota
        $assignResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-ekskul.tambah-anggota'), [
                'ekstrakurikuler_id' => $ekskul->id,
                'siswa_id'           => [$siswa->id],
                'peran'              => 'anggota',
            ]);

        $assignResponse->assertRedirect();
        $this->assertDatabaseHas('ekstrakurikuler_siswas', [
            'ekstrakurikuler_id' => $ekskul->id,
            'siswa_id'           => $siswa->id,
        ]);

        // 2. Endpoint JSON Anggota
        $jsonResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->getJson(route('plotting-ekskul.anggota', $ekskul->id));

        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonFragment([
            'nama_lengkap' => 'Ahmad Futsalis',
        ]);

        // 3. Hapus Anggota
        $hapusResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-ekskul.hapus-anggota'), [
                'ekstrakurikuler_id' => $ekskul->id,
                'siswa_id'           => [$siswa->id],
            ]);

        $hapusResponse->assertRedirect();
        $this->assertDatabaseMissing('ekstrakurikuler_siswas', [
            'ekstrakurikuler_id' => $ekskul->id,
            'siswa_id'           => $siswa->id,
        ]);
    }

    public function test_teacher_can_input_jurnal_and_attendance(): void
    {
        $admin = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $ekskul = Ekstrakurikuler::create([
            'lembaga_id'   => $smp->id,
            'jenjang'      => 'SMP',
            'nama_ekskul'  => 'Hadroh Banjari',
            'pembina_id'   => $admin->id,
            'nama_pembina' => $admin->name,
            'hari'         => 'Kamis',
            'is_active'    => true,
        ]);

        $siswa1 = Siswa::create([
            'nama_lengkap' => 'Santri Hadroh 1',
            'jenjang'      => 'SMP',
            'kelas'        => 'VIII A',
            'status'       => 'aktif',
        ]);
        $siswa2 = Siswa::create([
            'nama_lengkap' => 'Santri Hadroh 2',
            'jenjang'      => 'SMP',
            'kelas'        => 'VIII B',
            'status'       => 'aktif',
        ]);

        EkstrakurikulerSiswa::create(['ekstrakurikuler_id' => $ekskul->id, 'siswa_id' => $siswa1->id, 'peran' => 'ketua', 'is_active' => true]);
        EkstrakurikulerSiswa::create(['ekstrakurikuler_id' => $ekskul->id, 'siswa_id' => $siswa2->id, 'peran' => 'anggota', 'is_active' => true]);

        // 1. Buka Halaman Input Jurnal
        $pageResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('sekolah.ekskul.index', ['ekskul_id' => $ekskul->id]));

        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Form Input Jurnal Kegiatan Ekstrakurikuler');

        // 2. Simpan Jurnal & Presensi
        $postResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('sekolah.ekskul.store'), [
                'ekstrakurikuler_id' => $ekskul->id,
                'tanggal'            => '2026-11-05',
                'hari'               => 'Kamis',
                'jam_mulai'          => '15:30',
                'jam_selesai'        => '17:00',
                'tempat'             => 'Aula Asrama',
                'pembina_id'         => $admin->id,
                'materi'             => 'Latihan pukulan dasar terbang dan sholawat badar',
                'catatan'            => 'Peserta antusias dan kompak',
                'absensi'            => [
                    $siswa1->id => ['status' => 'hadir', 'keterangan' => ''],
                    $siswa2->id => ['status' => 'izin', 'keterangan' => 'Ada jadwal dokter gigi'],
                ],
            ]);

        $postResponse->assertRedirect(route('sekolah.ekskul.rekap-jurnal'));

        $this->assertDatabaseHas('jurnal_ekskuls', [
            'ekstrakurikuler_id' => $ekskul->id,
            'materi'             => 'Latihan pukulan dasar terbang dan sholawat badar',
            'total_siswa'        => 2,
            'total_hadir'        => 1,
            'total_izin'         => 1,
        ]);

        $this->assertDatabaseHas('jurnal_ekskul_absensis', [
            'siswa_id' => $siswa1->id,
            'status'   => 'hadir',
        ]);
        $this->assertDatabaseHas('jurnal_ekskul_absensis', [
            'siswa_id' => $siswa2->id,
            'status'   => 'izin',
        ]);
    }

    public function test_rekap_jurnal_and_rekap_absensi_ekskul_matrix_display(): void
    {
        $admin = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $ekskul = Ekstrakurikuler::create([
            'lembaga_id'   => $smp->id,
            'jenjang'      => 'SMP',
            'nama_ekskul'  => 'Robotik & IT',
            'hari'         => 'Rabu',
            'is_active'    => true,
        ]);

        // 1. Rekap Jurnal
        $rekapJurnalResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('sekolah.ekskul.rekap-jurnal', ['ekskul_id' => $ekskul->id]));

        $rekapJurnalResponse->assertStatus(200);
        $rekapJurnalResponse->assertSee('Rekap Jurnal Ekstrakurikuler');

        // 2. Rekap Absensi Matrix Ekskul
        $rekapMatrixResponse = $this->actingAs($admin)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('rekap-absensi.ekskul', ['ekskul_id' => $ekskul->id]));

        $rekapMatrixResponse->assertStatus(200);
        $rekapMatrixResponse->assertSee('Rekap Presensi Ekstrakurikuler');
        $rekapMatrixResponse->assertSee('Robotik & IT');
    }
}

