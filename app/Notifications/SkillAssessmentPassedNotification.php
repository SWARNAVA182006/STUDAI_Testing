<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SkillAssessment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SkillAssessmentPassedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SkillAssessment $assessment,
        protected string $skillName,
        protected float $score
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $scorePercent = round($this->score * 100);

        return (new MailMessage())
            ->subject("Skill Assessment Passed: {$this->skillName}")
            ->greeting("Congratulations {$notifiable->name}!")
            ->line("You've passed the {$this->skillName} skill assessment with a score of {$scorePercent}%.")
            ->action('View Your Skills', url('/skills'))
            ->line('This achievement has been added to your profile.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'skill_assessment_passed',
            'assessment_id' => $this->assessment->id,
            'skill_name' => $this->skillName,
            'score' => $this->score,
            'message' => "You passed the {$this->skillName} assessment!",
        ];
    }
}
