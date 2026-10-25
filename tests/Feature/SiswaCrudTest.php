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

    public function test_tombol_tambah_siswa_tidak_ditampilkan_pada_menu_siswa(): void
    {
        $response = $this->actingAs($this->admin)->get(route('siswa'));
        $response->assertStatus(200);
        $response->assertDontSee('Tambah Siswa');
    }

    public function test_klik_tombol_edit_pada_siswa_mengalihkan_ke_formulir_pendaftaran(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri Uji Edit',
            'jenjang'      => 'SMP',
            'kamar'        => 'Kamar A1',
            'status'       => 'aktif',
        ]);

        // Cek halaman siswa memuat link menuju route('siswa.edit', $siswa->id)
        $response = $this->actingAs($this->admin)->get(route('siswa'));
        $response->assertStatus(200);
        $response->assertSee(route('siswa.edit', $siswa->id));

        // Mengakses edit siswa mengalihkan ke halaman formulir pendaftaran edit
        $responseEdit = $this->actingAs($this->admin)->get(route('siswa.edit', $siswa->id));
        $responseEdit->assertRedirect();
        $this->assertStringContainsString('/pendaftaran/', $responseEdit->headers->get('Location'));
        $this->assertStringContainsString('/edit?from=siswa', $responseEdit->headers->get('Location'));

        // Halaman formulir edit pendaftaran dapat dibuka
        $responseForm = $this->actingAs($this->admin)->get($responseEdit->headers->get('Location'));
        $responseForm->assertStatus(200);
        $responseForm->assertSee('Edit Formulir Pendaftaran');
        $responseForm->assertSee('Santri Uji Edit');
        $responseForm->assertSee('Kembali ke Siswa');
    }

    public function test_update_formulir_pendaftaran_dari_menu_siswa_mensinkronkan_siswa(): void
    {
        $siswa = Siswa::create([
            'nama_lengkap' => 'Santri Awal',
            'jenjang'      => 'SMP',
            'kamar'        => 'Kamar C2',
            'status'       => 'aktif',
        ]);

        // Trigger edit untuk inisialisasi / link pendaftaran
        $this->actingAs($this->admin)->get(route('siswa.edit', $siswa->id));
        $siswa->refresh();
        $this->assertNotNull($siswa->pendaftaran_id);

        $pendaftaran = $siswa->pendaftaran;

        $updateData = [
            'from'                => 'siswa',
            'jenjang'             => 'SMA',
            'nik'                 => $pendaftaran->nik,
            'nama_lengkap'        => 'Santri Berubah Nama',
            'nama_panggilan'      => 'Berubah',
            'tempat_lahir'        => 'Kota Madiun',
            'tanggal_lahir'       => '2008-01-01',
            'jenis_kelamin'       => 'Laki-laki',
            'anak_ke'             => 1,
            'jumlah_saudara'      => 2,
            'tinggal_bersama'     => 'Orang Tua Kandung',
            'alamat_jalan'        => 'Jl. Uji No. 1',
            'rt_rw'               => '01/01',
            'desa_kelurahan'      => 'Josenan',
            'kecamatan'           => 'Taman',
            'kab_kota'            => 'Kota Madiun',
            'kode_pos'            => '63134',
            'provinsi'            => 'Jawa Timur',
            'warga_negara'        => 'Indonesia',
            'kelompok'            => 'Bina Syarekah',
            'desa'                => 'Mentawai',
            'daerah'              => 'Madiun Kota',
            'pendidikan_terakhir' => 'SMP/MTs',
            'status_mondok'       => 'Person',
            'pj_biaya_nama'       => 'Orang Tua',
            'pj_biaya_hp'         => '081234567890',
            'ayah_nama'           => 'Bapak Santri',
            'ayah_status'         => 'Hidup',
            'ibu_nama'            => 'Ibu Santri',
            'ibu_status'          => 'Hidup',
        ];

        $response = $this->actingAs($this->admin)->put(route('pendaftaran.update', $pendaftaran->id), $updateData);

        $response->assertRedirect(route('siswa'));
        $this->assertDatabaseHas('pendaftarans', [
            'id'           => $pendaftaran->id,
            'nama_lengkap' => 'Santri Berubah Nama',
            'jenjang'      => 'SMA',
        ]);
        $this->assertDatabaseHas('siswas', [
            'id'           => $siswa->id,
            'nama_lengkap' => 'Santri Berubah Nama',
            'jenjang'      => 'SMA',
        ]);
    }
}
