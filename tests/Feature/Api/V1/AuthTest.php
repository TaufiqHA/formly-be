<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => [
                        'email' => 'test@example.com',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test user cannot login with invalid password.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user cannot login with non-existent email.
     */
    public function test_user_cannot_login_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test authenticated user can get profile.
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'phone' => '+6281234567890',
            'location' => 'Jakarta',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => '+6281234567890',
                    'location' => 'Jakarta',
                    'avatar_url' => 'https://example.com/avatar.jpg',
                    'role' => 'admin',
                ],
            ]);
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil',
            ]);

        $this->assertEmpty($user->tokens);
    }

    /**
     * Test unauthenticated user cannot access profile.
     */
    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated user cannot logout.
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
