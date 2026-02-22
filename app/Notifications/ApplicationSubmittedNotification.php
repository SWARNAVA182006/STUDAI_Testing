<?php

namespace App\Notifications;

use App\Models\AutoApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AutoApplication $application;

    /**
     * Create a new notification instance.
     */
    public function __construct(AutoApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->application->discoveredJob;

        return (new MailMessage)
            ->subject('Application Submitted: ' . $job->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your autonomous agent has successfully submitted an application on your behalf.')
            ->line('**Position:** ' . $job->title)
            ->line('**Company:** ' . $job->company_name)
            ->line('**Location:** ' . $job->location)
            ->line('**Match Score:** ' . $this->application->jobMatch->match_score . '%')
            ->action('View Application', route('applications.show', $this->application->id))
            ->line('Your application has been submitted and is now pending review by the employer.')
            ->line('We will notify you of any status updates.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'job_id' => $this->application->discovered_job_id,
            'job_title' => $this->application->discoveredJob->title,
            'company_name' => $this->application->discoveredJob->company_name,
            'match_score' => $this->application->jobMatch->match_score,
            'status' => $this->application->status,
            'submitted_at' => $this->application->submitted_at,
            'message' => "Application submitted for {$this->application->discoveredJob->title} at {$this->application->discoveredJob->company_name}",
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'application_id' => $this->application->id,
            'job_title' => $this->application->discoveredJob->title,
            'company_name' => $this->application->discoveredJob->company_name,
            'message' => "New application submitted!",
        ]);
    }
}
