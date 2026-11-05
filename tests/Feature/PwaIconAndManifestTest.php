<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaIconAndManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LembagaAndUserSeeder::class);
    }

    public function test_manifest_file_exists_and_has_valid_json_structure()
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertEquals('Pondok Pesantren Muttaqin', $data['name']);
        $this->assertEquals('Al-Muttaqin', $data['short_name']);
        $this->assertEquals('standalone', $data['display']);
        $this->assertNotEmpty($data['icons']);
    }

    public function test_apple_touch_icons_and_pwa_icons_exist_in_public()
    {
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertFileExists(public_path('apple-touch-icon-180x180.png'));
        $this->assertFileExists(public_path('apple-touch-icon-precomposed.png'));
        $this->assertFileExists(public_path('icons/icon-192x192.png'));
        $this->assertFileExists(public_path('icons/icon-512x512.png'));
        $this->assertFileExists(public_path('favicon-32x32.png'));
        $this->assertFileExists(public_path('favicon.ico'));

        // Check file sizes are non-zero
        $this->assertGreaterThan(1000, filesize(public_path('apple-touch-icon.png')));
        $this->assertGreaterThan(1000, filesize(public_path('icons/icon-192x192.png')));
        $this->assertGreaterThan(1000, filesize(public_path('icons/icon-512x512.png')));
    }

    public function test_login_page_renders_apple_touch_icon_and_pwa_meta()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $response->assertSee('rel="apple-touch-icon"', false);
        $response->assertSee('name="apple-mobile-web-app-capable" content="yes"', false);
        $response->assertSee('name="apple-mobile-web-app-title" content="Al-Muttaqin"', false);
        $response->assertSee('rel="manifest"', false);
    }

    public function test_beranda_renders_apple_touch_icon_and_pwa_meta()
    {
        $user = User::where('role', 'yayasan')->first();

        $response = $this->actingAs($user)
            ->withSession(['active_room' => 'yayasan'])
            ->get('/beranda');
        $response->assertStatus(200);

        $response->assertSee('rel="apple-touch-icon"', false);
        $response->assertSee('name="apple-mobile-web-app-capable" content="yes"', false);
        $response->assertSee('name="apple-mobile-web-app-title" content="Al-Muttaqin"', false);
        $response->assertSee('rel="manifest"', false);
    }
}
