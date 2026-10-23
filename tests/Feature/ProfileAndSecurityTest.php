<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }
    public function test_profile_page_displays_biodata_and_contact_form(): void
    {
        $user = User::where('role', 'guru')->firstOrFail();

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertStatus(200);
        $response->assertSee('Biodata Diri');
        $response->assertSee('Informasi Kontak');
        $response->assertSee('Keamanan & Password');
        $response->assertSee(route('user'));
        $response->assertSee('Simpan Perubahan');
    }

    public function test_user_can_update_own_biodata_and_contact(): void
    {
        $user = User::where('role', 'guru')->firstOrFail();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name'          => 'Ustadz Ahmad Fauzi, M.Pd.',
            'email'         => $user->email,
            'nip'           => '198501012010011002',
            'jabatan'       => 'Koordinator Tahfidz',
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir'  => 'Jombang',
            'tanggal_lahir' => '1985-01-01',
            'telepon'       => '081234567890',
            'alamat'        => 'Jl. Pesantren No. 12, Jombang',
        ]);

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Ustadz Ahmad Fauzi, M.Pd.', $user->name);
        $this->assertEquals('198501012010011002', $user->nip);
        $this->assertEquals('081234567890', $user->telepon);
        $this->assertEquals('Jl. Pesantren No. 12, Jombang', $user->alamat);
    }

    public function test_user_security_page_loads_at_user_url(): void
    {
        $user = User::where('role', 'guru')->firstOrFail();

        $response = $this->actingAs($user)->get(route('user'));

        $response->assertStatus(200);
        $response->assertSee('Keamanan & Kata Sandi', false);
        $response->assertSee('Kata Sandi Saat Ini');
        $response->assertSee('Kata Sandi Baru');
        $response->assertSee('Konfirmasi Kata Sandi Baru');
        $response->assertSee(route('profile'));
    }

    public function test_user_can_update_password_at_user_url(): void
    {
        $user = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($user)
            ->from(route('user'))
            ->put(route('user.password.update'), [
                'current_password'      => 'password',
                'password'              => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertRedirect(route('user'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $user->password));
    }

    public function test_button_theme_matches_active_institution(): void
    {
        $smpUser = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $responseSmp = $this->actingAs($smpUser)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('profile'));

        $responseSmp->assertStatus(200);
        $responseSmp->assertSee('btn-theme-smp');

        $smaUser = User::where('role', 'yayasan')->firstOrFail();
        $responseSma = $this->actingAs($smaUser)
            ->withSession(['active_lembaga' => 'sma'])
            ->get(route('profile'));

        $responseSma->assertStatus(200);
        $responseSma->assertSee('btn-theme-sma');
    }

    public function test_superadmin_can_create_user_with_credentials_flash(): void
    {
        $yayasan = User::where('role', 'yayasan')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('pengguna.store'), [
            'name'                 => 'Ustadzah Fatimah, S.Pd.',
            'email'                => 'fatimah.guru@almuttaqin.id',
            'password'             => 'Muttaqin2026!',
            'role'                 => 'guru',
            'jabatan'              => 'Guru Bahasa Arab SMP',
            'lembagas'             => [$smp->id],
            'is_active'            => 1,
            'must_change_password' => 1,
            'permissions'          => [
                'siswa' => ['view' => 1],
                'perizinan_sambang' => ['view' => 1, 'create' => 1, 'edit' => 1],
            ],
        ]);

        $response->assertRedirect(route('pengguna.index'));
        $response->assertSessionHas('user_credentials');

        $newUser = User::where('email', 'fatimah.guru@almuttaqin.id')->firstOrFail();
        $this->assertTrue($newUser->must_change_password);
        $this->assertTrue(Hash::check('Muttaqin2026!', $newUser->password));
    }

    public function test_superadmin_can_reset_user_password(): void
    {
        $yayasan = User::where('role', 'yayasan')->firstOrFail();
        $guru = User::where('role', 'guru')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('pengguna.resetPassword', $guru->id), [
            'password' => 'ResetPass2026!',
        ]);

        $response->assertRedirect(route('pengguna.index'));
        $response->assertSessionHas('user_credentials');

        $guru->refresh();
        $this->assertTrue($guru->must_change_password);
        $this->assertTrue(Hash::check('ResetPass2026!', $guru->password));
    }

    public function test_temporary_password_warning_banner_displayed_and_cleared_after_change(): void
    {
        $guru = User::where('role', 'guru')->firstOrFail();
        $guru->must_change_password = true;
        $guru->password = Hash::make('temp12345');
        $guru->save();

        // 1. Banner appears on beranda
        $responseBeranda = $this->actingAs($guru)->get(route('beranda'));
        $responseBeranda->assertStatus(200);
        $responseBeranda->assertSee('Akun Anda saat ini menggunakan kata sandi sementara dari Admin');

        // 2. Guru updates password at /user
        $responseUpdate = $this->actingAs($guru)
            ->from(route('user'))
            ->put(route('user.password.update'), [
                'current_password'      => 'temp12345',
                'password'              => 'PersonalSecret789',
                'password_confirmation' => 'PersonalSecret789',
            ]);

        $responseUpdate->assertRedirect(route('user'));

        $guru->refresh();
        $this->assertFalse($guru->must_change_password);

        // 3. Banner no longer appears on beranda
        $responseAfter = $this->actingAs($guru)->get(route('beranda'));
        $responseAfter->assertDontSee('Akun Anda saat ini menggunakan kata sandi sementara dari Admin');
    }
}
