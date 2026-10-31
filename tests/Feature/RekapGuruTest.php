<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapGuruTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Lembaga $lembaga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lembaga = Lembaga::firstOrCreate(
            ['nama' => 'SMP Al Muttaqin'],
            ['kode' => 'SMP', 'singkatan' => 'SMP', 'kategori' => 'sekolah']
        );

        $this->admin = User::factory()->create([
            'email'              => 'admin.guru.test@almuttaqin.id',
            'role'               => 'yayasan',
            'is_active'          => true,
            'primary_lembaga_id' => $this->lembaga->id,
        ]);
    }

    public function test_halaman_rekap_presensi_guru_dapat_diakses_dan_menampilkan_header_harian(): void
    {
        $today = Carbon::today();

        // Buat guru dan data presensi hari ini
        $guru = User::factory()->create([
            'name'               => 'Adi Kara Prabowo, S. Pd., Gr.',
            'primary_lembaga_id' => $this->lembaga->id,
        ]);

        PresensiGuru::create([
            'user_id'    => $guru->id,
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:10:10',
            'status'     => 'hadir',
        ]);

        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'tanggal' => $today->toDateString(),
        ]));

        $response->assertStatus(200);

        // Header presensi harian sesuai Gambar 3
        $response->assertSee('HEADER PRESENSI HARIAN');
        $response->assertSee('Tepat Waktu');
        $response->assertSee('Total Guru');

        // Kolom tabel sesuai Gambar 3
        $response->assertSee('NAMA GURU');
        $response->assertSee('JAM MASUK');
        $response->assertSee('JAM PULANG');
        $response->assertSee('STATUS KEHADIRAN');
        $response->assertSee('AKSI');

        // Data guru
        $response->assertSee('Adi Kara Prabowo, S. Pd., Gr.');
        $response->assertSee('07:10:10 WIB');
        $response->assertSee('Tidak Absen Pulang');
    }
}
