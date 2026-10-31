<?php

namespace Tests\Feature;

use App\Models\MasterDevice;
use App\Models\MasterKelasPengajian;
use App\Models\MasterSesi;
use App\Models\Pendaftaran;
use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\Presensi;
use App\Models\SesiDeviceAllocation;
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

    public function test_halaman_rekap_pondok_dapat_diakses_dan_menampilkan_menu_sidebar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('rekap.pondok.index'));

        $response->assertStatus(200);
        $response->assertSee('Rekap Presensi Pondok');
        $response->assertSee('Total Target Santri');
        $response->assertSee('Hadir Tepat Waktu');
        $response->assertSee('Terlambat');
        $response->assertSee('Tidak Hadir / Alfa');
        $response->assertSee('Izin / Sakit');
        $response->assertSee('Rekap Harian (Detail Log Sesi)');
        $response->assertSee('Matriks Bulanan');
    }

    public function test_kalkulasi_status_hadir_terlambat_izin_sakit_dan_alfa_pada_rekap_harian(): void
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
            'nama_sesi'       => 'Dzuhur',
            'kategori'        => 'sholat',
            'jam_mulai'       => '11:45:00',
            'jam_selesai'     => '12:30:00',
            'toleransi_menit' => 10,
            'is_active'       => true,
        ]);

        SesiDeviceAllocation::create([
            'master_sesi_id'  => $sesiDzuhur->id,
            'device_id'       => 'tap_masjid',
            'target_kategori' => 'semua',
            'target_filter'   => null,
            'target_gender'   => 'ALL',
        ]);

        // 1. Santri Hadir Tepat Waktu (tap pukul 11:50, batas toleransi 11:55)
        $santriHadir = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Ahmad Hadir',
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
            'waktu_tap'        => Carbon::parse("$tanggal 11:50:00"),
            'status_kehadiran' => 'tepat_waktu',
        ]);

        // 2. Santri Terlambat (tap pukul 12:05, lewat batas toleransi 11:55)
        $santriTerlambat = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Budi Terlambat',
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

        // 3. Santri Izin Sambang (tidak tap, tapi ada PerizinanSambang aktif di tanggal tsb)
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
            'keterangan'      => 'Pulang acara keluarga',
            'status'          => 'sambang',
        ]);

        // 4. Santri Sakit (tidak tap, tapi ada PerizinanSakit di tanggal tsb)
        $santriSakit = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Dedi Sakit',
            'kamar'         => 'Putra A (Kamar 2)',
            'jenis_kelamin' => 'Laki-laki',
            'rfid_uid'      => 'UID004',
        ], ['nisn' => '1000000004']);

        PerizinanSakit::create([
            'siswa_id'      => $santriSakit->id,
            'nama'          => $santriSakit->nama_lengkap,
            'kamar'         => $santriSakit->kamar,
            'jenjang'       => 'SMP',
            'tanggal_sakit' => $tanggal,
            'sakit'         => 'Demam',
            'penanganan'    => 'UKS',
            'status'        => 'sakit',
        ]);

        // 5. Santri Alfa (tidak tap dan tidak ada izin/sakit)
        $santriAlfa = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Eko Alfa',
            'kamar'         => 'Putra A (Kamar 3)',
            'jenis_kelamin' => 'Laki-laki',
            'rfid_uid'      => 'UID005',
        ], ['nisn' => '1000000005']);

        $response = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kategori' => 'sholat',
            'sesi_id'  => $sesiDzuhur->id,
            'tanggal'  => $tanggal,
            'mode'     => 'harian',
        ]));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertEquals(5, $stats['total_target']);
        $this->assertEquals(1, $stats['hadir']);
        $this->assertEquals(1, $stats['terlambat']);
        $this->assertEquals(2, $stats['izin_sakit']);
        $this->assertEquals(1, $stats['izin']);
        $this->assertEquals(1, $stats['sakit']);
        $this->assertEquals(1, $stats['alfa']);

        $dailyRows = collect($response->viewData('dailyRows'))->keyBy('nama_lengkap');
        $this->assertEquals('hadir', $dailyRows['Ahmad Hadir']['status']);
        $this->assertEquals('11:50:00', $dailyRows['Ahmad Hadir']['jam_tap']);
        $this->assertStringContainsString('tap_masjid', $dailyRows['Ahmad Hadir']['device_label']);

        $this->assertEquals('terlambat', $dailyRows['Budi Terlambat']['status']);
        $this->assertEquals('izin', $dailyRows['Chandra Izin']['status']);
        $this->assertEquals('sakit', $dailyRows['Dedi Sakit']['status']);
        $this->assertEquals('alfa', $dailyRows['Eko Alfa']['status']);
    }

    public function test_filter_target_sesi_pengajian_dan_gender_berjalan_akurat(): void
    {
        $kelasLambatan = MasterKelasPengajian::create(['kode_kelas' => 'LAMBATAN', 'nama_kelas' => 'Lambatan']);
        $kelasCepatan  = MasterKelasPengajian::create(['kode_kelas' => 'CEPATAN_SMP', 'nama_kelas' => 'Cepatan SMP']);

        $sesiLambatanPutri = MasterSesi::create([
            'kode_sesi'       => 'NGAJI_LAMBATAN',
            'nama_sesi'       => 'lambatan',
            'kategori'        => 'pengajian',
            'jam_mulai'       => '13:00:00',
            'jam_selesai'     => '14:00:00',
            'toleransi_menit' => 15,
            'is_active'       => true,
        ]);

        SesiDeviceAllocation::create([
            'master_sesi_id'  => $sesiLambatanPutri->id,
            'device_id'       => 'tap_masjid',
            'target_kategori' => 'kelas_pengajian',
            'target_filter'   => 'Lambatan',
            'target_gender'   => 'P',
        ]);

        // Santri 1: Putri + Kelas Lambatan -> WAJIB MASUK TARGET
        $santriPutriLambatan = $this->createSantriWithPendaftaran([
            'nama_lengkap'       => 'Siti Lambatan Putri',
            'jenis_kelamin'      => 'Perempuan',
            'kamar'              => 'Putri A (Kamar 1)',
            'kelas_pengajian_id' => $kelasLambatan->id,
        ], ['nisn' => '2000000001']);

        // Santri 2: Putra + Kelas Lambatan -> TIDAK MASUK TARGET (karena alokasi sesi khusus P)
        $this->createSantriWithPendaftaran([
            'nama_lengkap'       => 'Rudi Lambatan Putra',
            'jenis_kelamin'      => 'Laki-laki',
            'kamar'              => 'Putra A (Kamar 1)',
            'kelas_pengajian_id' => $kelasLambatan->id,
        ], ['nisn' => '2000000002']);

        // Santri 3: Putri + Kelas Cepatan -> TIDAK MASUK TARGET
        $this->createSantriWithPendaftaran([
            'nama_lengkap'       => 'Nisa Cepatan Putri',
            'jenis_kelamin'      => 'Perempuan',
            'kamar'              => 'Putri A (Kamar 2)',
            'kelas_pengajian_id' => $kelasCepatan->id,
        ], ['nisn' => '2000000003']);

        $response = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kategori' => 'pengajian',
            'sesi_id'  => $sesiLambatanPutri->id,
            'tanggal'  => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $dailyRows = $response->viewData('dailyRows');
        $this->assertCount(1, $dailyRows);
        $this->assertEquals($santriPutriLambatan->id, $dailyRows[0]['siswa_id']);
    }

    public function test_matriks_bulanan_dan_ekspor_csv_serta_pdf_berjalan_baik(): void
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
            'nama_lengkap'  => 'Zaid Santri',
            'kamar'         => 'Putra A (Kamar 1)',
            'jenis_kelamin' => 'Laki-laki',
        ], ['nisn' => '3000000001']);

        Presensi::create([
            'device_id'        => 'tap_masjid',
            'sesi_id'          => $sesiSubuh->id,
            'tipe_entitas'     => 'santri',
            'entitas_id'       => $santri->id,
            'rfid_uid'         => 'UID999',
            'waktu_tap'        => Carbon::parse('2026-10-01 04:35:00'),
            'status_kehadiran' => 'tepat_waktu',
        ]);

        // 1. Cek Mode Matriks Bulanan
        $resBulanan = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'mode'    => 'bulanan',
            'sesi_id' => $sesiSubuh->id,
            'bulan'   => 10,
            'tahun'   => 2026,
        ]));
        $resBulanan->assertStatus(200);
        $matriksRows = $resBulanan->viewData('matriksRows');
        $this->assertCount(1, $matriksRows);
        $this->assertEquals('H', $matriksRows[0]['days'][1]['code']);
        $this->assertGreaterThanOrEqual(1, $matriksRows[0]['total_h']);

        // 2. Cek Ekspor Excel/CSV
        $resCsv = $this->actingAs($this->admin)->get(route('rekap.pondok.export', [
            'format'  => 'excel',
            'mode'    => 'harian',
            'sesi_id' => $sesiSubuh->id,
            'tanggal' => '2026-10-01',
        ]));
        $resCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resCsv->headers->get('Content-Type'));

        // 3. Cek Ekspor PDF/Cetak
        $resPdf = $this->actingAs($this->admin)->get(route('rekap.pondok.export', [
            'format'  => 'pdf',
            'mode'    => 'bulanan',
            'sesi_id' => $sesiSubuh->id,
            'bulan'   => 10,
            'tahun'   => 2026,
        ]));
        $resPdf->assertStatus(200);
        $resPdf->assertSee('Zaid Santri');
    }
}
