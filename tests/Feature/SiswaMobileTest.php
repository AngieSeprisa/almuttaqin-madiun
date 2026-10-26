<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaMobileTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->first();
    }

    /**
     * Test tampilan mobile card list dirender dengan benar sesuai desain screenshot.
     */
    public function test_tampilan_mobile_card_siswa_dirender_lengkap(): void
    {
        $pendaftaran = Pendaftaran::create([
            'nama_lengkap' => 'Aprilia Khoirunnisa',
            'jenjang' => 'Reguler',
            'no_induk' => 'PSB-2026-0001',
            'nik' => '3519010101080001',
            'tempat_lahir' => 'Madiun',
            'tanggal_lahir' => '2008-01-01',
            'jenis_kelamin' => 'Perempuan',
            'alamat_jalan' => 'Jl. Merak',
            'ayah_nama' => 'Ayah',
            'ibu_nama' => 'Ibu',
            'pj_biaya_hp' => '08123456789',
            'status_pendaftaran' => 'Diterima',
        ]);

        $siswa = Siswa::create([
            'nama_lengkap' => 'Aprilia Khoirunnisa',
            'jenjang' => 'Reguler',
            'kamar' => 'SMP',
            'status' => 'aktif',
            'pendaftaran_id' => $pendaftaran->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('siswa'));

        $response->assertStatus(200);

        // Memastikan elemen mobile card list dan atribut sesuai screenshot ada
        $response->assertSee('mobile-siswa-list');
        $response->assertSee('mobile-siswa-card');
        $response->assertSee('Aprilia Khoirunnisa');
        $response->assertSee('Jenjang');
        $response->assertSee('Kamar');
        $response->assertSee('Reguler');
        $response->assertSee('SMP');
        $response->assertSee('Aktif');
        $response->assertSee('Aksi');
        $response->assertSee('btn-view');
        $response->assertSee('btn-edit');
        $response->assertSee('btn-delete');
        $response->assertSee('placeholder="Cari nama santri/kamar..."', false);
    }
}
