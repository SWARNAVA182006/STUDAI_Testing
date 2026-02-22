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
 * Dispatched when a predictive analytics result is generated for a candidate.
 */
class PredictionGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $employer,
        public Application $application,
        public string $predictionType,
        public array $results
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
        return 'prediction.generated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'employer_id' => $this->employer->id,
            'application_id' => $this->application->id,
            'prediction_type' => $this->predictionType,
            'results' => $this->results,
        ];
    }
}
