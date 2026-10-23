<?php

namespace Tests\Feature;

use App\Models\Jurnal;
use App\Models\JurnalAbsensi;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapAbsensiSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_sidebar_rekap_menu_contains_sekolah_and_guru_submenus(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('beranda'));

        $response->assertStatus(200);

        // Menu grup Rekap ada di sidebar
        $response->assertSee('navGroupRekapAbsensi');
        $response->assertSee('toggleRekapAbsensi');
        $response->assertSee('Rekap');

        // Submenu Sekolah dan Guru ada di bawah Rekap
        $response->assertSee(route('rekap-absensi.sekolah'));
        $response->assertSee(route('rekap-absensi.guru'));

        // Menu Sekolah tidak lagi memiliki submenu Rekap Absensi
        $response->assertSee('navGroupSekolah');
        $response->assertSee(route('sekolah.jurnal'));
        $response->assertSee(route('sekolah.rekap-jurnal'));
    }

    public function test_halaman_rekap_absensi_sekolah_loads_matrix_table_and_statistics(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smpLembaga = Lembaga::where('kode', 'smp')->firstOrFail();

        // Buat data siswa di kelas VII A
        $siswa1 = Siswa::create([
            'nama_lengkap' => 'Adam Insan Atama',
            'kelas'        => 'VII A',
            'kamar'        => 'Reguler',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);
        $siswa2 = Siswa::create([
            'nama_lengkap' => 'Bisma Recoba Zayyan',
            'kelas'        => 'VII A',
            'kamar'        => 'Reguler',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);

        // Buat sesi jurnal pembelajaran
        $jurnal1 = Jurnal::create([
            'lembaga_id'      => $smpLembaga->id,
            'guru_id'         => $adminSMP->id,
            'nama_guru'       => $adminSMP->name,
            'kelas'           => 'VII A',
            'semester'        => 'Semester Ganjil',
            'tahun_pelajaran' => '2026/2027',
            'hari'            => 'Senin',
            'tanggal'         => '2026-10-05',
            'mata_pelajaran'  => 'Matematika',
            'jam_ke'          => 'Jam 1 - 2',
            'materi'          => 'Aljabar Dasar',
            'total_siswa'     => 2,
            'total_hadir'     => 1,
            'total_sakit'     => 1,
            'total_izin'      => 0,
            'total_alpa'      => 0,
        ]);

        JurnalAbsensi::create([
            'jurnal_id'  => $jurnal1->id,
            'siswa_id'   => $siswa1->id,
            'nama_siswa' => $siswa1->nama_lengkap,
            'status'     => 'hadir',
        ]);

        JurnalAbsensi::create([
            'jurnal_id'  => $jurnal1->id,
            'siswa_id'   => $siswa2->id,
            'nama_siswa' => $siswa2->nama_lengkap,
            'status'     => 'sakit',
            'keterangan' => 'Demam flu',
        ]);

        // Akses halaman rekap absensi sekolah
        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('rekap-absensi.sekolah', ['kelas' => 'VII A', 'bulan' => 10, 'tahun' => 2026]));

        $response->assertStatus(200);

        // Header tabel: kolom No, Nama Siswa, Tanggal 05, Mapel MTK, Akumulasi H, T, I, S, A, Aksi
        $response->assertSee('Adam Insan Atama');
        $response->assertSee('Bisma Recoba Zayyan');
        $response->assertSee('NISN: -');
        $response->assertSee('05');
        $response->assertSee('MTK');

        // Badge pill absensi H dan S
        $response->assertSee('pill-h');
        $response->assertSee('pill-s');

        // Tombol Detail
        $response->assertSee('js-btn-detail');
        $response->assertSee('modalDetailSiswa');
    }

    public function test_backward_compatibility_sekolah_rekap_absensi_route(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('sekolah.rekap-absensi'));

        $response->assertStatus(200);
        $response->assertSee('Rekap Absensi Siswa');
    }
}
