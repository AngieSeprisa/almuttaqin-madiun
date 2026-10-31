<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Database\Seeders\MasterDeviceAndSesiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PengaturanSesiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->seed(MasterDeviceAndSesiSeeder::class);
    }

    public function test_default_daftar_perangkat_kosong_tanpa_alat_dummy(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        Siswa::create([
            'nama_lengkap' => 'Ahmad Santri Reguler',
            'kamar' => 'Reguler',
            'status' => 'aktif',
        ]);

        $this->assertSame(0, DB::table('master_devices')->count());

        $response = $this->actingAs($yayasan)->get(route('pengaturan.sesi'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sesi &amp; Perangkat Presensi', false);
        $response->assertSee('REGULER_HARIAN');
        $response->assertSee('Presensi Kamar Reguler (24 Jam)');
        $response->assertSee('Semua Perangkat');
        $response->assertDontSee('ESP32_DAPUR');
        $response->assertSee(route('dashboard-presensi'));
        $response->assertSee(route('pengaturan.sesi.create'));

        // Akses halaman penuh tambah sesi
        $createResponse = $this->actingAs($yayasan)->get(route('pengaturan.sesi.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Semua Perangkat / Global (Belum ada alat terdaftar)');
        $createResponse->assertSee('Tambah Sesi Presensi Baru');
        $createResponse->assertSee('Pengaturan Sesi');
        $createResponse->assertSee('Tambah Sesi');
        $createResponse->assertSee('Kembali ke Daftar Sesi');
        $createResponse->assertSee('btn btn-secondary');
        $createResponse->assertSee('Informasi Dasar &amp; Rentang Waktu', false);
        $createResponse->assertSee('Alokasi Perangkat Mesin ESP32', false);
        $createResponse->assertSee('Target Peserta &amp; Hak Akses Presensi', false);
    }

    public function test_dapat_menambahkan_sesi_dengan_serial_hardware_esp32(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        DB::table('master_devices')->insert([
            'device_id' => 'ESP32-A4CF1298B3E0',
            'nama_lokasi' => 'Masjid Pondok Utama',
            'kategori_area' => 'pondok',
            'status_aktif' => 1,
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($yayasan)->post(route('pengaturan.sesi.store'), [
            'kode_sesi' => 'tahfidz sore',
            'nama_sesi' => 'Mengaji Tahfidz Sore',
            'jam_mulai' => '16:00',
            'jam_selesai' => '17:15',
            'toleransi_menit' => 10,
            'device_id_target' => 'ESP32-A4CF1298B3E0',
            'kategori' => 'pengajian',
            'target_kategori' => 'kamar_khusus',
            'target_filter' => 'Reguler',
        ]);

        $response->assertRedirect(route('pengaturan.sesi'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi' => 'TAHFIDZ_SORE',
            'nama_sesi' => 'Mengaji Tahfidz Sore',
            'jam_mulai' => '16:00:00',
            'jam_selesai' => '17:15:00',
            'toleransi_menit' => 10,
            'device_id_target' => 'ESP32-A4CF1298B3E0',
            'kategori' => 'pengajian',
            'target_kategori' => 'kamar_khusus',
            'target_filter' => 'Reguler',
        ]);
    }

    public function test_dapat_memperbarui_dan_menghapus_sesi_presensi(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $sesi = DB::table('master_sesis')->where('kode_sesi', 'SUBUH')->first();
        $this->assertNotNull($sesi);

        $updateResponse = $this->actingAs($yayasan)->put(route('pengaturan.sesi.update', $sesi->id), [
            'kode_sesi' => 'SUBUH',
            'nama_sesi' => 'Sholat Subuh & Kultum Pagi',
            'jam_mulai' => '04:10',
            'jam_selesai' => '05:45',
            'toleransi_menit' => 20,
            'device_id_target' => '',
            'kategori' => 'sholat',
            'target_kategori' => 'semua_santri',
            'target_filter' => 'AbaikanFilterIni',
        ]);

        $updateResponse->assertRedirect(route('pengaturan.sesi'));
        $this->assertDatabaseHas('master_sesis', [
            'id' => $sesi->id,
            'nama_sesi' => 'Sholat Subuh & Kultum Pagi',
            'jam_mulai' => '04:10:00',
            'jam_selesai' => '05:45:00',
            'toleransi_menit' => 20,
            'device_id_target' => null,
            'target_kategori' => 'semua_santri',
            'target_filter' => null,
        ]);

        $deleteResponse = $this->actingAs($yayasan)->delete(route('pengaturan.sesi.destroy', $sesi->id));
        $deleteResponse->assertRedirect(route('pengaturan.sesi'));

        $this->assertDatabaseMissing('master_sesis', [
            'id' => $sesi->id,
        ]);
    }

    public function test_kelola_serial_perangkat_edit_lokasi_dan_hapus_aman(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // 1. Daftar serial unik ESP32
        $storeResp = $this->actingAs($yayasan)->post(route('pengaturan.sesi.device.store'), [
            'device_id' => 'ESP32-7C9EBD4412FA',
            'nama_lokasi' => 'Alat Baru (Belum Diberi Lokasi)',
            'kategori_area' => 'pondok',
        ]);

        $storeResp->assertRedirect(route('pengaturan.sesi', ['tab' => 'devices']));
        $device = DB::table('master_devices')->where('device_id', 'ESP32-7C9EBD4412FA')->first();
        $this->assertNotNull($device);

        // Hubungkan 1 sesi ke alat ini
        DB::table('master_sesis')->where('kode_sesi', 'REGULER_HARIAN')->update([
            'device_id_target' => 'ESP32-7C9EBD4412FA',
        ]);

        // 2. Ubah nama lokasi alat
        $editResp = $this->actingAs($yayasan)->put(route('pengaturan.sesi.device.update', $device->id), [
            'nama_lokasi' => 'Pintu Masuk Kamar Reguler',
            'kategori_area' => 'pondok',
        ]);
        $editResp->assertRedirect(route('pengaturan.sesi', ['tab' => 'devices']));
        $this->assertDatabaseHas('master_devices', [
            'id' => $device->id,
            'nama_lokasi' => 'Pintu Masuk Kamar Reguler',
        ]);

        // 3. Cek endpoint live deteksi perangkat
        DB::table('master_devices')->where('id', $device->id)->update(['last_seen_at' => now()]);
        $detectResp = $this->actingAs($yayasan)->getJson(route('pengaturan.sesi.last-device'));
        $detectResp->assertOk()->assertJsonFragment([
            'device_id' => 'ESP32-7C9EBD4412FA',
            'nama_lokasi' => 'Pintu Masuk Kamar Reguler',
        ]);

        // 4. Hapus perangkat — sesi terkait harus otomatis kembali ke Global (NULL) tanpa error
        $delResp = $this->actingAs($yayasan)->delete(route('pengaturan.sesi.device.destroy', $device->id));
        $delResp->assertRedirect(route('pengaturan.sesi', ['tab' => 'devices']));

        $this->assertDatabaseMissing('master_devices', ['id' => $device->id]);
        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi' => 'REGULER_HARIAN',
            'device_id_target' => null,
        ]);
    }

    public function test_halaman_dashboard_nfc_tap_dan_sidebar_tampil_dengan_tema_baru(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        Siswa::create([
            'nama_lengkap' => 'Zaidan Santri Reguler',
            'kamar' => 'Reguler',
            'jenjang' => 'SMP',
            'kelas' => '7A',
            'rfid_uid' => 'A1B2C3D4',
            'status' => 'aktif',
        ]);

        // 1. Cek halaman Dashboard View NFC Tap (/dashboard-presensi)
        $dashResp = $this->actingAs($yayasan)->get(route('dashboard-presensi', ['tab' => 'sekolah']));
        $dashResp->assertStatus(200);
        $dashResp->assertSee('app-sidebar', false);
        $dashResp->assertSee('Pondok / Asrama');
        $dashResp->assertSee('Sekolah (SMP/SMA)');
        $dashResp->assertSee('Pegawai &amp; Guru', false);
        $dashResp->assertSee('Jadwal Sesi');
        $dashResp->assertSee('Perangkat ESP32');
        $dashResp->assertSee('#0052FF', false);

        // 2. Cek endpoint JSON real-time (/dashboard-presensi/data) untuk 3 tab
        foreach (['pondok', 'sekolah', 'pegawai'] as $tab) {
            $jsonResp = $this->actingAs($yayasan)->getJson(route('dashboard-presensi.data', ['tab' => $tab]));
            $jsonResp->assertOk()
                ->assertJsonStructure([
                    'currentTime',
                    'currentDate',
                    'sesiAktif',
                    'summary' => ['totalTarget', 'sudahHadir', 'belumHadir', 'persentase'],
                    'liveFeeds',
                    'waitingList',
                    'hadirList',
                ]);
        }

        // Pastikan tab sekolah memiliki field terpetakan nama, sub, is_hadir
        $sekolahData = $this->actingAs($yayasan)
            ->getJson(route('dashboard-presensi.data', ['tab' => 'sekolah']))
            ->json('waitingList.0');

        $this->assertSame('Zaidan Santri Reguler', $sekolahData['nama']);
        $this->assertStringContainsString('SMP', $sekolahData['sub']);
        $this->assertFalse($sekolahData['is_hadir']);
    }

    public function test_mendukung_target_filter_multiple_kamar_dan_jenjang(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // 1. Store dengan payload array untuk multiple kamar
        $responseArray = $this->actingAs($yayasan)->post(route('pengaturan.sesi.store'), [
            'kode_sesi' => 'sesi_multi_kamar',
            'nama_sesi' => 'Sesi Multi Kamar Putri',
            'jam_mulai' => '19:00',
            'jam_selesai' => '20:30',
            'toleransi_menit' => 15,
            'kategori' => 'pengajian',
            'target_kategori' => 'kamar_khusus',
            'target_filter' => ['Kelas 8 Putri', 'Kelas 9 Putri'],
        ]);

        $responseArray->assertRedirect(route('pengaturan.sesi'));
        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi' => 'SESI_MULTI_KAMAR',
            'target_filter' => 'Kelas 8 Putri, Kelas 9 Putri',
        ]);

        // 2. Update dengan string comma-separated
        $sesi = DB::table('master_sesis')->where('kode_sesi', 'SESI_MULTI_KAMAR')->first();
        $responseString = $this->actingAs($yayasan)->put(route('pengaturan.sesi.update', $sesi->id), [
            'kode_sesi' => 'SESI_MULTI_KAMAR',
            'nama_sesi' => 'Sesi Multi Kamar Putri & Putra',
            'jam_mulai' => '19:00',
            'jam_selesai' => '20:30',
            'toleransi_menit' => 15,
            'kategori' => 'pengajian',
            'target_kategori' => 'kamar_khusus',
            'target_filter' => 'Kelas 8 Putri, Kelas 9 Putri, Kelas 8 Putra',
        ]);

        $responseString->assertRedirect(route('pengaturan.sesi'));
        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi' => 'SESI_MULTI_KAMAR',
            'target_filter' => 'Kelas 8 Putri, Kelas 9 Putri, Kelas 8 Putra',
        ]);

        // 3. Store dengan multiple jenjang sekolah
        $responseSekolah = $this->actingAs($yayasan)->post(route('pengaturan.sesi.store'), [
            'kode_sesi' => 'apel_gabungan_smp_sma',
            'nama_sesi' => 'Apel Gabungan SMP & SMA',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:30',
            'toleransi_menit' => 5,
            'kategori' => 'sekolah',
            'target_kategori' => 'sekolah',
            'target_filter' => ['SMP', 'SMA'],
        ]);

        $responseSekolah->assertRedirect(route('pengaturan.sesi'));
        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi' => 'APEL_GABUNGAN_SMP_SMA',
            'target_filter' => 'SMP, SMA',
        ]);
    }

    public function test_daftar_nama_kamar_aktif_menghapus_duplikasi_kamar(): void
    {
        // Masukkan data siswa dengan nama kamar ganda dan varian "Kamar Kelas 9 Putri" vs "Kelas 9 Putri"
        Siswa::create([
            'nama_lengkap' => 'Santri 1',
            'kamar' => 'Kelas 9 Putri',
            'status' => 'aktif',
        ]);
        Siswa::create([
            'nama_lengkap' => 'Santri 2',
            'kamar' => 'Kamar Kelas 9 Putri',
            'status' => 'aktif',
        ]);
        Siswa::create([
            'nama_lengkap' => 'Santri 3',
            'kamar' => 'Kamar Kamar Kelas 8 Putra',
            'status' => 'aktif',
        ]);

        $kamars = \App\Models\Kamar::daftarNamaKamarAktif();

        // Pastikan "Kelas 9 Putri" digabungkan sehingga hanya muncul 1 kali sebagai "Kamar Kelas 9 Putri"
        $kelas9PutriCount = $kamars->filter(fn ($k) => stripos($k, 'Kelas 9 Putri') !== false)->count();
        $this->assertSame(1, $kelas9PutriCount);
        $this->assertTrue($kamars->contains('Kamar Kelas 9 Putri'));

        // Pastikan duplikasi kata "Kamar Kamar" dibersihkan menjadi "Kamar Kelas 8 Putra"
        $this->assertTrue($kamars->contains('Kamar Kelas 8 Putra'));
        $this->assertFalse($kamars->contains('Kamar Kamar Kelas 8 Putra'));
    }

    public function test_halaman_penuh_edit_sesi_dapat_diakses(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $sesi = DB::table('master_sesis')->where('kode_sesi', 'SUBUH')->firstOrFail();

        $response = $this->actingAs($yayasan)->get(route('pengaturan.sesi.edit', $sesi->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Sesi Presensi');
        $response->assertSee('Informasi Dasar &amp; Rentang Waktu', false);
        $response->assertSee($sesi->nama_sesi);
        $response->assertSee('Simpan Perubahan Sesi');
    }

    public function test_dapat_menambahkan_sesi_target_kelas_pengajian_dan_gender(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('pengaturan.sesi.store'), [
            'kode_sesi' => 'pengajian_malam_putra',
            'nama_sesi' => 'Pengajian Kitab Malam Khusus Putra',
            'jam_mulai' => '19:30',
            'jam_selesai' => '21:00',
            'toleransi_menit' => 15,
            'kategori' => 'pengajian',
            'target_kategori' => 'kelas_pengajian',
            'target_filter_kelas' => ['Kelas Lambatan SMP', 'Kelas Cepatan SMP'],
            'target_filter_gender' => 'putra',
        ]);

        $response->assertRedirect(route('pengaturan.sesi'));
        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi' => 'PENGAJIAN_MALAM_PUTRA',
            'nama_sesi' => 'Pengajian Kitab Malam Khusus Putra',
            'target_kategori' => 'kelas_pengajian',
            'target_filter' => 'Kelas Lambatan SMP, Kelas Cepatan SMP [Gender: Putra]',
        ]);
    }

    public function test_dapat_menyimpan_dan_mengubah_alokasi_multi_perangkat_dengan_target_masing_masing(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // Siapkan 2 device
        DB::table('master_devices')->insert([
            [
                'device_id' => 'tap_masjid',
                'nama_lokasi' => 'Pintu Putra Masjid',
                'kategori_area' => 'pondok',
                'status_aktif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'device_id' => 'ESP32_Presensi_Santri',
                'nama_lokasi' => 'Pintu Putri Masjid',
                'kategori_area' => 'pondok',
                'status_aktif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Simpan sesi tunggal dengan 2 alokasi mesin (Dzuhur Putra & Putri dalam 1 sesi)
        $allocations = [
            [
                'device_id' => 'tap_masjid',
                'target_kategori' => 'gender',
                'target_gender' => 'L',
                'target_filter' => null,
            ],
            [
                'device_id' => 'ESP32_Presensi_Santri',
                'target_kategori' => 'gender',
                'target_gender' => 'P',
                'target_filter' => null,
            ],
        ];

        $storeResponse = $this->actingAs($yayasan)->post(route('pengaturan.sesi.store'), [
            'kode_sesi' => 'DZUHUR_BERSAMA',
            'nama_sesi' => 'Sholat Dzuhur Berjamaah',
            'jam_mulai' => '11:45',
            'jam_selesai' => '12:30',
            'toleransi_menit' => 10,
            'kategori' => 'sholat',
            'allocations_json' => json_encode($allocations),
        ]);

        $storeResponse->assertRedirect(route('pengaturan.sesi'));
        $storeResponse->assertSessionHas('success');

        $sesi = DB::table('master_sesis')->where('kode_sesi', 'DZUHUR_BERSAMA')->first();
        $this->assertNotNull($sesi);

        // Pastikan tabel sesi_device_allocations terisi 2 baris
        $this->assertDatabaseHas('sesi_device_allocations', [
            'master_sesi_id' => $sesi->id,
            'device_id' => 'tap_masjid',
            'target_kategori' => 'gender',
            'target_gender' => 'L',
        ]);
        $this->assertDatabaseHas('sesi_device_allocations', [
            'master_sesi_id' => $sesi->id,
            'device_id' => 'ESP32_Presensi_Santri',
            'target_kategori' => 'gender',
            'target_gender' => 'P',
        ]);

        // Periksa tampilan halaman index tabel sesi memiliki badge perangkat & targetnya
        $indexResponse = $this->actingAs($yayasan)->get(route('pengaturan.sesi'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('tap_masjid');
        $indexResponse->assertSee('[Putra]');
        $indexResponse->assertSee('ESP32_Presensi_Santri');
        $indexResponse->assertSee('[Putri]');

        // Update alokasi: ganti target mesin putri ke kamar khusus
        $updatedAllocations = [
            [
                'device_id' => 'tap_masjid',
                'target_kategori' => 'gender',
                'target_gender' => 'L',
                'target_filter' => null,
            ],
            [
                'device_id' => 'ESP32_Presensi_Santri',
                'target_kategori' => 'kamar',
                'target_gender' => 'P',
                'target_filter' => 'Kamar Kelas 7 Putri, Kamar Kelas 8 Putri',
            ],
        ];

        $updateResponse = $this->actingAs($yayasan)->put(route('pengaturan.sesi.update', $sesi->id), [
            'kode_sesi' => 'DZUHUR_BERSAMA',
            'nama_sesi' => 'Sholat Dzuhur Berjamaah Santri',
            'jam_mulai' => '11:45',
            'jam_selesai' => '12:30',
            'toleransi_menit' => 10,
            'kategori' => 'sholat',
            'allocations_json' => json_encode($updatedAllocations),
        ]);

        $updateResponse->assertRedirect(route('pengaturan.sesi'));

        $this->assertDatabaseHas('sesi_device_allocations', [
            'master_sesi_id' => $sesi->id,
            'device_id' => 'ESP32_Presensi_Santri',
            'target_kategori' => 'kamar',
            'target_gender' => 'P',
            'target_filter' => 'Kamar Kelas 7 Putri, Kamar Kelas 8 Putri',
        ]);
    }
}
