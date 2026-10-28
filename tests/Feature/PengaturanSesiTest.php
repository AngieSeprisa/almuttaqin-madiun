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
        $response->assertSee('Semua Perangkat / Global (Belum ada alat terdaftar)');
        $response->assertDontSee('ESP32_DAPUR');
        $response->assertSee(route('dashboard-presensi'));
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
}
