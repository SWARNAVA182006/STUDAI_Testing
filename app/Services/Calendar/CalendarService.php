<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use App\Models\ScheduledEvent;
use App\Models\EventParticipant;
use App\Models\UserAvailability;
use App\Models\AvailabilityOverride;
use App\Models\SchedulingLink;
use App\Models\EventReminder;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarService
{
    /**
     * Create a new scheduled event.
     */
    public function createEvent(array $data, User $organizer): ScheduledEvent
    {
        return DB::transaction(function () use ($data, $organizer) {
            $event = ScheduledEvent::create([
                'organizer_id' => $organizer->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'event_type' => $data['event_type'] ?? 'meeting',
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'timezone' => $data['timezone'] ?? $organizer->timezone ?? 'UTC',
                'status' => $data['status'] ?? 'confirmed',
                'location' => $data['location'] ?? null,
                'meeting_type' => $data['meeting_type'] ?? 'video',
                'meeting_link' => $data['meeting_link'] ?? null,
                'meeting_provider' => $data['meeting_provider'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Add organizer as participant
            EventParticipant::create([
                'event_id' => $event->id,
                'user_id' => $organizer->id,
                'email' => $organizer->email,
                'name' => $organizer->name,
                'role' => 'organizer',
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            // Add other participants
            if (!empty($data['participants'])) {
                foreach ($data['participants'] as $participant) {
                    EventParticipant::create([
                        'event_id' => $event->id,
                        'user_id' => $participant['user_id'] ?? null,
                        'email' => $participant['email'],
                        'name' => $participant['name'] ?? null,
                        'role' => $participant['role'] ?? 'attendee',
                        'status' => 'pending',
                    ]);
                }
            }

            // Create default reminders
            $this->createDefaultReminders($event, $organizer);

            // Sync to connected calendars
            $this->syncEventToCalendars($event);

            return $event;
        });
    }

    /**
     * Update an event.
     */
    public function updateEvent(ScheduledEvent $event, array $data): ScheduledEvent
    {
        $event->update($data);

        // Re-sync to calendars
        $this->syncEventToCalendars($event);

        return $event->fresh();
    }

    /**
     * Cancel an event.
     */
    public function cancelEvent(ScheduledEvent $event, ?string $reason = null): void
    {
        $event->cancel();

        // Notify participants
        foreach ($event->participants as $participant) {
            if ($participant->user_id !== $event->organizer_id) {
                // TODO: Send cancellation notification
            }
        }

        // Update synced calendars
        $this->syncEventToCalendars($event);
    }

    /**
     * Get user's upcoming events.
     */
    public function getUpcomingEvents(User $user, int $days = 30): Collection
    {
        return ScheduledEvent::forUser($user->id)
            ->upcoming()
            ->where('starts_at', '<=', now()->addDays($days))
            ->with(['participants', 'organizer'])
            ->get();
    }

    /**
     * Get user's availability for a date range.
     */
    public function getAvailableSlots(
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        int $durationMinutes = 30,
        int $bufferMinutes = 0
    ): array {
        $availableSlots = [];

        // Get user's weekly availability
        $weeklyAvailability = UserAvailability::where('user_id', $user->id)
            ->active()
            ->get()
            ->groupBy('day_of_week');

        // Get overrides for the date range
        $overrides = AvailabilityOverride::where('user_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('date');

        // Get existing events
        $existingEvents = ScheduledEvent::forUser($user->id)
            ->inDateRange($startDate, $endDate)
            ->where('status', '!=', 'cancelled')
            ->get();

        // Iterate through each day
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeek;
            $dateString = $date->toDateString();

            // Check for override
            if (isset($overrides[$dateString])) {
                $override = $overrides[$dateString];
                if (!$override->is_available) {
                    continue; // Day is blocked
                }
                // Use override times
                $daySlots = $this->generateTimeSlots(
                    $date,
                    $override->start_time,
                    $override->end_time,
                    $durationMinutes,
                    $bufferMinutes
                );
            } elseif (isset($weeklyAvailability[$dayOfWeek])) {
                // Use weekly availability
                $daySlots = [];
                foreach ($weeklyAvailability[$dayOfWeek] as $availability) {
                    $slots = $this->generateTimeSlots(
                        $date,
                        $availability->start_time,
                        $availability->end_time,
                        $durationMinutes,
                        $bufferMinutes
                    );
                    $daySlots = array_merge($daySlots, $slots);
                }
            } else {
                continue; // No availability for this day
            }

            // Remove slots that conflict with existing events
            $daySlots = $this->filterConflictingSlots($daySlots, $existingEvents, $durationMinutes);

            if (!empty($daySlots)) {
                $availableSlots[$dateString] = $daySlots;
            }
        }

        return $availableSlots;
    }

    /**
     * Generate time slots for a day.
     */
    protected function generateTimeSlots(
        Carbon $date,
        string $startTime,
        string $endTime,
        int $durationMinutes,
        int $bufferMinutes
    ): array {
        $slots = [];
        $slotStart = $date->copy()->setTimeFromTimeString($startTime);
        $dayEnd = $date->copy()->setTimeFromTimeString($endTime);

        while ($slotStart->copy()->addMinutes($durationMinutes)->lte($dayEnd)) {
            // Skip past times
            if ($slotStart->isFuture()) {
                $slots[] = [
                    'start' => $slotStart->toIso8601String(),
                    'end' => $slotStart->copy()->addMinutes($durationMinutes)->toIso8601String(),
                ];
            }

            $slotStart->addMinutes($durationMinutes + $bufferMinutes);
        }

        return $slots;
    }

    /**
     * Filter out slots that conflict with existing events.
     */
    protected function filterConflictingSlots(array $slots, Collection $events, int $duration): array
    {
        return array_filter($slots, function ($slot) use ($events, $duration) {
            $slotStart = Carbon::parse($slot['start']);
            $slotEnd = Carbon::parse($slot['end']);

            foreach ($events as $event) {
                // Check for overlap
                if ($slotStart < $event->ends_at && $slotEnd > $event->starts_at) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Set user's weekly availability.
     */
    public function setWeeklyAvailability(User $user, array $schedule): void
    {
        // Clear existing availability
        UserAvailability::where('user_id', $user->id)->delete();

        foreach ($schedule as $day => $slots) {
            foreach ($slots as $slot) {
                UserAvailability::create([
                    'user_id' => $user->id,
                    'day_of_week' => $day,
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'timezone' => $user->timezone ?? 'UTC',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Block specific date/time.
     */
    public function blockTime(User $user, Carbon $date, ?string $startTime = null, ?string $endTime = null, ?string $reason = null): void
    {
        AvailabilityOverride::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'is_available' => false,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reason' => $reason,
        ]);
    }

    /**
     * Create default reminders for an event.
     */
    protected function createDefaultReminders(ScheduledEvent $event, User $user): void
    {
        $reminderTimes = [
            1440, // 24 hours
            60,   // 1 hour
            15,   // 15 minutes
        ];

        foreach ($reminderTimes as $minutes) {
            $scheduledAt = $event->starts_at->copy()->subMinutes($minutes);

            // Only create if in the future
            if ($scheduledAt->isFuture()) {
                EventReminder::create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'minutes_before' => $minutes,
                    'channel' => 'email',
                    'scheduled_at' => $scheduledAt,
                ]);
            }
        }
    }

    /**
     * Sync event to connected calendars.
     */
    public function syncEventToCalendars(ScheduledEvent $event): void
    {
        $connections = CalendarConnection::where('user_id', $event->organizer_id)
            ->active()
            ->get();

        foreach ($connections as $connection) {
            try {
                $provider = $this->getCalendarProvider($connection->provider);
                $provider->syncEvent($connection, $event);
            } catch (\Exception $e) {
                Log::error('Failed to sync event to calendar', [
                    'event_id' => $event->id,
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get calendar provider service (public accessor).
     */
    public function getProvider(string $provider): CalendarProviderInterface
    {
        return $this->getCalendarProvider($provider);
    }

    /**
     * Get calendar provider service.
     */
    protected function getCalendarProvider(string $provider): CalendarProviderInterface
    {
        return match ($provider) {
            'google' => app(GoogleCalendarService::class),
            'outlook' => app(OutlookCalendarService::class),
            'apple' => app(AppleCalendarService::class),
            default => throw new \Exception("Unknown calendar provider: {$provider}"),
        };
    }

    /**
     * Book a slot from scheduling link.
     */
    public function bookFromLink(SchedulingLink $link, array $data): ScheduledEvent
    {
        // Validate the slot is still available
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = $startsAt->copy()->addMinutes($link->duration_minutes);

        // Check for conflicts
        $hasConflict = ScheduledEvent::forUser($link->user_id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->whereBetween('starts_at', [$startsAt, $endsAt])
                  ->orWhereBetween('ends_at', [$startsAt, $endsAt]);
            })
            ->exists();

        if ($hasConflict) {
            throw new \Exception('This time slot is no longer available.');
        }

        // Create the event
        $event = $this->createEvent([
            'title' => $link->title . ' with ' . $data['name'],
            'description' => $link->description,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'meeting_type' => $link->meeting_type,
            'meeting_provider' => $link->meeting_provider,
            'status' => $link->require_confirmation ? 'pending' : 'confirmed',
            'participants' => [
                [
                    'email' => $data['email'],
                    'name' => $data['name'],
                ],
            ],
        ], $link->user);

        // Increment booking count
        $link->incrementBookings();

        return $event;
    }

    /**
     * Generate a meeting link.
     */
    public function generateMeetingLink(string $provider, ScheduledEvent $event): ?string
    {
        try {
            return match ($provider) {
                'google_meet' => $this->generateGoogleMeetLink($event),
                'zoom' => $this->generateZoomLink($event),
                'teams' => $this->generateTeamsLink($event),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Failed to generate meeting link', [
                'provider' => $provider,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate Google Meet link.
     */
    protected function generateGoogleMeetLink(ScheduledEvent $event): ?string
    {
        // This would integrate with Google Calendar API to create a Meet link
        // For now, return null - will be implemented in GoogleCalendarService
        return null;
    }

    /**
     * Generate Zoom link.
     */
    protected function generateZoomLink(ScheduledEvent $event): ?string
    {
        // This would integrate with Zoom API
        // Requires Zoom OAuth app setup
        return null;
    }

    /**
     * Generate Microsoft Teams link.
     */
    protected function generateTeamsLink(ScheduledEvent $event): ?string
    {
        // This would integrate with Microsoft Graph API
        return null;
    }
}
