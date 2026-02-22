<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SkillAssessment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user passes a skill assessment.
 */
class SkillAssessmentPassed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public SkillAssessment $assessment,
        public string $skillName,
        public float $score
    ) {}
}
