<?php

namespace App\Notifications;

use App\Models\AutoApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AutoApplication $application;
    protected string $oldStatus;
    protected string $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(AutoApplication $application, string $oldStatus, string $newStatus)
    {
        $this->application = $application;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
        $statusMessage = $this->getStatusMessage($this->newStatus);

        $mail = (new MailMessage)
            ->subject('Application Update: ' . $job->title)
            ->greeting('Hello ' . $notifiable->name . '!');

        // Customize message based on status
        switch ($this->newStatus) {
            case 'viewed':
                $mail->line('Great news! Your application has been viewed by the employer.')
                    ->line('**Position:** ' . $job->title)
                    ->line('**Company:** ' . $job->company_name)
                    ->line('This is a positive sign that they are reviewing your application.');
                break;

            case 'shortlisted':
                $mail->line('🎉 Congratulations! You have been shortlisted for an interview.')
                    ->line('**Position:** ' . $job->title)
                    ->line('**Company:** ' . $job->company_name)
                    ->line('The employer is interested in your profile and may contact you soon.');
                break;

            case 'interview_scheduled':
                $mail->line('Interview Scheduled!')
                    ->line('**Position:** ' . $job->title)
                    ->line('**Company:** ' . $job->company_name)
                    ->line('Please check your email or the employer\'s portal for interview details.');
                break;

            case 'rejected':
                $mail->line('Unfortunately, your application was not selected at this time.')
                    ->line('**Position:** ' . $job->title)
                    ->line('**Company:** ' . $job->company_name)
                    ->line('Don\'t be discouraged! Your autonomous agent is still working to find great opportunities for you.');
                break;

            case 'offer':
                $mail->line('🎊 Amazing news! You have received a job offer!')
                    ->line('**Position:** ' . $job->title)
                    ->line('**Company:** ' . $job->company_name)
                    ->line('Please review the offer details and respond promptly.');
                break;

            default:
                $mail->line('Your application status has been updated.')
                    ->line('**Position:** ' . $job->title)
                    ->line('**Company:** ' . $job->company_name)
                    ->line('**New Status:** ' . ucfirst(str_replace('_', ' ', $this->newStatus)));
        }

        return $mail->action('View Application', route('applications.show', $this->application->id));
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $this->getStatusMessage($this->newStatus),
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
            'new_status' => $this->newStatus,
            'message' => $this->getStatusMessage($this->newStatus),
        ]);
    }

    /**
     * Get human-readable status message
     */
    protected function getStatusMessage(string $status): string
    {
        return match($status) {
            'viewed' => 'Your application has been viewed by the employer',
            'shortlisted' => 'You have been shortlisted for an interview',
            'interview_scheduled' => 'Your interview has been scheduled',
            'rejected' => 'Your application was not selected',
            'offer' => 'You have received a job offer',
            default => 'Application status updated to ' . ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
