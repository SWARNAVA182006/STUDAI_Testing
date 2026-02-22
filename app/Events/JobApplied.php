<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user applies to a job.
 */
class JobApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Job $job,
        public Application $application,
        public string $applicationMethod = 'manual', // manual, agent, quick_apply
        public bool $aiAssisted = false
    ) {}
}
