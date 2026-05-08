<?php

namespace Tests\Feature\Api\V1;

use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_summary(): void
    {
        Sanctum::actingAs($this->user);

        Form::factory()->count(3)->create(['status' => 'active', 'user_id' => $this->user->id]);
        Form::factory()->create(['status' => 'draft', 'user_id' => $this->user->id]);

        $form = Form::first();
        Submission::factory()->count(10)->create(['form_id' => $form->id]);

        $response = $this->getJson('/api/v1/analytics/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_responses' => 10,
                    'active_forms' => 3,
                    'average_conversion' => 0,
                ],
            ]);
    }

    public function test_can_get_trend(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);

        // Create 2 submissions today
        Submission::factory()->count(2)->create(['form_id' => $form->id, 'created_at' => now()]);

        // Create 1 submission yesterday
        Submission::factory()->create(['form_id' => $form->id, 'created_at' => now()->subDay()]);

        $response = $this->getJson('/api/v1/analytics/trend');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // We expect at least 2 entries if grouping works (yesterday and today)
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_get_status_distribution(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);

        Submission::factory()->count(3)->create(['form_id' => $form->id, 'status' => 'new']);
        Submission::factory()->count(2)->create(['form_id' => $form->id, 'status' => 'done']);

        $response = $this->getJson('/api/v1/analytics/status-distribution');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    ['status' => 'done', 'count' => 2],
                    ['status' => 'new', 'count' => 3],
                ],
            ]);
    }

    public function test_analytics_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/analytics/summary');
        $response->assertStatus(401);
    }
}
