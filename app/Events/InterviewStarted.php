<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user starts an interview practice session.
 */
class InterviewStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public InterviewSession $session,
        public string $interviewType = 'general' // general, job_specific, behavioral
    ) {}
}
