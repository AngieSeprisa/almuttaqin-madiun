<?php

namespace Tests\Feature;

use App\Http\Controllers\RekapPondokController;
use App\Models\MasterDevice;
use App\Models\MasterKelasPengajian;
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
            'nisn'          => '00' . rand(10000000, 99999999),
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
        // 1. Akses route utama rekap.pondok.index
        $response = $this->actingAs($this->admin)->get(route('rekap.pondok.index'));

        $response->assertStatus(200);
        $response->assertSee('Rekap Presensi Pondok');
        $response->assertSee('Data Presensi Pondok');
        $response->assertSee('Total Target Santri');
        $response->assertSee('Hadir Tepat Waktu');
        $response->assertSee('Terlambat');
        $response->assertSee('Sakit / Izin');
        $response->assertSee('Alpha / Tidak Hadir');
        $response->assertSee('Laporan WhatsApp');
        $response->assertSee('Unduh Rekap');
        $response->assertSee('Cetak PDF');
        $response->assertSee('Pondok'); // Submenu di sidebar Rekap

        // 2. Akses alias route rekap.sholat.index juga harus tetap berfungsi
        $resAlias = $this->actingAs($this->admin)->get(route('rekap.sholat.index'));
        $resAlias->assertStatus(200);

        // 3. Verifikasi dropdown Jenjang, Kamar, dan Sesi di Modal WhatsApp
        $response->assertSee('id="waJenjangSelect"', false);
        $response->assertSee('id="waKamarSelect"', false);
        $response->assertSee('id="waSessionSelect"', false);

        // 4. Verifikasi teks deskripsi berlebih sudah dihilangkan agar tampilan elegan & bersih
        $response->assertDontSee('Santri aktif dalam sasaran sesi');
        $response->assertDontSee('Tap sebelum batas toleransi');
        $response->assertDontSee('Melewati toleransi batas sesi');
        $response->assertDontSee('Format baku siap kirim ke WhatsApp tanpa embel-embel jenjang sekolah');
    }

    public function test_presensi_pondok_harian_menampilkan_status_dan_jam_tap_dengan_benar(): void
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
        ]);

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
        ]);

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
        ]);

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

        // 4. Santri Sakit
        $santriSakit = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Dedi Sakit',
            'kamar'         => 'Putra A (Kamar 2)',
            'jenis_kelamin' => 'Laki-laki',
            'rfid_uid'      => 'UID004',
        ]);

        PerizinanSakit::create([
            'siswa_id'      => $santriSakit->id,
            'nama'          => $santriSakit->nama_lengkap,
            'kamar'         => $santriSakit->kamar,
            'jenjang'       => 'SMP',
            'tanggal_sakit' => Carbon::parse($tanggal),
            'sakit'         => 'Demam Tinggi',
            'status'        => 'sakit',
        ]);

        $response = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
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
        $this->assertEquals('S', $rows['Dedi Sakit']['sholat']['dzuhur']['code']);
    }

    public function test_filter_lengkap_kategori_sesi_kelas_kamar_pengajian_gender(): void
    {
        $kpLambatan = MasterKelasPengajian::create([
            'kode_kelas' => 'KLS_LAMBATAN_SMP',
            'nama_kelas' => 'Kelas Lambatan SMP',
            'jenjang'    => 'smp',
        ]);

        $kpCepatan = MasterKelasPengajian::create([
            'kode_kelas' => 'KLS_CEPATAN_SMP',
            'nama_kelas' => 'Kelas Cepatan SMP',
            'jenjang'    => 'smp',
        ]);

        $santriPutra7A = $this->createSantriWithPendaftaran([
            'nama_lengkap'       => 'Ali Putra 7A',
            'kelas'              => '7A',
            'kamar'              => 'Kamar 1 Putra',
            'jenis_kelamin'      => 'Laki-laki',
            'kelas_pengajian_id' => $kpLambatan->id,
            'jenjang'            => 'SMP AL MUTTAQIN',
        ]);

        $santriPutri8B = $this->createSantriWithPendaftaran([
            'nama_lengkap'       => 'Aisyah Putri 8B',
            'kelas'              => '8B',
            'kamar'              => 'Kamar Banat 1',
            'jenis_kelamin'      => 'Perempuan',
            'kelas_pengajian_id' => $kpCepatan->id,
            'jenjang'            => 'SMP AL MUTTAQIN',
        ]);

        // Filter 1: Berdasarkan Kelas Sekolah (7A)
        $resKelas = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kelas' => '7A',
        ]));
        $resKelas->assertStatus(200);
        $rowsKelas = $resKelas->viewData('rows');
        $this->assertCount(1, $rowsKelas);
        $this->assertEquals('Ali Putra 7A', $rowsKelas[0]['nama_lengkap']);

        // Filter 2: Berdasarkan Kamar (Kamar Banat)
        $resKamar = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kamar' => 'Banat',
        ]));
        $resKamar->assertStatus(200);
        $rowsKamar = $resKamar->viewData('rows');
        $this->assertCount(1, $rowsKamar);
        $this->assertEquals('Aisyah Putri 8B', $rowsKamar[0]['nama_lengkap']);

        // Filter 3: Berdasarkan Kelas Pengajian (Lambatan)
        $resPengajian = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kelas_pengajian_id' => $kpLambatan->id,
        ]));
        $resPengajian->assertStatus(200);
        $rowsPengajian = $resPengajian->viewData('rows');
        $this->assertCount(1, $rowsPengajian);
        $this->assertEquals('Ali Putra 7A', $rowsPengajian[0]['nama_lengkap']);

        // Filter 4: Berdasarkan Gender Putri (P)
        $resGenderP = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'gender' => 'P',
        ]));
        $resGenderP->assertStatus(200);
        $rowsGenderP = $resGenderP->viewData('rows');
        $this->assertCount(1, $rowsGenderP);
        $this->assertEquals('Aisyah Putri 8B', $rowsGenderP[0]['nama_lengkap']);

        // Filter 5: Berdasarkan Gender Putra (L)
        $resGenderL = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'gender' => 'L',
        ]));
        $resGenderL->assertStatus(200);
        $rowsGenderL = $resGenderL->viewData('rows');
        $this->assertCount(1, $rowsGenderL);
        $this->assertEquals('Ali Putra 7A', $rowsGenderL[0]['nama_lengkap']);
    }

    public function test_format_laporan_whatsapp_persis_sesuai_template(): void
    {
        $tanggal = '2026-10-31'; // Sabtu

        $sesiDzuhur = MasterSesi::create([
            'kode_sesi'       => 'SHOLAT_DZUHUR',
            'nama_sesi'       => 'Dzuhur',
            'kategori'        => 'sholat',
            'jam_mulai'       => '11:30:00',
            'jam_selesai'     => '13:00:00',
            'toleransi_menit' => 15,
            'is_active'       => true,
        ]);

        // 1. Santri Hadir Tepat Waktu (dengan nama mengandung suffix yang harus dibersihkan)
        $s1 = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Ahmad Fauzi (MTK-SMP)',
            'kelas'        => '7A',
            'rfid_uid'     => 'UID_WA_1',
        ]);
        Presensi::create([
            'device_id'        => 'tap_masjid',
            'sesi_id'          => $sesiDzuhur->id,
            'tipe_entitas'     => 'santri',
            'entitas_id'       => $s1->id,
            'rfid_uid'         => 'UID_WA_1',
            'waktu_tap'        => Carbon::parse("$tanggal 11:35:00"),
            'status_kehadiran' => 'tepat_waktu',
        ]);

        // 2. Santri Terlambat
        $s2 = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Bambang Wijaya (SMP)',
            'kelas'        => '7A',
            'rfid_uid'     => 'UID_WA_2',
        ]);
        Presensi::create([
            'device_id'        => 'tap_masjid',
            'sesi_id'          => $sesiDzuhur->id,
            'tipe_entitas'     => 'santri',
            'entitas_id'       => $s2->id,
            'rfid_uid'         => 'UID_WA_2',
            'waktu_tap'        => Carbon::parse("$tanggal 12:10:00"),
            'status_kehadiran' => 'terlambat',
        ]);

        // 3. Santri Izin
        $s3 = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Cahyo Utomo',
            'kelas'        => '7A',
            'kamar'        => 'Putra A (Kamar 1)',
            'rfid_uid'     => 'UID_WA_3',
        ]);
        PerizinanSambang::create([
            'siswa_id'        => $s3->id,
            'nama'            => $s3->nama_lengkap,
            'kamar'           => $s3->kamar,
            'jenjang'         => 'SMP',
            'tanggal_mulai'   => '2026-10-30',
            'tanggal_kembali' => '2026-11-02',
            'status'          => 'sambang',
        ]);

        // 4. Santri Alpa
        $s4 = $this->createSantriWithPendaftaran([
            'nama_lengkap' => 'Doni Saputra',
            'kelas'        => '7A',
            'rfid_uid'     => 'UID_WA_4',
        ]);

        $response = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kelas'   => '7A',
            'tanggal' => $tanggal,
            'sesi_id' => 'dzuhur',
        ]));

        $response->assertStatus(200);

        $waText = $response->viewData('waText');

        // Verifikasi Format Baku:
        // 1. Header Target
        $this->assertStringContainsString('📅 *LAPORAN ABSENSI SHOLAT SISWA KELAS 7A*', $waText);
        // 2. Tanggal Bahasa Indonesia Huruf Miring
        $this->assertStringContainsString('_Sabtu, 31 Oktober 2026_', $waText);
        // 3. Waktu Sholat
        $this->assertStringContainsString('*Waktu Sholat:* DZUHUR', $waText);
        // 4. Daftar Kehadiran Siswa
        $this->assertStringContainsString('*Daftar Kehadiran Siswa:*', $waText);

        // 5. Nama Santri Bersih Tanpa Embel-embel (MTK-SMP)
        $this->assertStringContainsString('1. Ahmad Fauzi ✅', $waText);
        $this->assertStringNotContainsString('(MTK-SMP)', $waText);

        // 6. Telat, Izin, Alpa
        $this->assertStringContainsString('2. Bambang Wijaya ⚠️ - (telat)', $waText);
        $this->assertStringNotContainsString('(SMP)', $waText);
        $this->assertStringContainsString('3. Cahyo Utomo ✉️ - IZIN', $waText);
        $this->assertStringContainsString('4. Doni Saputra ❌ - ALPA', $waText);

        // 7. Ringkasan Kehadiran
        $this->assertStringContainsString('*Ringkasan Kehadiran:*', $waText);
        $this->assertStringContainsString('• Hadir: 1 Siswa', $waText);
        $this->assertStringContainsString('• Sakit/Izin/Terlambat: 2 Siswa', $waText);
        $this->assertStringContainsString('• Alpha: 1 Siswa', $waText);

        // 8. Uji laporan WhatsApp dengan sasaran jenjang SMP
        $resJenjang = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'jenjang' => 'smp',
            'tanggal' => $tanggal,
            'sesi_id' => 'dzuhur',
        ]));
        $resJenjang->assertStatus(200);
        $waJenjangText = $resJenjang->viewData('waText');
        $this->assertStringContainsString('📅 *LAPORAN ABSENSI SHOLAT SISWA KELAS SMP*', $waJenjangText);
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
        $resBulanan = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'rentang' => 'bulanan',
            'bulan'   => 10,
            'tahun'   => 2026,
        ]));
        $resBulanan->assertStatus(200);
        $rowsBulanan = $resBulanan->viewData('rows');
        $this->assertCount(1, $rowsBulanan);
        $this->assertGreaterThanOrEqual(1, $rowsBulanan[0]['total_h']);

        // 2. Ekspor Excel
        $resExcel = $this->actingAs($this->admin)->get(route('rekap.pondok.export', [
            'format'  => 'excel',
            'rentang' => 'harian',
            'tanggal' => '2026-10-01',
        ]));
        $resExcel->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resExcel->headers->get('Content-Type'));

        // 3. Ekspor PDF / Cetak
        $resPdf = $this->actingAs($this->admin)->get(route('rekap.pondok.export', [
            'format'  => 'pdf',
            'rentang' => 'harian',
            'tanggal' => '2026-10-01',
        ]));
        $resPdf->assertStatus(200);
        $resPdf->assertSee('Zaid Santri');
    }

    public function test_akses_rekap_pondok_dengan_akun_guru_dan_musyrif_tanpa_dibatasi_wali_kelas(): void
    {
        // Buat beberapa santri aktif
        $this->createSantriWithPendaftaran(['nama_lengkap' => 'Santri Satu', 'kamar' => 'Kamar Abu Bakar']);
        $this->createSantriWithPendaftaran(['nama_lengkap' => 'Santri Dua', 'kamar' => 'Kamar Umar']);

        // Akun Guru biasa (bukan wali kelas dan bukan admin yayasan)
        $userGuru = User::factory()->create([
            'role'      => 'guru',
            'is_active' => true,
        ]);

        $resGuru = $this->actingAs($userGuru)->get(route('rekap.pondok.index'));
        $resGuru->assertStatus(200);
        $rowsGuru = $resGuru->viewData('rows');
        $this->assertGreaterThanOrEqual(2, count($rowsGuru));

        // Akun Musyrif (role musyrif)
        $userMusyrif = User::factory()->create([
            'role'      => 'musyrif',
            'is_active' => true,
        ]);

        $resMusyrif = $this->actingAs($userMusyrif)->get(route('rekap.pondok.index'));
        $resMusyrif->assertStatus(200);
        $rowsMusyrif = $resMusyrif->viewData('rows');
        $this->assertGreaterThanOrEqual(2, count($rowsMusyrif));
    }

    public function test_filter_fleksibel_kamar_gender_dan_fallback_null(): void
    {
        // 1. Santri disimpan dengan kamar "Abu Bakar" (tanpa kata "Kamar "), tapi difilter dengan "Kamar Abu Bakar"
        $s1 = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Santri Abu Bakar',
            'kamar'         => 'Abu Bakar',
            'jenis_kelamin' => 'Laki-laki',
            'kelas'         => 'Kelas 7A',
        ]);

        $resKamar = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kamar' => 'Kamar Abu Bakar',
        ]));
        $resKamar->assertStatus(200);
        $rowsKamar = collect($resKamar->viewData('rows'))->pluck('nama_lengkap')->all();
        $this->assertContains('Santri Abu Bakar', $rowsKamar);

        // 2. Filter kelas fleksibel ("7A" vs "Kelas 7A")
        $resKelas = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kelas' => '7A',
        ]));
        $resKelas->assertStatus(200);
        $rowsKelas = collect($resKelas->viewData('rows'))->pluck('nama_lengkap')->all();
        $this->assertContains('Santri Abu Bakar', $rowsKelas);

        // 3. Filter gender fleksibel ('L' mencocokkan 'Laki-laki', 'P' mencocokkan 'Perempuan')
        $s2 = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Fathimah Banat',
            'kamar'         => 'Kamar Banat 1',
            'jenis_kelamin' => 'Perempuan',
            'kelas'         => '8B',
        ]);

        $resGenderL = $this->actingAs($this->admin)->get(route('rekap.pondok.index', ['gender' => 'L']));
        $rowsL = collect($resGenderL->viewData('rows'))->pluck('nama_lengkap')->all();
        $this->assertContains('Santri Abu Bakar', $rowsL);
        $this->assertNotContains('Fathimah Banat', $rowsL);

        $resGenderP = $this->actingAs($this->admin)->get(route('rekap.pondok.index', ['gender' => 'P']));
        $rowsP = collect($resGenderP->viewData('rows'))->pluck('nama_lengkap')->all();
        $this->assertContains('Fathimah Banat', $rowsP);
        $this->assertNotContains('Santri Abu Bakar', $rowsP);

        // 4. Fallback toleran jika kombinasi filter menghasilkan 0 santri karena kolom santri NULL
        // Misal santri santri baru kamar "Kamar Ali" belum punya kelas (kelas = null)
        $s3 = $this->createSantriWithPendaftaran([
            'nama_lengkap'  => 'Ali Santri Baru',
            'kamar'         => 'Kamar Ali',
            'kelas'         => null,
            'jenis_kelamin' => 'Laki-laki',
        ]);

        // Filter kamar "Kamar Ali" dan kelas "9A" -> karena kelasnya null, fallback memastikan santri tetap muncul
        $resCombo = $this->actingAs($this->admin)->get(route('rekap.pondok.index', [
            'kamar' => 'Kamar Ali',
            'kelas' => '9A',
        ]));
        $resCombo->assertStatus(200);
        $rowsCombo = collect($resCombo->viewData('rows'))->pluck('nama_lengkap')->all();
        $this->assertContains('Ali Santri Baru', $rowsCombo);
    }
}
