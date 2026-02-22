<?php

namespace App\Notifications\Agent;

use App\Models\AutoApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Application Submitted Notification
 * 
 * Notifies user when the agent successfully submits an application.
 */
class ApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AutoApplication $application
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database']; // Don't spam email for each application
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'application_submitted',
            'application_id' => $this->application->id,
            'job_title' => $this->application->job_title,
            'company_name' => $this->application->company_name,
            'match_score' => $this->application->match_score,
            'submitted_at' => $this->application->created_at->toISOString(),
            'message' => "Applied to {$this->application->job_title} at {$this->application->company_name}",
        ];
    }
}
