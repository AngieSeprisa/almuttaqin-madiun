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

    public function test_halaman_pengaturan_sesi_dapat_diakses_dan_menampilkan_data(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        Siswa::create([
            'nama_lengkap' => 'Ahmad Santri Reguler',
            'kamar' => 'Reguler',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($yayasan)->get(route('pengaturan.sesi'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sesi & Perangkat Presensi');
        $response->assertSee('REGULER_HARIAN');
        $response->assertSee('Presensi Kamar Reguler (24 Jam)');
        $response->assertSee('ESP32_Presensi_Santri');
        $response->assertSee(route('dashboard-presensi'));
    }

    public function test_dapat_menambahkan_sesi_presensi_baru(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('pengaturan.sesi.store'), [
            'kode_sesi' => 'tahfidz sore',
            'nama_sesi' => 'Mengaji Tahfidz Sore',
            'jam_mulai' => '16:00',
            'jam_selesai' => '17:15',
            'toleransi_menit' => 10,
            'device_id_target' => 'ESP32_MASJID',
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
            'device_id_target' => 'ESP32_MASJID',
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
            'device_id_target' => 'ESP32_MASJID',
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
            'target_kategori' => 'semua_santri',
            'target_filter' => null,
        ]);

        $deleteResponse = $this->actingAs($yayasan)->delete(route('pengaturan.sesi.destroy', $sesi->id));
        $deleteResponse->assertRedirect(route('pengaturan.sesi'));

        $this->assertDatabaseMissing('master_sesis', [
            'id' => $sesi->id,
        ]);
    }

    public function test_dapat_mengelola_perangkat_esp32(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $storeResp = $this->actingAs($yayasan)->post(route('pengaturan.sesi.device.store'), [
            'device_id' => 'ESP32 GERBANG UTAMA',
            'nama_lokasi' => 'Pos Keamanan Gerbang Depan',
            'kategori_area' => 'umum',
        ]);

        $storeResp->assertRedirect(route('pengaturan.sesi', ['tab' => 'devices']));
        $this->assertDatabaseHas('master_devices', [
            'device_id' => 'ESP32_GERBANG_UTAMA',
            'nama_lokasi' => 'Pos Keamanan Gerbang Depan',
            'kategori_area' => 'umum',
            'status_aktif' => 1,
        ]);

        $device = DB::table('master_devices')->where('device_id', 'ESP32_GERBANG_UTAMA')->first();

        $toggleResp = $this->actingAs($yayasan)->patch(route('pengaturan.sesi.device.toggle', $device->id));
        $toggleResp->assertRedirect(route('pengaturan.sesi', ['tab' => 'devices']));
        $this->assertDatabaseHas('master_devices', [
            'id' => $device->id,
            'status_aktif' => 0,
        ]);
    }
}
