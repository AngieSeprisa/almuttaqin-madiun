<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiLembagaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/beranda');
        $response->assertRedirect('/login');
    }

    public function test_user_can_login_and_see_beranda(): void
    {
        $user = User::where('email', 'yayasan@almuttaqin.id')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/beranda');
        $response->assertStatus(200);
        $response->assertSee('Yayasan');
    }

    public function test_room_switcher_works(): void
    {
        $user = User::where('email', 'yayasan@almuttaqin.id')->first();

        $response = $this->actingAs($user)->get(route('switch-lembaga', ['kode' => 'smp']));
        $response->assertSessionHas('active_lembaga', 'smp');
    }

    public function test_guru_without_permission_cannot_access_pengguna(): void
    {
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->first();
        $this->assertNotNull($guru);

        $response = $this->actingAs($guru)->get('/pengguna');
        $response->assertRedirect(route('beranda'));
    }

    public function test_yayasan_can_access_pengguna_and_create_form(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->first();

        $response = $this->actingAs($yayasan)->get('/pengguna');
        $response->assertStatus(200);
        $response->assertSee('Pengguna &amp; Hak Akses', false);

        $createResponse = $this->actingAs($yayasan)->get('/pengguna/create');
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Matriks Hak Akses Menu');
    }

    public function test_sidebar_shows_smp_logo_and_title_when_in_smp_room(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->first();
        $this->assertNotNull($adminSMP);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('beranda'));

        $response->assertStatus(200);
        $response->assertSee('logo-smp.png');
        $response->assertSee('SMP AL MUTTAQIN');
    }

    public function test_sidebar_has_room_dropdown_and_topbar_room_dropdown_is_removed(): void
    {
        $user = User::where('email', 'yayasan@almuttaqin.id')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('beranda'));
        $response->assertStatus(200);

        // Sidebar contains the room switcher dropdown
        $response->assertSee('sidebar-room-area');
        $response->assertSee('sidebar-room-btn');
        $response->assertSee('sidebar-room-dropdown');

        // Topbar room switcher is removed
        $response->assertDontSee('room-switcher-wrap');
    }

    public function test_sidebar_has_user_profile_card_linking_to_profile_page(): void
    {
        $user = User::where('email', 'yayasan@almuttaqin.id')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('beranda'));
        $response->assertStatus(200);

        // Sidebar footer contains profile card with user name and profile route
        $response->assertSee('sidebar-profile-card');
        $response->assertSee($user->name);
        $response->assertSee(route('profile'));

        // Profile page itself loads cleanly
        $profileResponse = $this->actingAs($user)->get(route('profile'));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee($user->name);
        $profileResponse->assertSee('Biodata Diri');
        $profileResponse->assertSee(route('user'));
    }
}
