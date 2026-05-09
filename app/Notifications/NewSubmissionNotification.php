<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewSubmissionNotification extends Notification
{
    use Queueable;

    public $submission;

    /**
     * Create a new notification instance.
     */
    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the notification's delivery channels.
     * Kita hanya menggunakan 'database' untuk In-App Notification.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     * Ini adalah data yang akan disimpan di tabel notifications kolom `data`.
     */
    public function toDatabase($notifiable)
    {
        // Load relasi form untuk mendapatkan judul form
        $this->submission->loadMissing('form');

        return [
            'submission_id' => $this->submission->id,
            'submission_number' => $this->submission->submission_number,
            'customer_name' => $this->submission->customer_name,
            'form_title' => $this->submission->form ? $this->submission->form->title : 'Form',
            'message' => 'Ada submission baru dari ' . $this->submission->customer_name,
        ];
    }
}
