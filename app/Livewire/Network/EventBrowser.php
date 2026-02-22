<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\NetworkEvent;
use App\Services\EventService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class EventBrowser extends Component
{
    use WithPagination;

    public string $activeTab = 'discover';
    public string $searchQuery = '';
    public string $typeFilter = '';
    public string $locationFilter = '';
    
    // Create event form
    public bool $showCreateModal = false;
    public string $newEventTitle = '';
    public string $newEventDescription = '';
    public string $newEventType = 'virtual';
    public string $newEventLocation = '';
    public string $newEventVirtualLink = '';
    public string $newEventStartsAt = '';
    public string $newEventEndsAt = '';
    public ?int $newEventCapacity = null;
    public bool $newEventRequiresApproval = false;

    protected EventService $eventService;

    public function boot(EventService $eventService): void
    {
        $this->eventService = $eventService;
    }

    public function mount(): void
    {
        $this->newEventStartsAt = now()->addDays(7)->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function upcomingEvents()
    {
        return $this->eventService->getUpcomingEvents(
            Auth::user(),
            12,
            $this->typeFilter ?: null
        );
    }

    #[Computed]
    public function myEvents()
    {
        return $this->eventService->getUserAttendingEvents(Auth::user(), 12);
    }

    #[Computed]
    public function organizedEvents()
    {
        return $this->eventService->getUserOrganizedEvents(Auth::user(), 12);
    }

    #[Computed]
    public function interestedEvents()
    {
        return $this->eventService->getUserInterestedEvents(Auth::user(), 12);
    }

    #[Computed]
    public function pastEvents()
    {
        return $this->eventService->getPastEvents(Auth::user(), 12);
    }

    #[Computed]
    public function featuredEvents()
    {
        return $this->eventService->getFeaturedEvents(6);
    }

    #[Computed]
    public function suggestedEvents()
    {
        return $this->eventService->getSuggestedEvents(Auth::user(), 4);
    }

    #[Computed]
    public function eventStats()
    {
        return $this->eventService->getEventStats(Auth::user());
    }

    #[Computed]
    public function happeningSoon()
    {
        return $this->eventService->getEventsHappeningSoon(Auth::user());
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function search(): void
    {
        $this->resetPage();
    }

    public function rsvp(int $eventId, string $status = 'going'): void
    {
        $event = NetworkEvent::find($eventId);
        if (!$event) {
            return;
        }

        if ($status === 'going' && $event->isFull()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'This event is full.',
            ]);
            return;
        }

        $this->eventService->rsvp($event, Auth::user(), $status);

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

    public function cancelRsvp(int $eventId): void
    {
        $event = NetworkEvent::find($eventId);
        if (!$event) {
            return;
        }

        $this->eventService->cancelRsvp($event, Auth::user());

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'RSVP cancelled.',
        ]);
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->resetCreateForm();
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    protected function resetCreateForm(): void
    {
        $this->newEventTitle = '';
        $this->newEventDescription = '';
        $this->newEventType = 'virtual';
        $this->newEventLocation = '';
        $this->newEventVirtualLink = '';
        $this->newEventStartsAt = now()->addDays(7)->format('Y-m-d\TH:i');
        $this->newEventEndsAt = '';
        $this->newEventCapacity = null;
        $this->newEventRequiresApproval = false;
    }

    public function createEvent(): void
    {
        $this->validate([
            'newEventTitle' => 'required|string|min:3|max:255',
            'newEventDescription' => 'nullable|string|max:5000',
            'newEventType' => 'required|in:virtual,in_person,hybrid',
            'newEventLocation' => 'required_if:newEventType,in_person,hybrid|nullable|string|max:255',
            'newEventVirtualLink' => 'required_if:newEventType,virtual,hybrid|nullable|url|max:255',
            'newEventStartsAt' => 'required|date|after:now',
            'newEventEndsAt' => 'nullable|date|after:newEventStartsAt',
            'newEventCapacity' => 'nullable|integer|min:1|max:10000',
        ]);

        $event = $this->eventService->createEvent(
            Auth::user(),
            [
                'title' => $this->newEventTitle,
                'description' => $this->newEventDescription,
                'type' => $this->newEventType,
                'location' => $this->newEventLocation,
                'virtual_link' => $this->newEventVirtualLink,
                'starts_at' => $this->newEventStartsAt,
                'ends_at' => $this->newEventEndsAt ?: null,
                'capacity' => $this->newEventCapacity,
                'requires_approval' => $this->newEventRequiresApproval,
            ]
        );

        $this->closeCreateModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Event created successfully!',
        ]);

        $this->setTab('organized');
    }

    #[On('event-deleted')]
    public function handleEventDeleted(): void
    {
        // Refresh the list
    }

    public function render()
    {
        $events = match ($this->activeTab) {
            'discover' => $this->searchQuery
                ? $this->eventService->searchEvents(
                    $this->searchQuery,
                    $this->typeFilter ?: null,
                    $this->locationFilter ?: null,
                    12
                )
                : $this->upcomingEvents,
            'my-events' => $this->myEvents,
            'interested' => $this->interestedEvents,
            'organized' => $this->organizedEvents,
            'past' => $this->pastEvents,
            default => $this->upcomingEvents,
        };

        return view('livewire.network.event-browser', [
            'events' => $events,
        ]);
    }
}
