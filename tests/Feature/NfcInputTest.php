<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NfcInputTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_halaman_input_nfc_dapat_diakses_dengan_tampilan_filament_bersih(): void
    {
        $response = $this->actingAs($this->admin)->get(route('nfc.index'));

        $response->assertStatus(200);
        $response->assertSee('Input NFC');
        $response->assertSee('Nomor Serial Kartu (NFC)');
        $response->assertSee('Pilih Nama Santri');
        $response->assertSee('Simpan Kartu');
        $response->assertSee('Riwayat Input Terkini');
        // Pastikan tidak ada tab-tab internal
        $response->assertDontSee('tab=devices');
        $response->assertDontSee('tab=jadwal');
    }

    public function test_menu_input_nfc_muncul_di_sidebar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('beranda'));

        $response->assertStatus(200);
        $response->assertSee(route('nfc.index'));
        $response->assertSee('Input NFC');
    }

    public function test_check_card_endpoint_menangani_kartu_baru_dan_terdaftar(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri Kartu Eksis',
            'jenjang' => 'SMP',
            'kamar' => 'Kamar A1',
            'status' => 'aktif',
            'rfid_uid' => '0012345678',
        ]);

        // 1. Cek kartu yang belum terdaftar
        $respBaru = $this->actingAs($this->admin)->get(route('nfc.check-card', ['serial' => '0099999999']));
        $respBaru->assertStatus(200);
        $respBaru->assertJson([
            'status' => 'available',
            'serial' => '0099999999',
        ]);

        // 2. Cek kartu yang sudah terdaftar
        $respEksis = $this->actingAs($this->admin)->get(route('nfc.check-card', ['serial' => '0012345678']));
        $respEksis->assertStatus(200);
        $respEksis->assertJson([
            'status' => 'assigned',
            'serial' => '0012345678',
            'siswa' => [
                'id' => $siswa->id,
                'nama_lengkap' => 'Santri Kartu Eksis',
            ],
        ]);
    }

    public function test_dapat_memasangkan_kartu_nfc_ke_santri(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri Belum Berkartu',
            'jenjang' => 'SMP',
            'kamar' => 'Kamar B2',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('nfc.assign'), [
            'rfid_uid' => '0088776655',
            'siswa_id' => $siswa->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $siswa->refresh();
        $this->assertEquals('0088776655', $siswa->rfid_uid);
    }

    public function test_memasangkan_kartu_yang_sudah_dipakai_otomatis_mengalihkan_dari_pemilik_lama(): void
    {
        $siswaLama = Siswa::create([
            'nama_lengkap' => 'Santri Pemilik Lama',
            'jenjang' => 'SMP',
            'kamar' => 'Kamar A1',
            'status' => 'aktif',
            'rfid_uid' => '0055443322',
        ]);

        $siswaBaru = Siswa::create([
            'nama_lengkap' => 'Santri Pemilik Baru',
            'jenjang' => 'SMA',
            'kamar' => 'Kamar Reguler',
            'status' => 'aktif',
        ]);

        // Pasangkan nomor 0055443322 ke siswaBaru
        $response = $this->actingAs($this->admin)->postJson(route('nfc.assign'), [
            'rfid_uid' => '0055443322',
            'siswa_id' => $siswaBaru->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $siswaLama->refresh();
        $siswaBaru->refresh();

        $this->assertNull($siswaLama->rfid_uid);
        $this->assertEquals('0055443322', $siswaBaru->rfid_uid);
    }

    public function test_dapat_melepas_kartu_nfc_dari_santri(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri Ingin Lepas Kartu',
            'jenjang' => 'SMP',
            'kamar' => 'Kamar C3',
            'status' => 'aktif',
            'rfid_uid' => '0011223344',
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('nfc.unpair'), [
            'siswa_id' => $siswa->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $siswa->refresh();
        $this->assertNull($siswa->rfid_uid);
    }

    public function test_format_usb_reader_split_dan_interoperabilitas_esp32(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri USB Tap',
            'jenjang' => 'SMP',
            'kamar' => 'Kamar D4',
            'status' => 'aktif',
        ]);

        // 1. Simpan menggunakan format output USB reader: ;42992,47057
        $response = $this->actingAs($this->admin)->postJson(route('nfc.assign'), [
            'rfid_uid' => ';42992,47057',
            'siswa_id' => $siswa->id,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $siswa->refresh();
        $this->assertEquals(';42992,47057', $siswa->rfid_uid);

        // 2. Cek kartu menggunakan format HEX ESP32 (A7F0B7D1) -> harus terdeteksi milik santri yang sama
        $respHex = $this->actingAs($this->admin)->get(route('nfc.check-card', ['serial' => 'A7F0B7D1']));
        $respHex->assertStatus(200);
        $respHex->assertJson([
            'status' => 'assigned',
            'siswa' => [
                'id' => $siswa->id,
                'nama_lengkap' => 'Santri USB Tap',
            ],
        ]);

        // 3. Cek kartu menggunakan format tanpa titik koma (42992,47057)
        $respNoSemi = $this->actingAs($this->admin)->get(route('nfc.check-card', ['serial' => '42992,47057']));
        $respNoSemi->assertStatus(200);
        $respNoSemi->assertJson([
            'status' => 'assigned',
            'siswa' => [
                'id' => $siswa->id,
            ],
        ]);

        // 4. Santri kedua mendaftar dengan format HEX A7F0B7D1 -> kartu santri pertama otomatis dialihkan
        $siswa2 = Siswa::create([
            'nama_lengkap' => 'Santri Kedua',
            'jenjang' => 'SMA',
            'status' => 'aktif',
        ]);

        $response2 = $this->actingAs($this->admin)->postJson(route('nfc.assign'), [
            'rfid_uid' => 'A7F0B7D1',
            'siswa_id' => $siswa2->id,
        ]);
        $response2->assertStatus(200);

        $siswa->refresh();
        $siswa2->refresh();
        $this->assertNull($siswa->rfid_uid);
        $this->assertEquals('A7F0B7D1', $siswa2->rfid_uid);
    }
}

