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

    public function test_can_filter_submissions_by_form_id(): void
    {
        Sanctum::actingAs($this->user);

        $form1 = Form::factory()->create(['user_id' => $this->user->id, 'title' => 'Form 1']);
        $form2 = Form::factory()->create(['user_id' => $this->user->id, 'title' => 'Form 2']);
        
        Submission::factory()->count(3)->create(['form_id' => $form1->id]);
        Submission::factory()->count(2)->create(['form_id' => $form2->id]);

        $response = $this->getJson("/api/v1/submissions?form_id={$form1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.items');
            
        $response = $this->getJson("/api/v1/submissions?form_id={$form2->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
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
        $field = \App\Models\FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Dynamic Question'
        ]);
        
        $submission = Submission::factory()->create(['form_id' => $form->id]);
        \App\Models\SubmissionValue::factory()->create([
            'submission_id' => $submission->id,
            'form_field_id' => $field->id,
            'field_label' => 'Dynamic Question',
            'value_text' => 'Dynamic Answer'
        ]);

        $response = $this->get('/api/v1/submissions/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();
        
        // Verify static headers
        $this->assertStringContainsString('ID,"Submission Number","Customer Name","Customer Phone","Form Title",Status,"Submitted At"', $content);
        
        // Verify dynamic header
        $this->assertStringContainsString('"Dynamic Question"', $content);
        
        // Verify dynamic value
        $this->assertStringContainsString('"Dynamic Answer"', $content);
    }

    public function test_cannot_list_other_users_submissions(): void
    {
        $otherUser = User::factory()->create();
        $otherForm = Form::factory()->create(['user_id' => $otherUser->id]);
        Submission::factory()->count(3)->create(['form_id' => $otherForm->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/submissions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_cannot_access_other_users_submission_detail(): void
    {
        $otherUser = User::factory()->create();
        $otherForm = Form::factory()->create(['user_id' => $otherUser->id]);
        $otherSubmission = Submission::factory()->create(['form_id' => $otherForm->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/submissions/{$otherSubmission->id}");

        $response->assertStatus(404);
    }
}
