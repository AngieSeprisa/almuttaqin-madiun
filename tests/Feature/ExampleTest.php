<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(LembagaAndUserSeeder::class);
        $user = User::where('email', 'yayasan@almuttaqin.id')->first();

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
    }
}
