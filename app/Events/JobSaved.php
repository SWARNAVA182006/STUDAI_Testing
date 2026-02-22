<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user saves a job for later.
 */
class JobSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public JobListing $job
    ) {}
}
