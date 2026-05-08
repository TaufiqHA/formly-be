<?php

namespace Tests\Feature\Api\V1;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Submission;
use App\Models\SubmissionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_active_form_by_slug(): void
    {
        $form = Form::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'test-form',
            'status' => 'active',
        ]);

        FormField::factory()->count(2)->create([
            'form_id' => $form->id,
        ]);

        $response = $this->getJson("/api/v1/public/forms/test-form");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $form->id,
                    'title' => $form->title,
                ],
            ]);
    }

    public function test_cannot_get_draft_form_by_slug(): void
    {
        Form::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'draft-form',
            'status' => 'draft',
        ]);

        $response = $this->getJson("/api/v1/public/forms/draft-form");

        $response->assertStatus(404);
    }

    public function test_can_submit_form(): void
    {
        $form = Form::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'submit-form',
            'status' => 'active',
        ]);

        $field1 = FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Name',
            'field_type' => 'text',
        ]);

        $field2 = FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Interests',
            'field_type' => 'check',
        ]);

        $payload = [
            'values' => [
                $field1->id => 'John Doe',
                $field2->id => ['Coding', 'Reading'],
            ],
        ];

        $response = $this->postJson("/api/v1/public/forms/submit-form/submit", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pesanan berhasil dikirim',
            ]);

        $this->assertDatabaseHas('submissions', [
            'form_id' => $form->id,
            'status' => 'new',
        ]);

        $submission = Submission::first();

        $this->assertDatabaseHas('submission_values', [
            'submission_id' => $submission->id,
            'form_field_id' => $field1->id,
            'field_label' => 'Name',
            'value_text' => 'John Doe',
        ]);

        $this->assertDatabaseHas('submission_values', [
            'submission_id' => $submission->id,
            'form_field_id' => $field2->id,
            'field_label' => 'Interests',
            'value_json' => json_encode(['Coding', 'Reading']),
        ]);
    }
}
