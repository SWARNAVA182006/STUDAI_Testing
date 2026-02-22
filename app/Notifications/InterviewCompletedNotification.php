<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\InterviewSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected InterviewSession $session,
        protected float $overallScore,
        protected int $questionsAnswered
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scorePercent = round($this->overallScore * 100);

        return (new MailMessage())
            ->subject('Interview Practice Session Complete')
            ->greeting("Hi {$notifiable->name},")
            ->line('You\'ve completed an interview practice session!')
            ->line("Overall Score: {$scorePercent}%")
            ->line("Questions Answered: {$this->questionsAnswered}")
            ->action('View Detailed Feedback', url('/interviews/sessions/' . $this->session->id))
            ->line('Keep practicing to improve your interview skills.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'interview_completed',
            'session_id' => $this->session->id,
            'overall_score' => $this->overallScore,
            'questions_answered' => $this->questionsAnswered,
            'message' => 'Your interview practice session is complete. View your feedback.',
        ];
    }
}
