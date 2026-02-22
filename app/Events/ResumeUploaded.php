<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a user uploads or creates a resume.
 */
class ResumeUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public Resume $resume,
        public string $uploadMethod = 'manual' // manual, import, ai_generated
    ) {}
}
