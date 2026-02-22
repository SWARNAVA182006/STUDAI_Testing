<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\JobListing;
use App\Models\JobMatch;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when the agent discovers a job that matches user criteria.
 */
class AgentJobMatched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public JobListing $job,
        public JobMatch $match,
        public float $matchScore,
        public bool $requiresApproval = false
    ) {}
}
