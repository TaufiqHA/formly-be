<?php

namespace Tests\Feature\Api\V1;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FormFieldTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Form $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->form = Form::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_bulk_update_fields(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Create fields
        $response = $this->putJson("/api/v1/forms/{$this->form->id}/fields", [
            'fields' => [
                [
                    'label' => 'Full Name',
                    'field_type' => 'text',
                    'placeholder' => 'Enter name',
                    'is_required' => true,
                    'sort_order' => 1,
                ],
                [
                    'label' => 'Gender',
                    'field_type' => 'radio',
                    'options' => ['Male', 'Female'],
                    'is_required' => false,
                    'sort_order' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $this->form->fields);
        $this->assertDatabaseHas('form_fields', ['label' => 'Full Name', 'form_id' => $this->form->id]);

        $genderField = $this->form->fields()->where('label', 'Gender')->first();

        // 2. Update one, Delete one, Add one
        $response = $this->putJson("/api/v1/forms/{$this->form->id}/fields", [
            'fields' => [
                [
                    'id' => $genderField->id,
                    'label' => 'Gender Updated',
                    'field_type' => 'radio',
                    'options' => ['Male', 'Female', 'Other'],
                    'is_required' => true,
                    'sort_order' => 1,
                ],
                [
                    'label' => 'Phone',
                    'field_type' => 'text',
                    'is_required' => true,
                    'sort_order' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->form->refresh();
        $this->assertCount(2, $this->form->fields);
        $this->assertDatabaseMissing('form_fields', ['label' => 'Full Name']);
        $this->assertDatabaseHas('form_fields', ['label' => 'Gender Updated', 'id' => $genderField->id]);
        $this->assertDatabaseHas('form_fields', ['label' => 'Phone']);
    }

    public function test_cannot_update_other_users_form_fields(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/forms/{$this->form->id}/fields", [
            'fields' => [],
        ]);

        $response->assertStatus(404);
    }
}
