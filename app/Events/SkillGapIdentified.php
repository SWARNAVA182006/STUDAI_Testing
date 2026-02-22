<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SkillGap;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a skill gap is identified for a user.
 */
class SkillGapIdentified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public SkillGap $skillGap,
        public string $targetRole,
        public string $priority = 'medium' // low, medium, high, critical
    ) {}
}
