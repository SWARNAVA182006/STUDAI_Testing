<?php

namespace App\Notifications\Agent;

use App\Models\AutoApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Approval Required Notification
 * 
 * Notifies user when manual approval is required for an application.
 */
class ApprovalRequiredNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Application Approval Required')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your autonomous agent found a great match that requires your approval:')
            ->line('**Job:** ' . $this->application->job_title)
            ->line('**Company:** ' . $this->application->company_name)
            ->line('**Match Score:** ' . round($this->application->match_score) . '%')
            ->line('Please review and approve this application to proceed.')
            ->action('Review Application', route('agent.applications', ['status' => 'pending_approval']))
            ->line('The application will remain pending until you take action.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'approval_required',
            'application_id' => $this->application->id,
            'job_title' => $this->application->job_title,
            'company_name' => $this->application->company_name,
            'match_score' => $this->application->match_score,
            'message' => "Approval needed for {$this->application->job_title} at {$this->application->company_name}",
        ];
    }
}
