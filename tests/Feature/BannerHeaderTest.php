<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_beranda_keeps_original_header_and_does_not_have_restructured_page_header(): void
    {
        $response = $this->actingAs($this->admin)->get(route('beranda'));
        $response->assertStatus(200);

        // Top navbar is active on Beranda (matching Image 2)
        $response->assertSee('topbarSearchInput', false);
        $response->assertSee('topbar-avatar-circle', false);

        // Beranda does not have the nested Page Header structure in main content
        $response->assertDontSee('page-header');
        $response->assertDontSee('page-header-breadcrumbs');
    }

    public function test_page_header_structure_on_siswa_with_action_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('siswa'));
        $response->assertStatus(200);

        // Top navbar is active
        $response->assertSee('topbarSearchInput', false);

        // Page header container exists (no floating card)
        $response->assertSee('page-header', false);
        $response->assertSee('page-header-breadcrumbs text-sm text-gray-500', false);

        // Breadcrumb: Siswa > Daftar
        $response->assertSee('Siswa', false);
        $response->assertSee('Daftar', false);
        $response->assertSee('page-breadcrumb-separator', false);

        // Bold title
        $response->assertSee('page-header-title text-2xl font-bold text-gray-900', false);

        // Action button placed on the right
        $response->assertSee('page-header-actions', false);
        $response->assertSee('Tambah Siswa', false);
    }

    public function test_page_header_structure_on_pendaftaran(): void
    {
        $response = $this->actingAs($this->admin)->get(route('pendaftaran.index'));
        $response->assertStatus(200);

        $response->assertSee('page-header', false);
        $response->assertSee('Pendaftaran', false);
        $response->assertSee('Daftar', false);
        $response->assertSee('page-header-actions', false);
        $response->assertSee('Formulir Pendaftaran Baru', false);
    }

    public function test_page_header_structure_on_pengguna(): void
    {
        $response = $this->actingAs($this->admin)->get(route('pengguna.index'));
        $response->assertStatus(200);

        $response->assertSee('page-header', false);
        $response->assertSee('Pengguna', false);
        $response->assertSee('Hak Akses', false);
        $response->assertSee('page-header-actions', false);
        $response->assertSee('Tambah Pengguna', false);
    }

    public function test_page_header_structure_on_sekolah_jurnal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('sekolah.jurnal'));
        $response->assertStatus(200);

        $response->assertSee('page-header', false);
        $response->assertSee('Sekolah', false);
        $response->assertSee('Jurnal', false);
        $response->assertSee('page-header-actions', false);
        $response->assertSee('Rekap Jurnal', false);
    }

    public function test_page_header_structure_on_profile(): void
    {
        $response = $this->actingAs($this->admin)->get(route('profile'));
        $response->assertStatus(200);

        $response->assertSee('page-header', false);
        $response->assertSee('Akun', false);
        $response->assertSee('Profil Pengguna', false);
        $response->assertSee('page-header-actions', false);
        $response->assertSee('Keamanan &amp; Password', false);
    }

    public function test_page_header_structure_on_presensi_and_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('presensi.index'));
        $response->assertStatus(200);
        $response->assertSee('page-header', false);
        $response->assertSee('Presensi Guru', false);

        $response2 = $this->actingAs($this->admin)->get(route('guru-sekolah.index'));
        $response2->assertStatus(200);
        $response2->assertSee('page-header', false);
        $response2->assertSee('Pengaturan Sekolah', false);
        $response2->assertSee('Guru Sekolah', false);

        $response3 = $this->actingAs($this->admin)->get(route('plotting-kelas.index'));
        $response3->assertStatus(200);
        $response3->assertSee('page-header', false);
        $response3->assertSee('Pengaturan Sekolah', false);
        $response3->assertSee('Plotting Kelas', false);
    }

    public function test_page_header_component_direct_rendering(): void
    {
        $view = $this->blade(
            '<x-page-header title="Judul Khusus" :breadcrumbs="[
                [\'label\' => \'Modul Utama\', \'url\' => \'/utama\'],
                [\'label\' => \'Sub Halaman\']
            ]">
                <x-slot:actions>
                    <button class="btn btn-primary">Aksi Tombol</button>
                </x-slot:actions>
            </x-page-header>'
        );

        $view->assertSee('Judul Khusus');
        $view->assertSee('Modul Utama');
        $view->assertSee('/utama');
        $view->assertSee('Sub Halaman');
        $view->assertSee('Aksi Tombol');
        $view->assertSee('page-header-breadcrumbs text-sm text-gray-500');
        $view->assertSee('page-header-title text-2xl font-bold text-gray-900');
    }
}

