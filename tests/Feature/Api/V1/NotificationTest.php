<?php

namespace Tests\Feature\Api\V1;

use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\NewSubmissionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_new_submission_triggers_notification(): void
    {
        $form = Form::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'values' => [
                'some-field-id' => 'Some value',
            ],
        ];

        $response = $this->postJson("/api/v1/public/forms/{$form->slug}/submit", $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $this->user->notifications()->first();
        $this->assertEquals('NewSubmissionNotification', class_basename($notification->type));
        $this->assertEquals('John Doe', $notification->data['customer_name']);
        $this->assertEquals($form->title, $notification->data['form_title']);
    }

    public function test_can_list_notifications(): void
    {
        Sanctum::actingAs($this->user);

        $submission = Submission::factory()->create();
        $this->user->notify(new NewSubmissionNotification($submission));

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'unread_count',
                    'items' => [
                        '*' => ['id', 'type', 'data', 'read_at', 'created_at']
                    ],
                    'pagination',
                ],
            ])
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_can_mark_notification_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $submission = Submission::factory()->create();
        $this->user->notify(new NewSubmissionNotification($submission));

        $notification = $this->user->unreadNotifications()->first();

        $response = $this->patchJson("/api/v1/notifications/mark-as-read/{$notification->id}");

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $submission = Submission::factory()->create();
        $this->user->notify(new NewSubmissionNotification($submission));
        $this->user->notify(new NewSubmissionNotification($submission));

        $this->assertEquals(2, $this->user->unreadNotifications()->count());

        $response = $this->patchJson("/api/v1/notifications/mark-as-read");

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }
}
