<?php

namespace Tests\Feature;

use App\Models\MasterDevice;
use App\Models\MasterSesi;
use App\Models\Pendaftaran;
use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapPondokTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role'      => 'yayasan',
            'is_active' => true,
        ]);
    }

    private function createSantriWithPendaftaran(array $siswaAttrs, array $pendaftaranAttrs = []): Siswa
    {
        $pendaftaran = Pendaftaran::create(array_merge([
            'nama_lengkap'  => $siswaAttrs['nama_lengkap'] ?? 'Santri Uji',
            'nisn'          => '0012345678',
            'nik'           => (string) rand(1000000000000000, 9999999999999999),
            'jenis_kelamin' => $siswaAttrs['jenis_kelamin'] ?? 'Laki-laki',
            'tempat_lahir'  => 'Madiun',
            'tanggal_lahir' => '2010-01-01',
            'alamat_rumah'  => 'Jl. Pondok No. 1',
            'jenjang'       => $siswaAttrs['jenjang'] ?? 'SMP AL MUTTAQIN',
            'status'        => 'diterima',
        ], $pendaftaranAttrs));

        return Siswa::create(array_merge([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap'   => $pendaftaran->nama_lengkap,
            'kamar'          => 'Putra A (Kamar 1)',
            'jenjang'        => 'SMP AL MUTTAQIN',
            'kelas'          => '7A',
            'jenis_kelamin'  => 'Laki-laki',
            'status'         => 'aktif',
        ], $siswaAttrs));
    }

    public function test_halaman_rekap_sholat_dapat_diakses_dan_menampilkan_menu_sidebar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('rekap.sholat.index'));

        $response->assertStatus(200);
        $response->assertSee('Rekap Presensi Sholat');
        $response->assertSee('Data Presensi Sholat');
        $response->assertSee('Nama');
        $response->assertSee('Hadir');
        $response->assertSee('Telat');
        $response->assertSee('Izin');
        $response->assertSee('Alfa');
        $response->assertSee('Unduh Rekap');
        $response->assertSee('Rincian Kehadiran Siswa');
        $response->assertSee('Sholat'); // Submenu di sidebar
    }

    public function test_presensi_sholat_harian_menampilkan_status_dan_jam_tap_dengan_benar(): void
    {
        $tanggal = '2026-10-31';

        MasterDevice::create([
            'device_id'   => 'tap_masjid',
            'nama_lokasi' => 'Timur Masjid',
            'kategori'    => 'pondok',
            'is_active'   => true,
        ]);

        $sesiDzuhur = MasterSesi::create([
            'kode_sesi'       => 'SHOLAT_DZUHUR',
            'nama_sesi'       => 'Sholat Dzuhur Berjamaah',
            'kategori'        => 'sholat',
            'jam_mulai'       => '11:30:00',
            'jam_selesai'     => '13:00:00',
            'toleransi_menit' => 15,
            'is_active'       => true,
        ]);

        // 1. Santri Hadir Tepat Waktu di Dzuhur (tap 11:40, toleransi s/d 11:45)
        $santriHadir = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Ahmad Dzuhur',
            'kamar'         => 'Putra A (Kamar 1)',
            'jenis_kelamin' => 'Laki-laki',
            'rfid_uid'      => 'UID001',
        ], ['nisn' => '1000000001']);

        Presensi::create([
            'device_id'        => 'tap_masjid',
            'sesi_id'          => $sesiDzuhur->id,
            'tipe_entitas'     => 'santri',
            'entitas_id'       => $santriHadir->id,
            'rfid_uid'         => 'UID001',
            'waktu_tap'        => Carbon::parse("$tanggal 11:40:00"),
            'status_kehadiran' => 'tepat_waktu',
        ]);

        // 2. Santri Terlambat di Dzuhur (tap 12:05)
        $santriTerlambat = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Budi Telat',
            'kamar'         => 'Putra A (Kamar 1)',
            'jenis_kelamin' => 'Laki-laki',
            'rfid_uid'      => 'UID002',
        ], ['nisn' => '1000000002']);

        Presensi::create([
            'device_id'        => 'tap_masjid',
            'sesi_id'          => $sesiDzuhur->id,
            'tipe_entitas'     => 'santri',
            'entitas_id'       => $santriTerlambat->id,
            'rfid_uid'         => 'UID002',
            'waktu_tap'        => Carbon::parse("$tanggal 12:05:00"),
            'status_kehadiran' => 'terlambat',
        ]);

        // 3. Santri Izin Sambang
        $santriIzin = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Chandra Izin',
            'kamar'         => 'Putra A (Kamar 2)',
            'jenis_kelamin' => 'Laki-laki',
            'rfid_uid'      => 'UID003',
        ], ['nisn' => '1000000003']);

        PerizinanSambang::create([
            'siswa_id'        => $santriIzin->id,
            'nama'            => $santriIzin->nama_lengkap,
            'kamar'           => $santriIzin->kamar,
            'jenjang'         => 'SMP',
            'tanggal_mulai'   => '2026-10-30',
            'tanggal_kembali' => '2026-11-01',
            'keterangan'      => 'Pulang keluarga',
            'status'          => 'sambang',
        ]);

        $response = $this->actingAs($this->admin)->get(route('rekap.sholat.index', [
            'rentang' => 'harian',
            'tanggal' => $tanggal,
        ]));

        $response->assertStatus(200);

        $rows = collect($response->viewData('rows'))->keyBy('nama_lengkap');

        $this->assertEquals('H', $rows['Ahmad Dzuhur']['sholat']['dzuhur']['code']);
        $this->assertStringContainsString('11:40', $rows['Ahmad Dzuhur']['sholat']['dzuhur']['jam']);
        $this->assertGreaterThanOrEqual(1, $rows['Ahmad Dzuhur']['total_h']);

        $this->assertEquals('T', $rows['Budi Telat']['sholat']['dzuhur']['code']);
        $this->assertStringContainsString('12:05', $rows['Budi Telat']['sholat']['dzuhur']['jam']);
        $this->assertGreaterThanOrEqual(1, $rows['Budi Telat']['total_t']);

        $this->assertEquals('I', $rows['Chandra Izin']['sholat']['dzuhur']['code']);
    }

    public function test_filter_jenjang_dan_kamar_serta_sesi_sholat(): void
    {
        $santriSmp = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Santri SMP Al-Muttaqin',
            'jenjang'      => 'SMP AL MUTTAQIN',
            'kamar'        => 'Putra A (Kamar 1)',
        ]);

        $santriSma = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Santri SMA Al-Muttaqin',
            'jenjang'      => 'SMA AL MUTTAQIN',
            'kamar'        => 'Putra B (Kamar 2)',
        ]);

        $santriReguler = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Santri Pondok Reguler',
            'jenjang'      => 'REGULER',
            'kamar'        => 'Reguler A',
        ]);

        // Filter SMP
        $resSmp = $this->actingAs($this->admin)->get(route('rekap.sholat.index', [
            'jenjang' => 'smp',
        ]));
        $resSmp->assertStatus(200);
        $rowsSmp = $resSmp->viewData('rows');
        $this->assertCount(1, $rowsSmp);
        $this->assertEquals($santriSmp->id, $rowsSmp[0]['siswa_id']);

        // Filter Kamar
        $resKamar = $this->actingAs($this->admin)->get(route('rekap.sholat.index', [
            'kamar' => 'Reguler A',
        ]));
        $resKamar->assertStatus(200);
        $rowsKamar = $resKamar->viewData('rows');
        $this->assertCount(1, $rowsKamar);
        $this->assertEquals($santriReguler->id, $rowsKamar[0]['siswa_id']);
    }

    public function test_rentang_mingguan_bulanan_dan_ekspor(): void
    {
        $sesiSubuh = MasterSesi::create([
            'kode_sesi'       => 'SHOLAT_SUBUH',
            'nama_sesi'       => 'Subuh',
            'kategori'        => 'sholat',
            'jam_mulai'       => '04:30:00',
            'jam_selesai'     => '05:15:00',
            'toleransi_menit' => 10,
            'is_active'       => true,
        ]);

        $santri = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Zaid Santri',
            'kamar'        => 'Putra A (Kamar 1)',
        ]);

        Presensi::create([
            'device_id'        => 'tap_masjid',
            'sesi_id'          => $sesiSubuh->id,
            'tipe_entitas'     => 'santri',
            'entitas_id'       => $santri->id,
            'rfid_uid'         => 'UID999',
            'waktu_tap'        => Carbon::parse('2026-10-01 04:35:00'),
            'status_kehadiran' => 'tepat_waktu',
        ]);

        // 1. Mode Bulanan
        $resBulanan = $this->actingAs($this->admin)->get(route('rekap.sholat.index', [
            'rentang' => 'bulanan',
            'bulan'   => 10,
            'tahun'   => 2026,
        ]));
        $resBulanan->assertStatus(200);
        $rowsBulanan = $resBulanan->viewData('rows');
        $this->assertCount(1, $rowsBulanan);
        $this->assertGreaterThanOrEqual(1, $rowsBulanan[0]['total_h']);

        // 2. Ekspor Excel
        $resExcel = $this->actingAs($this->admin)->get(route('rekap.sholat.export', [
            'format'  => 'excel',
            'rentang' => 'harian',
            'tanggal' => '2026-10-01',
        ]));
        $resExcel->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resExcel->headers->get('Content-Type'));

        // 3. Ekspor PDF / Cetak
        $resPdf = $this->actingAs($this->admin)->get(route('rekap.sholat.export', [
            'format'  => 'pdf',
            'rentang' => 'harian',
            'tanggal' => '2026-10-01',
        ]));
        $resPdf->assertStatus(200);
        $resPdf->assertSee('Zaid Santri');
    }
}
