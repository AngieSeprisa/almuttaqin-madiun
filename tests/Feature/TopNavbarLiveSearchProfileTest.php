<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopNavbarLiveSearchProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->user = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_topbar_rendered_with_image2_components_on_all_pages_including_beranda(): void
    {
        $routes = [
            route('beranda'),
            route('siswa'),
            route('pendaftaran.index'),
            route('profile'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->user)->get($url);
            $response->assertStatus(200);

            // Clean minimalist hamburger button (no bulky button box)
            $response->assertSee('topbar-toggle-btn js-sidebar-toggle', false);

            // Clean search box with "Cari" placeholder
            $response->assertSee('topbarSearchInput', false);
            $response->assertSee('placeholder="Cari"', false);
            $response->assertSee('topbar-search-icon', false);
            $response->assertSee('topbarSearchDropdown', false);

            // Notification bell
            $response->assertSee('topbarNotifToggle', false);

            // User profile avatar circular button
            $response->assertSee('userDropdownToggle', false);
            $response->assertSee('topbar-avatar-circle', false);

            // Old card header elements must NOT exist
            $response->assertDontSee('topbar-institution-tag');
        }
    }

    public function test_avatar_displays_initials_when_user_has_no_uploaded_photo(): void
    {
        $this->user->update([
            'name' => 'Fulan Syaifullah',
            'foto' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('beranda'));
        $response->assertStatus(200);

        // Circular avatar with black background and initials "FS"
        $response->assertSee('topbar-avatar-initials', false);
        $response->assertSee('FS', false);
        $response->assertDontSee('topbar-avatar-img');
    }

    public function test_avatar_displays_photo_when_user_has_uploaded_photo(): void
    {
        Storage::fake('public');
        $uploaded = UploadedFile::fake()->image('profile.jpg', 200, 200);
        $path = $uploaded->store('avatars', 'public');

        $this->user->update([
            'foto' => $path,
        ]);

        $response = $this->actingAs($this->user)->get(route('beranda'));
        $response->assertStatus(200);

        // Displays photo image inside circular avatar
        $response->assertSee('topbar-avatar-img', false);
        $response->assertSee(asset('storage/' . $path), false);
    }

    public function test_quick_search_endpoint_returns_categorized_results(): void
    {
        // Add a sample Siswa
        $lembaga = Lembaga::first();
        Siswa::create([
            'lembaga_id' => $lembaga ? $lembaga->id : 1,
            'nama_lengkap' => 'Ahmad Dahlan',
            'jenjang' => 'SMP',
            'kamar' => 'Abu Bakar 01',
            'status' => 'aktif',
        ]);

        // Search for "Ahmad"
        $response = $this->actingAs($this->user)->getJson(route('quick-search', ['q' => 'Ahmad']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'query',
            'results' => [
                '*' => [
                    'category',
                    'items' => [
                        '*' => ['title', 'subtitle', 'url', 'icon', 'badge']
                    ]
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['results']);

        // Check if Ahmad Dahlan appears in results
        $found = false;
        foreach ($data['results'] as $group) {
            foreach ($group['items'] as $item) {
                if (str_contains($item['title'], 'Ahmad Dahlan')) {
                    $found = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($found, 'Siswa Ahmad Dahlan should be found in quick search');

        // Search for Menu "siswa"
        $responseMenu = $this->actingAs($this->user)->getJson(route('quick-search', ['q' => 'siswa']));
        $responseMenu->assertStatus(200);
        $dataMenu = $responseMenu->json();
        $this->assertNotEmpty($dataMenu['results']);
    }

    public function test_quick_search_with_empty_or_no_match_query(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('quick-search', ['q' => '']));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('results'));

        $responseNoMatch = $this->actingAs($this->user)->getJson(route('quick-search', ['q' => 'xyznomatch123456']));
        $responseNoMatch->assertStatus(200);
        $this->assertEmpty($responseNoMatch->json('results'));
    }

    public function test_user_profile_photo_upload_and_delete_flow(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('my_photo.png', 400, 400);

        // Upload photo via profile form
        $response = $this->actingAs($this->user)->put(route('profile.update'), [
            'name' => 'Fulan Syaifullah Update',
            'email' => $this->user->email,
            'foto' => $file,
        ]);

        $response->assertRedirect(route('profile'));
        $this->user->refresh();

        $this->assertNotNull($this->user->foto);
        $this->assertTrue(Storage::disk('public')->exists($this->user->foto));

        // Delete photo via profile form
        $responseDelete = $this->actingAs($this->user)->put(route('profile.update'), [
            'name' => 'Fulan Syaifullah Update',
            'email' => $this->user->email,
            'hapus_foto' => '1',
        ]);

        $responseDelete->assertRedirect(route('profile'));
        $this->user->refresh();

        $this->assertNull($this->user->foto);
    }
}
