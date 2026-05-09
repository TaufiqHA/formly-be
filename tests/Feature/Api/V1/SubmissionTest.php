<?php

namespace Tests\Feature\Api\V1;

use App\Models\Form;
use App\Models\Submission;
use App\Models\SubmissionNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_submissions(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        Submission::factory()->count(5)->create(['form_id' => $form->id]);

        $response = $this->getJson('/api/v1/submissions');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.items')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'submission_number',
                            'customer_name',
                            'customer_phone',
                            'form_title',
                            'status',
                            'submitted_at',
                        ],
                    ],
                    'pagination' => ['page', 'limit', 'total'],
                ],
            ]);
    }

    public function test_can_filter_submissions_by_status(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        Submission::factory()->create(['form_id' => $form->id, 'status' => 'new']);
        Submission::factory()->create(['form_id' => $form->id, 'status' => 'read']);

        $response = $this->getJson('/api/v1/submissions?status=new');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'new');
    }

    public function test_can_search_submissions(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        Submission::factory()->create([
            'form_id' => $form->id,
            'customer_name' => 'Unique Name',
            'submission_number' => 'SUB-999',
        ]);
        Submission::factory()->create([
            'form_id' => $form->id,
            'customer_name' => 'Regular Person',
        ]);

        // Search by name
        $response = $this->getJson('/api/v1/submissions?search=Unique');
        $response->assertJsonCount(1, 'data.items');

        // Search by number
        $response = $this->getJson('/api/v1/submissions?search=SUB-999');
        $response->assertJsonCount(1, 'data.items');
    }

    public function test_can_show_submission_detail(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        $submission = Submission::factory()->create(['form_id' => $form->id]);

        SubmissionNote::factory()->create([
            'submission_id' => $submission->id,
            'user_id' => $this->user->id,
            'content' => 'Test note',
        ]);

        $response = $this->getJson("/api/v1/submissions/{$submission->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $submission->id,
                    'submission_number' => $submission->submission_number,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'values',
                    'notes' => [
                        '*' => ['id', 'user_name', 'content', 'created_at'],
                    ],
                ],
            ]);
    }

    public function test_can_update_submission_status(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        $submission = Submission::factory()->create(['form_id' => $form->id, 'status' => 'new']);

        $response = $this->patchJson("/api/v1/submissions/{$submission->id}/status", [
            'status' => 'read',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'read',
        ]);
    }

    public function test_can_add_note_to_submission(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        $submission = Submission::factory()->create(['form_id' => $form->id]);

        $response = $this->postJson("/api/v1/submissions/{$submission->id}/notes", [
            'content' => 'Important note',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('submission_notes', [
            'submission_id' => $submission->id,
            'user_id' => $this->user->id,
            'content' => 'Important note',
        ]);
    }

    public function test_can_resend_wa_notification(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        $submission = Submission::factory()->create(['form_id' => $form->id]);

        $response = $this->postJson("/api/v1/submissions/{$submission->id}/resend-wa");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Notifikasi WhatsApp dimasukkan ke antrean']);
    }

    public function test_can_export_submissions(): void
    {
        Sanctum::actingAs($this->user);

        $form = Form::factory()->create(['user_id' => $this->user->id]);
        Submission::factory()->count(3)->create(['form_id' => $form->id]);

        $response = $this->get('/api/v1/submissions/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('ID,"Submission Number","Customer Name","Customer Phone","Form Title",Status,"Submitted At"', $content);
    }
}
