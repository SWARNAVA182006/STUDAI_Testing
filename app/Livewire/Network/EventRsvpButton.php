<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\NetworkEvent;
use App\Services\EventService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EventRsvpButton extends Component
{
    #[Reactive]
    public NetworkEvent $event;

    protected EventService $eventService;

    public function boot(EventService $eventService): void
    {
        $this->eventService = $eventService;
    }

    public function rsvp(string $status = 'going'): void
    {
        if ($status === 'going' && $this->event->isFull()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'This event is full.',
            ]);
            return;
        }

        $this->eventService->rsvp($this->event, Auth::user(), $status);
        $this->event->refresh();

        $message = match ($status) {
            'going' => 'You\'re going to this event!',
            'interested' => 'Marked as interested.',
            'not_going' => 'RSVP updated.',
        };

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);
    }

    public function cancelRsvp(): void
    {
        $this->eventService->cancelRsvp($this->event, Auth::user());
        $this->event->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'RSVP cancelled.',
        ]);
    }

    public function render()
    {
        $userRsvp = $this->event->getRsvpForUser(Auth::user());

        return view('livewire.network.event-rsvp-button', [
            'userRsvp' => $userRsvp,
        ]);
    }
}
