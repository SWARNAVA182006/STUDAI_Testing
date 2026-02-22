<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Resume;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResumeAnalysisReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Resume $resume,
        protected array $analysisResults
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $score = $this->analysisResults['ats_score'] ?? $this->analysisResults['overall_score'] ?? 'N/A';

        return (new MailMessage())
            ->subject('Your Resume Analysis is Ready')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your resume has been analyzed by our AI engine.')
            ->line("ATS Compatibility Score: {$score}")
            ->action('View Analysis', url('/resumes/' . $this->resume->id))
            ->line('Use the insights to optimize your resume for better results.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resume_analysis_ready',
            'resume_id' => $this->resume->id,
            'ats_score' => $this->analysisResults['ats_score'] ?? null,
            'message' => 'Your resume analysis is ready. View insights to improve your resume.',
        ];
    }
}
