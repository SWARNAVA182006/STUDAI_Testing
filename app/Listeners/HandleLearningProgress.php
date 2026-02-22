<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LearningPathCompleted;
use App\Events\LearningPathStarted;
use App\Events\SkillAssessmentPassed;
use App\Events\SkillGapIdentified;
use App\Notifications\LearningPathCompletedNotification;
use App\Notifications\SkillAssessmentPassedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleLearningProgress implements ShouldQueue
{
    public string $queue = 'default';

    public function handleSkillGapIdentified(SkillGapIdentified $event): void
    {
        Log::info('Skill gap identified', [
            'user_id' => $event->user->id,
            'skill_gap_id' => $event->skillGap->id,
            'target_role' => $event->targetRole,
            'priority' => $event->priority,
        ]);
    }

    public function handleLearningPathStarted(LearningPathStarted $event): void
    {
        Log::info('Learning path started', [
            'user_id' => $event->user->id,
            'learning_path_id' => $event->learningPath->id,
            'skill' => $event->skill,
        ]);
    }

    public function handleSkillAssessmentPassed(SkillAssessmentPassed $event): void
    {
        try {
            $event->user->notify(new SkillAssessmentPassedNotification(
                $event->assessment,
                $event->skillName,
                $event->score
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send skill assessment notification', ['error' => $e->getMessage()]);
        }
    }

    public function handleLearningPathCompleted(LearningPathCompleted $event): void
    {
        try {
            $event->user->notify(new LearningPathCompletedNotification(
                $event->learningPath,
                $event->completionScore
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send learning path completed notification', ['error' => $e->getMessage()]);
        }

        Log::info('Learning path completed', [
            'user_id' => $event->user->id,
            'learning_path_id' => $event->learningPath->id,
            'completion_score' => $event->completionScore,
        ]);
    }

    public function subscribe($events): array
    {
        return [
            SkillGapIdentified::class => 'handleSkillGapIdentified',
            LearningPathStarted::class => 'handleLearningPathStarted',
            SkillAssessmentPassed::class => 'handleSkillAssessmentPassed',
            LearningPathCompleted::class => 'handleLearningPathCompleted',
        ];
    }
}
