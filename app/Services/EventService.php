<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EventRsvp;
use App\Models\Group;
use App\Models\NetworkEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EventService
{
    /**
     * Create a new networking event.
     */
    public function createEvent(
        User $organizer,
        array $eventData,
        ?Group $group = null
    ): NetworkEvent {
        return DB::transaction(function () use ($organizer, $eventData, $group) {
            $event = NetworkEvent::create([
                'title' => $eventData['title'],
                'slug' => $eventData['slug'] ?? Str::slug($eventData['title']) . '-' . Str::random(6),
                'description' => $eventData['description'] ?? null,
                'cover_image' => $eventData['cover_image'] ?? null,
                'type' => $eventData['type'] ?? 'virtual',
                'location' => $eventData['location'] ?? null,
                'virtual_link' => $eventData['virtual_link'] ?? null,
                'starts_at' => $eventData['starts_at'],
                'ends_at' => $eventData['ends_at'] ?? null,
                'timezone' => $eventData['timezone'] ?? 'UTC',
                'organizer_id' => $organizer->id,
                'group_id' => $group?->id,
                'capacity' => $eventData['capacity'] ?? null,
                'requires_approval' => $eventData['requires_approval'] ?? false,
                'tags' => $eventData['tags'] ?? null,
                'settings' => $eventData['settings'] ?? null,
            ]);

            // Automatically RSVP the organizer as going
            $this->rsvp($event, $organizer, 'going');

            return $event;
        });
    }

    /**
     * Update an event.
     */
    public function updateEvent(NetworkEvent $event, array $data): NetworkEvent
    {
        $event->update($data);
        return $event->fresh();
    }

    /**
     * Delete an event.
     */
    public function deleteEvent(NetworkEvent $event): bool
    {
        return $event->delete();
    }

    /**
     * RSVP to an event.
     */
    public function rsvp(NetworkEvent $event, User $user, string $status = 'going'): EventRsvp
    {
        $existingRsvp = $event->getRsvpForUser($user);

        if ($existingRsvp) {
            $oldStatus = $existingRsvp->status;
            $existingRsvp->update(['status' => $status]);

            // Update attendee count
            if ($oldStatus === 'going' && $status !== 'going') {
                $event->decrementAttendeeCount();
            } elseif ($oldStatus !== 'going' && $status === 'going') {
                $event->incrementAttendeeCount();
            }

            return $existingRsvp->fresh();
        }

        $rsvp = EventRsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => $status,
        ]);

        if ($status === 'going') {
            $event->incrementAttendeeCount();
        }

        return $rsvp;
    }

    /**
     * Cancel RSVP to an event.
     */
    public function cancelRsvp(NetworkEvent $event, User $user): bool
    {
        $rsvp = $event->getRsvpForUser($user);

        if (!$rsvp) {
            return false;
        }

        if ($rsvp->isGoing()) {
            $event->decrementAttendeeCount();
        }

        return $rsvp->delete();
    }

    /**
     * Get upcoming events for a user (based on connections, groups, etc.).
     */
    public function getUpcomingEvents(
        User $user,
        int $perPage = 15,
        ?string $type = null,
        bool $includeGroupEvents = true
    ): LengthAwarePaginator {
        $query = NetworkEvent::query()
            ->with(['organizer', 'group', 'rsvps' => fn($q) => $q->where('user_id', $user->id)])
            ->upcoming();

        if ($type) {
            $query->where('type', $type);
        }

        if ($includeGroupEvents) {
            $userGroupIds = $user->groups()->pluck('groups.id');
            $query->where(function ($q) use ($userGroupIds) {
                $q->whereNull('group_id')
                    ->orWhereIn('group_id', $userGroupIds);
            });
        } else {
            $query->whereNull('group_id');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get featured events.
     */
    public function getFeaturedEvents(int $limit = 10): Collection
    {
        return NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->featured()
            ->upcoming()
            ->limit($limit)
            ->get();
    }

    /**
     * Get events by group.
     */
    public function getGroupEvents(Group $group, int $perPage = 15): LengthAwarePaginator
    {
        return NetworkEvent::query()
            ->with(['organizer', 'rsvps'])
            ->where('group_id', $group->id)
            ->upcoming()
            ->paginate($perPage);
    }

    /**
     * Get events organized by a user.
     */
    public function getUserOrganizedEvents(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return NetworkEvent::query()
            ->with(['group', 'rsvps'])
            ->where('organizer_id', $user->id)
            ->orderBy('starts_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get events user is attending.
     */
    public function getUserAttendingEvents(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->whereHas('rsvps', fn($q) => $q->where('user_id', $user->id)->where('status', 'going'))
            ->upcoming()
            ->paginate($perPage);
    }

    /**
     * Get events user is interested in.
     */
    public function getUserInterestedEvents(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->whereHas('rsvps', fn($q) => $q->where('user_id', $user->id)->where('status', 'interested'))
            ->upcoming()
            ->paginate($perPage);
    }

    /**
     * Get past events for a user.
     */
    public function getPastEvents(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->whereHas('rsvps', fn($q) => $q->where('user_id', $user->id))
            ->past()
            ->paginate($perPage);
    }

    /**
     * Search events.
     */
    public function searchEvents(
        string $query,
        ?string $type = null,
        ?string $location = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $builder = NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->upcoming();

        if ($type) {
            $builder->where('type', $type);
        }

        if ($location) {
            $builder->where('location', 'like', "%{$location}%");
        }

        return $builder->paginate($perPage);
    }

    /**
     * Get event attendees.
     */
    public function getEventAttendees(NetworkEvent $event, int $perPage = 20): LengthAwarePaginator
    {
        return EventRsvp::query()
            ->with('user.profile')
            ->where('event_id', $event->id)
            ->where('status', 'going')
            ->paginate($perPage);
    }

    /**
     * Get suggested events based on user interests.
     */
    public function getSuggestedEvents(User $user, int $limit = 5): Collection
    {
        // Get user's industries and skills for matching
        $userProfile = $user->profile;
        $industries = $userProfile?->industries ?? [];
        $skills = $userProfile?->skills ?? [];

        return NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->upcoming()
            ->where('organizer_id', '!=', $user->id)
            ->whereDoesntHave('rsvps', fn($q) => $q->where('user_id', $user->id))
            ->where(function ($q) use ($industries, $skills) {
                // Match by tags that contain user's skills or industries
                if (!empty($industries)) {
                    $q->orWhere(function ($subQ) use ($industries) {
                        foreach ($industries as $industry) {
                            $subQ->orWhereJsonContains('tags', $industry);
                        }
                    });
                }
                if (!empty($skills)) {
                    $q->orWhere(function ($subQ) use ($skills) {
                        foreach ($skills as $skill) {
                            $subQ->orWhereJsonContains('tags', $skill);
                        }
                    });
                }
            })
            ->orderBy('starts_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get events happening soon (within the next 24 hours).
     */
    public function getEventsHappeningSoon(User $user): Collection
    {
        return NetworkEvent::query()
            ->with(['organizer', 'group'])
            ->whereHas('rsvps', fn($q) => $q->where('user_id', $user->id)->where('status', 'going'))
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->orderBy('starts_at', 'asc')
            ->get();
    }

    /**
     * Get event statistics for a user.
     */
    public function getEventStats(User $user): array
    {
        $organizedCount = NetworkEvent::where('organizer_id', $user->id)->count();
        $attendedCount = EventRsvp::where('user_id', $user->id)
            ->where('status', 'going')
            ->whereHas('event', fn($q) => $q->where('starts_at', '<', now()))
            ->count();
        $upcomingCount = EventRsvp::where('user_id', $user->id)
            ->where('status', 'going')
            ->whereHas('event', fn($q) => $q->where('starts_at', '>', now()))
            ->count();

        return [
            'organized' => $organizedCount,
            'attended' => $attendedCount,
            'upcoming' => $upcomingCount,
        ];
    }

    /**
     * Duplicate an event (for recurring events).
     */
    public function duplicateEvent(NetworkEvent $event, array $overrides = []): NetworkEvent
    {
        $newData = array_merge([
            'title' => $event->title,
            'description' => $event->description,
            'cover_image' => $event->cover_image,
            'type' => $event->type,
            'location' => $event->location,
            'virtual_link' => $event->virtual_link,
            'timezone' => $event->timezone,
            'capacity' => $event->capacity,
            'requires_approval' => $event->requires_approval,
            'tags' => $event->tags,
            'settings' => $event->settings,
        ], $overrides);

        return $this->createEvent(
            $event->organizer,
            $newData,
            $event->group
        );
    }
}
