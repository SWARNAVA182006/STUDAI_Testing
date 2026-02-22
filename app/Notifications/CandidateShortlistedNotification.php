<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidateShortlistedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Application $application,
        protected float $matchScore
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jobTitle = $this->application->job->title ?? 'a position';
        $company = $this->application->job->company_name ?? 'a company';

        return (new MailMessage())
            ->subject("You've Been Shortlisted!")
            ->greeting("Congratulations {$notifiable->name}!")
            ->line("You've been shortlisted for {$jobTitle} at {$company}.")
            ->line('The employer has shown interest in your profile.')
            ->action('View Application', url('/applications/' . $this->application->id))
            ->line('Prepare for the next steps in the hiring process.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'candidate_shortlisted',
            'application_id' => $this->application->id,
            'match_score' => $this->matchScore,
            'message' => "You've been shortlisted! Review your application.",
        ];
    }
}
