<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BackgroundCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackgroundCheckCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected BackgroundCheck $backgroundCheck;

    /**
     * Create a new notification instance.
     */
    public function __construct(BackgroundCheck $backgroundCheck)
    {
        $this->backgroundCheck = $backgroundCheck;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $candidate = $this->backgroundCheck->candidate;
        $job = $this->backgroundCheck->job;
        $result = $this->backgroundCheck->result;
        
        $mail = (new MailMessage)
            ->subject('Background Check Complete - ' . ($candidate->name ?? 'Candidate'))
            ->greeting('Hello ' . $notifiable->name . '!');

        if ($result === 'clear') {
            $mail->line('✅ Great news! The background check for **' . ($candidate->name ?? 'the candidate') . '** has been completed with a clear result.')
                ->line('**Position:** ' . ($job->title ?? 'N/A'))
                ->line('**Result:** Clear - No issues found')
                ->line('You can proceed with the next steps in the hiring process.');
        } elseif ($result === 'consider') {
            $mail->line('⚠️ The background check for **' . ($candidate->name ?? 'the candidate') . '** has been completed and requires your review.')
                ->line('**Position:** ' . ($job->title ?? 'N/A'))
                ->line('**Result:** Requires Review')
                ->line('Please review the full report to make an informed decision.');
        } else {
            $mail->line('The background check for **' . ($candidate->name ?? 'the candidate') . '** has been completed.')
                ->line('**Position:** ' . ($job->title ?? 'N/A'))
                ->line('**Result:** ' . ucfirst($result ?? 'Complete'))
                ->line('Please review the full report for details.');
        }

        return $mail
            ->action('View Full Report', route('background-checks.show', $this->backgroundCheck))
            ->line('Thank you for using StudAI Path for your hiring needs.')
            ->salutation('Best regards, StudAI Path Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'background_check_completed',
            'background_check_id' => $this->backgroundCheck->id,
            'candidate_name' => $this->backgroundCheck->candidate->name ?? 'N/A',
            'job_title' => $this->backgroundCheck->job->title ?? 'N/A',
            'result' => $this->backgroundCheck->result,
            'has_flags' => $this->backgroundCheck->has_flags,
            'message' => 'Background check completed for ' . ($this->backgroundCheck->candidate->name ?? 'candidate'),
            'action_url' => route('background-checks.show', $this->backgroundCheck),
        ];
    }
}
