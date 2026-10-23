<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->first();
    }

    public function test_halaman_siswa_dapat_diakses_dan_menampilkan_kolom_aksi(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Ahmad Fauzi',
            'jenjang'      => 'SMP',
            'kamar'        => 'Kamar A1',
            'status'       => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->get(route('siswa'));

        $response->assertStatus(200);
        $response->assertSee('Ahmad Fauzi');
        $response->assertSee('Kamar A1');
        $response->assertSee('Aksi');
        $response->assertSee('Lihat');
        $response->assertSee('Edit');
        $response->assertSee('Hapus');
    }

    public function test_dapat_menambahkan_siswa_baru(): void
    {
        $data = [
            'nama_lengkap' => 'Muhammad Bilal',
            'jenjang'      => 'SMP',
            'kamar'        => 'Kamar B2',
            'status'       => 'aktif',
        ];

        $response = $this->actingAs($this->admin)->post(route('siswa.store'), $data);

        $response->assertRedirect(route('siswa'));
        $this->assertDatabaseHas('siswas', [
            'nama_lengkap' => 'Muhammad Bilal',
            'kamar'        => 'Kamar B2',
            'status'       => 'aktif',
        ]);
    }

    public function test_dapat_memperbarui_data_siswa(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Farhan Kamil',
            'jenjang'      => 'SMA',
            'kamar'        => 'Reguler 1',
            'status'       => 'aktif',
        ]);

        $updateData = [
            'nama_lengkap' => 'Farhan Kamil Updated',
            'kamar'        => 'Reguler 2',
            'status'       => 'pulang',
        ];

        $response = $this->actingAs($this->admin)->put(route('siswa.update', $siswa->id), $updateData);

        $response->assertRedirect(route('siswa'));
        $this->assertDatabaseHas('siswas', [
            'id'           => $siswa->id,
            'nama_lengkap' => 'Farhan Kamil Updated',
            'kamar'        => 'Reguler 2',
            'status'       => 'pulang',
        ]);
    }

    public function test_dapat_menghapus_data_siswa(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Dani Santoso',
            'jenjang'      => 'SMP',
            'kamar'        => 'Kamar C3',
            'status'       => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('siswa.destroy', $siswa->id));

        $response->assertRedirect(route('siswa'));
        $this->assertDatabaseMissing('siswas', [
            'id' => $siswa->id,
        ]);
    }
}
