<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarUntitledUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_sidebar_untitled_ui_layout_for_yayasan(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->get(route('beranda'));
        $response->assertStatus(200);

        // 1. Bagian atas berisi logo dan nama lembaga (tanpa subtitle)
        $response->assertSee('brand-wrapper');
        $response->assertSee('brand-logo-img');
        $response->assertSee('brand-title');
        $response->assertDontSee('brand-subtitle');

        // 2. Dropdown pindah lembaga ada di sidebar sebagai dropdown biasa untuk akun multi-lembaga (Yayasan)
        $response->assertSee('sidebar-room-area');
        $response->assertSee('sidebar-room-btn');
        $response->assertSee('sidebar-room-dropdown');
        $response->assertDontSee('sidebar-room-icon');
        $response->assertDontSee('sidebar-room-label');
        $response->assertSee('SMP AL MUTTAQIN');
        $response->assertSee('SMA AL MUTTAQIN');
        $response->assertSee('PONDOK PESANTREN AL MUTTAQIN');

        // 3. Dropdown pindah lembaga di topbar kanan telah dihilangkan
        $response->assertDontSee('room-switcher-wrap');

        // 4. Menu profile user berada di pojok kanan atas topbar (userDropdownToggle) dan sidebar-profile-card bawah telah dihilangkan
        $response->assertDontSee('sidebar-profile-card');
        $response->assertSee('userDropdownToggle');
        $response->assertSee(route('profile'));
    }

    public function test_sidebar_untitled_ui_when_switched_to_smp(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('beranda'));

        $response->assertStatus(200);

        // Header menampilkan logo SMP dan nama SMP AL MUTTAQIN tanpa subtitle
        $response->assertSee('logo-smp.png');
        $response->assertSee('SMP AL MUTTAQIN');
        $response->assertDontSee('brand-subtitle');

        // Akun yang hanya punya 1 lembaga TIDAK menampilkan dropdown pindah lembaga sama sekali
        $response->assertDontSee('sidebar-room-area');
        $response->assertDontSee('sidebar-room-btn');

        // Footer menampilkan profile Admin SMP
        $response->assertSee('Admin SMP Al-Muttaqin');
        $response->assertSee('admin.smp@almuttaqin.id');
    }

    public function test_profile_page_loads_with_full_user_details(): void
    {
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($guru)->get(route('profile'));
        $response->assertStatus(200);

        $response->assertSee('Ustadz Syarifuddin, S.Pd.');
        $response->assertSee('guru.pondok.smp@almuttaqin.id');
        $response->assertSee('Guru / Pengajar');
        $response->assertSee('Biodata Diri');
        $response->assertSee('Informasi Kontak');
        $response->assertSee(route('user'));

        // Test security / user password page loads
        $securityResponse = $this->actingAs($guru)->get(route('user'));
        $securityResponse->assertStatus(200);
        $securityResponse->assertSee('Keamanan & Kata Sandi', false);
    }

    public function test_sidebar_pengaturan_menu_has_absensi_submenu(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // 1. Pada halaman beranda, menu grup Pengaturan dan submenu Absensi tampil
        $response = $this->actingAs($yayasan)->get(route('beranda'));
        $response->assertStatus(200);
        $response->assertSee('navGroupPengaturan');
        $response->assertSee('togglePengaturan');
        $response->assertSee('Pengaturan');
        $response->assertSee('Absensi');
        $response->assertSee(route('pengaturan-absensi.index'));

        // 2. Saat mengakses route pengaturan-absensi, grup Pengaturan berstatus 'open' dan submenu Absensi aktif
        $absensiResponse = $this->actingAs($yayasan)->get(route('pengaturan-absensi.index'));
        $absensiResponse->assertStatus(200);
        $absensiResponse->assertSee('nav-group open', false);
        $absensiResponse->assertSee('subnav-item active', false);
    }
}

