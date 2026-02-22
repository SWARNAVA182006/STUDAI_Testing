<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user completes an interview practice session.
 */
class InterviewCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public InterviewSession $session,
        public float $overallScore,
        public int $questionsAnswered
    ) {}
}
