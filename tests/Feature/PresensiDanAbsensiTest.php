<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\PengaturanAbsensi;
use App\Models\PresensiGuru;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiDanAbsensiTest extends TestCase
{
    use RefreshDatabase;

    protected User $yayasan;
    protected User $guru;
    protected Lembaga $lembagaSmp;
    protected PengaturanAbsensi $pengaturanSmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);

        $this->yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $this->guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();
        $this->lembagaSmp = Lembaga::where('kode', 'smp')->firstOrFail();

        // Buat pengaturan absensi default untuk SMP
        $this->pengaturanSmp = $this->lembagaSmp->getOrCreatePengaturanAbsensi();
        $this->pengaturanSmp->update([
            'jam_masuk'                  => '07:00:00',
            'toleransi_terlambat_menit'  => 15,
            'jam_pulang'                 => '15:30:00',
            'latitude'                   => -7.327434,
            'longitude'                  => 108.220761,
            'radius_meter'               => 100,
            'qr_secret_key'              => 'test-secret-salt-key',
        ]);
    }

    public function test_halaman_presensi_dapat_diakses_oleh_guru(): void
    {
        $response = $this->actingAs($this->guru)->get(route('presensi.index'));
        $response->assertStatus(200);
        $response->assertSee('Presensi Guru');
        $response->assertSee('Pemindai Kamera');
    }

    public function test_scan_qr_berhasil_saat_token_valid_dan_dalam_radius(): void
    {
        $payload = $this->pengaturanSmp->generateQrPayload();

        $response = $this->actingAs($this->guru)
            ->withSession(['active_lembaga' => 'smp'])
            ->postJson(route('presensi.scan'), [
                'token'     => $payload['token'],
                'latitude'  => -7.327430, // Sangat dekat (~1 meter)
                'longitude' => 108.220760,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action'  => 'masuk',
        ]);

        $this->assertDatabaseHas('presensi_gurus', [
            'user_id'    => $this->guru->id,
            'lembaga_id' => $this->lembagaSmp->id,
        ]);
    }

    public function test_scan_langsung_via_kamera_hp_native_berhasil(): void
    {
        $payload = $this->pengaturanSmp->generateQrPayload();

        $response = $this->actingAs($this->guru)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('presensi.index', ['token' => $payload['token']]));

        $response->assertRedirect(route('presensi.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('presensi_gurus', [
            'user_id'    => $this->guru->id,
            'lembaga_id' => $this->lembagaSmp->id,
        ]);
    }


    public function test_scan_qr_ditolak_jika_di_luar_radius(): void
    {
        $this->pengaturanSmp->update(['mode_lokasi' => 'wajib']);
        $payload = $this->pengaturanSmp->generateQrPayload();

        $response = $this->actingAs($this->guru)
            ->withSession(['active_lembaga' => 'smp'])
            ->postJson(route('presensi.scan'), [
                'token'     => $payload['token'],
                'latitude'  => -7.350000, // Berjarak beberapa kilometer
                'longitude' => 108.250000,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_scan_qr_tetap_berhasil_saat_mode_opsional_meskipun_gps_berbeda(): void
    {
        $this->pengaturanSmp->update(['mode_lokasi' => 'opsional']);
        $payload = $this->pengaturanSmp->generateQrPayload();

        $response = $this->actingAs($this->guru)
            ->withSession(['active_lembaga' => 'smp'])
            ->postJson(route('presensi.scan'), [
                'token'     => $payload['token'],
                'latitude'  => -7.350000, // Jauh tapi mode opsional (misal laptop/sinyal indoor)
                'longitude' => 108.250000,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_guru_dapat_mengajukan_izin(): void
    {
        $response = $this->actingAs($this->guru)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('presensi.izin'), [
                'status'     => 'izin',
                'keterangan' => 'Keperluan keluarga mendesak',
            ]);

        $response->assertRedirect(route('presensi.index'));
        $this->assertDatabaseHas('presensi_gurus', [
            'user_id'    => $this->guru->id,
            'status'     => 'izin',
            'keterangan' => 'Keperluan keluarga mendesak',
        ]);
    }

    public function test_halaman_pengaturan_absensi_dapat_diakses_dan_diupdate(): void
    {
        $response = $this->actingAs($this->yayasan)->get(route('pengaturan-absensi.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Absensi');
        $response->assertSee('Jam Masuk');

        $updateResponse = $this->actingAs($this->yayasan)->put(route('pengaturan-absensi.update'), [
            'lembaga_id'                => $this->lembagaSmp->id,
            'jam_masuk'                 => '06:45',
            'toleransi_terlambat_menit' => 10,
            'jam_pulang'                => '16:00',
            'latitude'                  => -7.327500,
            'longitude'                 => 108.220800,
            'radius_meter'              => 80,
            'mode_lokasi'               => 'opsional',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('pengaturan_absensis', [
            'lembaga_id'   => $this->lembagaSmp->id,
            'jam_masuk'    => '06:45:00',
            'radius_meter' => 80,
            'mode_lokasi'  => 'opsional',
        ]);
    }


    public function test_rekap_absensi_guru_dapat_diakses_dan_diekspor(): void
    {
        $response = $this->actingAs($this->yayasan)->get(route('rekap-absensi.guru'));
        $response->assertStatus(200);
        $response->assertSee('Rekap Absensi Guru');
        $response->assertSee('Hadir Tepat');

        $exportResponse = $this->actingAs($this->yayasan)->get(route('rekap-absensi.export'));
        $exportResponse->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $exportResponse->headers->get('Content-Type'));
    }
}
