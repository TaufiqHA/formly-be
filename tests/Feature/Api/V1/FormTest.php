<?php

namespace Tests\Feature\Api\V1;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_forms(): void
    {
        Form::factory()->count(3)->create(['user_id' => $this->user->id]);
        Form::factory()->count(2)->create(); // Other user's forms

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/forms');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'slug', 'status', 'updated_at']
                ]
            ]);
    }

    public function test_can_create_form(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/forms', [
            'title' => 'New Survey',
            'description' => 'A description here'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'title', 'slug', 'status']
            ])
            ->assertJsonPath('data.title', 'New Survey')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('forms', [
            'title' => 'New Survey',
            'user_id' => $this->user->id
        ]);
    }

    public function test_can_show_form(): void
    {
        $form = Form::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/forms/{$form->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'title', 'description', 'status', 'fields']
            ]);
    }

    public function test_can_update_form(): void
    {
        $form = Form::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/forms/{$form->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'title' => 'Updated Title'
        ]);
    }

    public function test_can_delete_form(): void
    {
        $form = Form::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/forms/{$form->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Form berhasil dihapus');

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);
    }

    public function test_can_update_form_status(): void
    {
        $form = Form::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/v1/forms/{$form->id}/status", [
            'status' => 'active'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertEquals('active', $form->refresh()->status);
    }

    public function test_can_get_form_stats(): void
    {
        $form = Form::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/forms/{$form->id}/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['total_views', 'total_submissions', 'conversion_rate']
            ]);
    }

    public function test_can_filter_forms_by_status(): void
    {
        Form::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'title' => 'Active Form'
        ]);
        Form::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'title' => 'Draft Form'
        ]);

        Sanctum::actingAs($this->user);

        // Test filter active
        $response = $this->getJson('/api/v1/forms?status=active');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Active Form');

        // Test filter draft
        $response = $this->getJson('/api/v1/forms?status=draft');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Draft Form');
    }

    public function test_can_search_forms_by_title(): void
    {
        Form::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Survey Kepuasan Pelanggan'
        ]);
        Form::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Formulir Pemesanan Kopi'
        ]);

        Sanctum::actingAs($this->user);

        // Test search "kepuasan"
        $response = $this->getJson('/api/v1/forms?search=kepuasan');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Survey Kepuasan Pelanggan');

        // Test search "kopi"
        $response = $this->getJson('/api/v1/forms?search=kopi');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Formulir Pemesanan Kopi');
    }

    public function test_can_combine_search_and_filter(): void
    {
        Form::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'title' => 'Order Kopi Active'
        ]);
        Form::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'title' => 'Order Kopi Draft'
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/forms?status=active&search=kopi');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Order Kopi Active');
    }

    public function test_cannot_access_other_users_form(): void
    {
        $otherUser = User::factory()->create();
        $otherForm = Form::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($this->user);

        // Test show
        $this->getJson("/api/v1/forms/{$otherForm->id}")->assertStatus(404);

        // Test update
        $this->putJson("/api/v1/forms/{$otherForm->id}", ['title' => 'Hacked'])
            ->assertStatus(404);

        // Test delete
        $this->deleteJson("/api/v1/forms/{$otherForm->id}")->assertStatus(404);

        // Test update status
        $this->patchJson("/api/v1/forms/{$otherForm->id}/status", ['status' => 'active'])
            ->assertStatus(404);

        // Test stats
        $this->getJson("/api/v1/forms/{$otherForm->id}/stats")->assertStatus(404);
    }
}
