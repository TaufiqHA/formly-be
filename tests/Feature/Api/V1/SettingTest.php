<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\WaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_settings_and_initializes_if_missing(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'preferences' => [
                        'notif_email_new_order' => true,
                        'theme' => 'light',
                    ],
                    'whatsapp' => [
                        'connection_status' => 'disconnected',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('user_preferences', ['user_id' => $this->user->id]);
        $this->assertDatabaseHas('wa_settings', ['user_id' => $this->user->id]);
    }

    public function test_can_update_preferences(): void
    {
        Sanctum::actingAs($this->user);

        // Initialize first
        $this->getJson('/api/v1/settings');

        $payload = [
            'notif_email_new_order' => false,
            'theme' => 'dark',
        ];

        $response = $this->putJson('/api/v1/settings', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'notif_email_new_order' => false,
            'theme' => 'dark',
        ]);
    }

    public function test_can_update_whatsapp_settings(): void
    {
        Sanctum::actingAs($this->user);

        // Initialize first
        $this->getJson('/api/v1/settings');

        $payload = [
            'api_key' => 'secret-key',
            'phone_number' => '628123456789',
            'wa_template_new_order' => 'Custom template',
        ];

        $response = $this->putJson('/api/v1/settings/whatsapp', $payload);

        $response->assertStatus(200);

        $waSetting = WaSetting::where('user_id', $this->user->id)->first();
        $this->assertEquals('secret-key', $waSetting->api_key);
        $this->assertEquals('628123456789', $waSetting->phone_number);
        $this->assertEquals('Custom template', $waSetting->wa_template_new_order);
        $this->assertEquals('disconnected', $waSetting->connection_status);
    }

    public function test_can_test_whatsapp_connection(): void
    {
        Sanctum::actingAs($this->user);

        // Initialize and set API Key
        $waSetting = WaSetting::firstOrCreate(['user_id' => $this->user->id]);
        $waSetting->update(['api_key' => 'some-key']);

        $response = $this->postJson('/api/v1/settings/whatsapp/test');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('wa_settings', [
            'user_id' => $this->user->id,
            'connection_status' => 'connected',
        ]);
    }

    public function test_cannot_test_whatsapp_connection_without_api_key(): void
    {
        Sanctum::actingAs($this->user);

        WaSetting::firstOrCreate(['user_id' => $this->user->id], ['api_key' => null]);

        $response = $this->postJson('/api/v1/settings/whatsapp/test');

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }
}
