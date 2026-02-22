<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $matchedJobs;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $matchedJobs)
    {
        $this->matchedJobs = $matchedJobs;
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
        $jobCount = count($this->matchedJobs);
        $topJob = $this->matchedJobs[0] ?? null;

        $message = (new MailMessage)
            ->subject("🎯 {$jobCount} New Job Match" . ($jobCount > 1 ? 'es' : '') . " for You!")
            ->greeting("Hello {$notifiable->name}!")
            ->line("We found {$jobCount} new job" . ($jobCount > 1 ? 's' : '') . " that match your preferences:");

        // Add top 5 jobs to email
        foreach (array_slice($this->matchedJobs, 0, 5) as $index => $jobData) {
            $job = $jobData['job'];
            $matchScore = $jobData['match_score'];
            $company = $job->company?->name ?? 'Company';
            
            $message->line("**{$job->title}** at {$company}")
                   ->line("📍 {$job->location} | 💼 {$job->employment_type} | Match: {$matchScore}%");
            
            if ($index < 4) {
                $message->line('---');
            }
        }

        if ($jobCount > 5) {
            $message->line("...and " . ($jobCount - 5) . " more!");
        }

        $message->action('View All Matches', url('/jobs/recommended'))
               ->line('Update your job alert preferences anytime in your profile.')
               ->line('Happy job hunting! 🚀');

        return $message;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'job_alert',
            'job_count' => count($this->matchedJobs),
            'top_jobs' => array_slice(
                array_map(fn($j) => [
                    'id' => $j['job']->id,
                    'title' => $j['job']->title,
                    'company' => $j['job']->company?->name,
                    'match_score' => $j['match_score'],
                    'alert_name' => $j['alert_name'],
                ], $this->matchedJobs),
                0,
                5
            ),
            'message' => count($this->matchedJobs) . ' new job matches found',
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}

