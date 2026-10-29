<?php

namespace Tests\Feature;

use App\Http\Controllers\SekolahController;
use App\Models\Classroom;
use App\Models\Jurnal;
use App\Models\JurnalAbsensi;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SinkronisasiKelasJurnalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_siswa_dengan_kelas_7a_muncul_di_jurnal_pembelajaran_kelas_vii_a(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smpLembaga = Lembaga::where('kode', 'smp')->firstOrFail();

        // Buat kelas 7A di tabel classrooms (seperti yang dibuat dari menu Plotting Kelas)
        Classroom::create([
            'lembaga_id' => $smpLembaga->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => '7A',
            'tingkat'    => 7,
            'urutan'     => 1,
            'is_active'  => true,
        ]);

        // Buat siswa aktif dengan kelas '7A'
        $siswa = Siswa::create([
            'nama_lengkap' => 'Adam Insan Atama',
            'kelas'        => '7A',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('sekolah.jurnal'));

        $response->assertStatus(200);

        // Siswa harus ada di data view siswasByKelas baik diakses via 'VII A' maupun '7A'
        $siswasByKelas = $response->viewData('siswasByKelas');
        $this->assertNotEmpty($siswasByKelas['VII A'] ?? null, 'Siswa 7A harus sinkron ke VII A');
        $this->assertTrue($siswasByKelas['VII A']->contains('id', $siswa->id));

        $this->assertNotEmpty($siswasByKelas['7A'] ?? null, 'Siswa 7A harus dapat diakses via 7A');
        $this->assertTrue($siswasByKelas['7A']->contains('id', $siswa->id));

        // Nama siswa harus dirender di script / HTML
        $response->assertSee('Adam Insan Atama');
    }

    public function test_rekap_absensi_menemukan_siswa_7a_dan_jurnal_vii_a(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smpLembaga = Lembaga::where('kode', 'smp')->firstOrFail();

        // Siswa tersimpan dengan kelas '7A'
        $siswa = Siswa::create([
            'nama_lengkap' => 'Ahza Danis Nur Pratama',
            'kelas'        => '7A',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);

        // Jurnal tersimpan dengan kelas '7A'
        $jurnal = Jurnal::create([
            'lembaga_id'      => $smpLembaga->id,
            'guru_id'         => $adminSMP->id,
            'nama_guru'       => $adminSMP->name,
            'kelas'           => '7A',
            'semester'        => 'Semester Ganjil',
            'tahun_pelajaran' => '2026/2027',
            'hari'            => 'Senin',
            'tanggal'         => date('Y-m-d'),
            'mata_pelajaran'  => 'IPA',
            'jam_ke'          => 'Jam 1 - 2',
            'materi'          => 'Ekosistem',
            'total_siswa'     => 1,
            'total_hadir'     => 1,
            'total_sakit'     => 0,
            'total_izin'      => 0,
            'total_alpa'      => 0,
        ]);

        JurnalAbsensi::create([
            'jurnal_id'  => $jurnal->id,
            'siswa_id'   => $siswa->id,
            'nama_siswa' => $siswa->nama_lengkap,
            'status'     => 'hadir',
        ]);

        // Buka rekap absensi dengan filter 'VII A' (format Romawi)
        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('rekap-absensi.sekolah', ['kelas' => 'VII A']));

        $response->assertStatus(200);
        $response->assertSee('Ahza Danis Nur Pratama');
        $response->assertSee('Ekosistem');

        // Buka rekap jurnal dengan filter 'VII A'
        $responseJurnal = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('sekolah.rekap-jurnal', ['kelas' => 'VII A']));

        $responseJurnal->assertStatus(200);
        $responseJurnal->assertSee('Ekosistem');
    }

    public function test_get_equivalent_class_names_helper(): void
    {
        $equivalents7A = SekolahController::getEquivalentClassNames('7A');
        $this->assertContains('7A', $equivalents7A);
        $this->assertContains('VII A', $equivalents7A);
        $this->assertContains('Kelas VII A', $equivalents7A);

        $equivalentsVIIA = SekolahController::getEquivalentClassNames('VII A');
        $this->assertContains('VII A', $equivalentsVIIA);
        $this->assertContains('7A', $equivalentsVIIA);
    }
}
