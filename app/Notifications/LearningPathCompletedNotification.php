<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LearningPath;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearningPathCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected LearningPath $learningPath,
        protected float $completionScore
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scorePercent = round($this->completionScore * 100);
        $pathName = $this->learningPath->name ?? $this->learningPath->skill ?? 'your learning path';

        return (new MailMessage())
            ->subject("Learning Path Completed: {$pathName}")
            ->greeting("Great job {$notifiable->name}!")
            ->line("You've completed {$pathName} with a score of {$scorePercent}%.")
            ->action('View Your Learning Progress', url('/skills/learning'))
            ->line('Keep up the great work in advancing your career!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'learning_path_completed',
            'learning_path_id' => $this->learningPath->id,
            'completion_score' => $this->completionScore,
            'message' => 'You completed a learning path! View your progress.',
        ];
    }
}
