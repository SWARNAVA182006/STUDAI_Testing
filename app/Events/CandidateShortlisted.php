<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Application;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an employer shortlists a candidate for a position.
 */
class CandidateShortlisted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employer,
        public Application $application,
        public float $matchScore,
        public array $reasons
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->employer->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'candidate.shortlisted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'employer_id' => $this->employer->id,
            'application_id' => $this->application->id,
            'match_score' => $this->matchScore,
            'reasons' => $this->reasons,
        ];
    }
}
