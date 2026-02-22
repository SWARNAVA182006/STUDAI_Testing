<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user starts a learning path.
 */
class LearningPathStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public LearningPath $learningPath,
        public string $skill
    ) {}
}
