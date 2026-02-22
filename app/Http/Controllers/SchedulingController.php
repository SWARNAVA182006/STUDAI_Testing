<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SchedulingLink;
use App\Models\ScheduledEvent;
use App\Models\EventParticipant;
use App\Models\UserAvailability;
use App\Models\CalendarConnection;
use App\Services\Calendar\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * SchedulingController handles public booking pages.
 * 
 * This controller manages the public-facing scheduling interface
 * where external users can book meetings through scheduling links.
 */
class SchedulingController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Display the public booking page for a scheduling link.
     */
    public function show(SchedulingLink $link): View
    {
        // Check if link is active
        if (!$link->is_active) {
            abort(404, 'This scheduling link is no longer active.');
        }

        // Check expiration
        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(404, 'This scheduling link has expired.');
        }

        // Check max uses
        if ($link->max_uses && $link->times_used >= $link->max_uses) {
            abort(404, 'This scheduling link has reached its maximum bookings.');
        }

        // Load the user with their profile
        $link->load('user');
        $user = $link->user;

        return view('schedule.show', [
            'link' => $link,
            'user' => $user,
            'eventTypes' => $this->getEventTypes($link),
        ]);
    }

    /**
     * Get available time slots for a scheduling link on a specific date.
     */
    public function getAvailableTimes(SchedulingLink $link, Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'timezone' => 'nullable|string|timezone',
        ]);

        $date = Carbon::parse($request->date);
        $timezone = $request->timezone ?? $link->timezone ?? 'UTC';

        // Get user's availability for this day
        $dayOfWeek = strtolower($date->format('l'));
        $availability = UserAvailability::where('user_id', $link->user_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get();

        if ($availability->isEmpty()) {
            return response()->json([
                'available_slots' => [],
                'message' => 'No availability on this day.',
            ]);
        }

        // Get existing events for this day from all connected calendars
        $existingEvents = $this->getBlockedTimeSlots($link->user_id, $date);

        // Generate available slots based on availability and duration
        $slots = $this->generateAvailableSlots(
            $availability,
            $existingEvents,
            $date,
            $link->duration_minutes,
            $link->buffer_before ?? 0,
            $link->buffer_after ?? 0,
            $timezone
        );

        return response()->json([
            'date' => $date->toDateString(),
            'timezone' => $timezone,
            'duration_minutes' => $link->duration_minutes,
            'available_slots' => $slots,
        ]);
    }

    /**
     * Book a meeting through a scheduling link.
     */
    public function book(SchedulingLink $link, Request $request): JsonResponse
    {
        // Validate booking request
        $validated = $request->validate([
            'start_time' => 'required|date|after:now',
            'attendee_name' => 'required|string|max:255',
            'attendee_email' => 'required|email|max:255',
            'attendee_phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'timezone' => 'nullable|string|timezone',
        ]);

        // Check if link is still valid
        if (!$link->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This scheduling link is no longer active.',
            ], 400);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This scheduling link has expired.',
            ], 400);
        }

        if ($link->max_uses && $link->times_used >= $link->max_uses) {
            return response()->json([
                'success' => false,
                'message' => 'This scheduling link has reached its maximum bookings.',
            ], 400);
        }

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = $startTime->copy()->addMinutes($link->duration_minutes);
        $timezone = $validated['timezone'] ?? $link->timezone ?? 'UTC';

        // Verify the slot is still available
        $existingEvents = $this->getBlockedTimeSlots($link->user_id, $startTime);
        if ($this->isTimeSlotBlocked($startTime, $endTime, $existingEvents)) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot is no longer available. Please select another time.',
            ], 409);
        }

        // Generate meeting link if configured
        $meetingLink = null;
        $conferenceData = null;
        if ($link->meeting_type && $link->meeting_type !== 'in_person') {
            $meetingResult = $this->generateMeetingLink($link);
            $meetingLink = $meetingResult['link'] ?? null;
            $conferenceData = $meetingResult['data'] ?? null;
        }

        // Create the scheduled event
        $event = ScheduledEvent::create([
            'user_id' => $link->user_id,
            'scheduling_link_id' => $link->id,
            'title' => $link->name . ' with ' . $validated['attendee_name'],
            'description' => $validated['notes'] ?? '',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'timezone' => $timezone,
            'location' => $link->location,
            'meeting_link' => $meetingLink,
            'meeting_provider' => $link->meeting_type,
            'conference_data' => $conferenceData,
            'event_type' => $link->event_type ?? 'meeting',
            'status' => 'confirmed',
            'is_all_day' => false,
            'metadata' => [
                'booked_via' => 'scheduling_link',
                'scheduling_link_id' => $link->id,
                'attendee_notes' => $validated['notes'] ?? null,
            ],
        ]);

        // Add participant (the person booking)
        EventParticipant::create([
            'scheduled_event_id' => $event->id,
            'name' => $validated['attendee_name'],
            'email' => $validated['attendee_email'],
            'phone' => $validated['attendee_phone'] ?? null,
            'role' => 'attendee',
            'rsvp_status' => 'accepted',
            'is_organizer' => false,
        ]);

        // Add the host as organizer
        $host = $link->user;
        EventParticipant::create([
            'scheduled_event_id' => $event->id,
            'user_id' => $host->id,
            'name' => $host->name,
            'email' => $host->email,
            'role' => 'organizer',
            'rsvp_status' => 'accepted',
            'is_organizer' => true,
        ]);

        // Increment times used
        $link->increment('times_used');

        // Sync to connected calendars
        $this->syncEventToCalendars($event, $link->user_id);

        // Queue reminder notifications
        $this->queueReminders($event, $link);

        // Send confirmation emails
        // TODO: Queue email notifications to both parties

        return response()->json([
            'success' => true,
            'message' => 'Meeting booked successfully!',
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'start_time' => $event->start_time->toIso8601String(),
                'end_time' => $event->end_time->toIso8601String(),
                'timezone' => $event->timezone,
                'meeting_link' => $event->meeting_link,
                'location' => $event->location,
            ],
            'confirmation_url' => route('schedule.confirmation', ['event' => $event->id]),
        ]);
    }

    /**
     * Show booking confirmation page.
     */
    public function confirmation(int $eventId): View
    {
        $event = ScheduledEvent::with(['participants', 'schedulingLink.user'])
            ->findOrFail($eventId);

        return view('schedule.confirmation', [
            'event' => $event,
            'host' => $event->schedulingLink?->user ?? $event->user,
            'attendee' => $event->participants->where('is_organizer', false)->first(),
        ]);
    }

    /**
     * Get event types for a scheduling link.
     */
    protected function getEventTypes(SchedulingLink $link): array
    {
        return [
            [
                'id' => $link->id,
                'name' => $link->name,
                'duration' => $link->duration_minutes,
                'description' => $link->description,
                'meeting_type' => $link->meeting_type,
                'location' => $link->location,
            ],
        ];
    }

    /**
     * Get blocked time slots from all calendar sources.
     */
    protected function getBlockedTimeSlots(int $userId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get events from our database
        $localEvents = ScheduledEvent::where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('start_time', [$startOfDay, $endOfDay])
                    ->orWhereBetween('end_time', [$startOfDay, $endOfDay])
                    ->orWhere(function ($q) use ($startOfDay, $endOfDay) {
                        $q->where('start_time', '<=', $startOfDay)
                            ->where('end_time', '>=', $endOfDay);
                    });
            })
            ->get(['start_time', 'end_time'])
            ->map(fn($e) => [
                'start' => $e->start_time,
                'end' => $e->end_time,
            ])
            ->toArray();

        // Get busy times from connected calendars
        $connections = CalendarConnection::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $externalEvents = [];
        foreach ($connections as $connection) {
            try {
                $provider = $this->calendarService->getProvider($connection->provider);
                $busyTimes = $provider->getFreeBusyInfo(
                    $connection,
                    $startOfDay,
                    $endOfDay
                );
                $externalEvents = array_merge($externalEvents, $busyTimes);
            } catch (\Exception $e) {
                // Log but don't fail - continue with local events
                \Log::warning("Failed to get free/busy from {$connection->provider}: " . $e->getMessage());
            }
        }

        return array_merge($localEvents, $externalEvents);
    }

    /**
     * Generate available time slots based on availability and blocked times.
     */
    protected function generateAvailableSlots(
        $availability,
        array $blockedSlots,
        Carbon $date,
        int $durationMinutes,
        int $bufferBefore,
        int $bufferAfter,
        string $timezone
    ): array {
        $slots = [];
        $now = Carbon::now($timezone);

        foreach ($availability as $avail) {
            // Parse availability times for the specific date
            $startTime = Carbon::parse($date->toDateString() . ' ' . $avail->start_time, $timezone);
            $endTime = Carbon::parse($date->toDateString() . ' ' . $avail->end_time, $timezone);

            // Skip if end time is in the past
            if ($endTime->lessThan($now)) {
                continue;
            }

            // Adjust start time if it's in the past
            if ($startTime->lessThan($now)) {
                // Round up to next slot increment
                $startTime = $now->copy()->addMinutes(30 - ($now->minute % 30));
            }

            // Generate slots at configured intervals
            $slotInterval = 30; // Default 30-minute intervals
            $current = $startTime->copy();

            while ($current->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($endTime)) {
                $slotEnd = $current->copy()->addMinutes($durationMinutes);

                // Check if slot (with buffers) is blocked
                $bufferedStart = $current->copy()->subMinutes($bufferBefore);
                $bufferedEnd = $slotEnd->copy()->addMinutes($bufferAfter);

                if (!$this->isTimeSlotBlocked($bufferedStart, $bufferedEnd, $blockedSlots)) {
                    $slots[] = [
                        'start' => $current->toIso8601String(),
                        'end' => $slotEnd->toIso8601String(),
                        'display' => $current->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                    ];
                }

                $current->addMinutes($slotInterval);
            }
        }

        return $slots;
    }

    /**
     * Check if a time slot overlaps with any blocked slots.
     */
    protected function isTimeSlotBlocked(Carbon $start, Carbon $end, array $blockedSlots): bool
    {
        foreach ($blockedSlots as $blocked) {
            $blockedStart = $blocked['start'] instanceof Carbon ? $blocked['start'] : Carbon::parse($blocked['start']);
            $blockedEnd = $blocked['end'] instanceof Carbon ? $blocked['end'] : Carbon::parse($blocked['end']);

            // Check for overlap
            if ($start->lessThan($blockedEnd) && $end->greaterThan($blockedStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a meeting link based on the configured provider.
     */
    protected function generateMeetingLink(SchedulingLink $link): array
    {
        switch ($link->meeting_type) {
            case 'google_meet':
                return $this->generateGoogleMeetLink($link);
            case 'zoom':
                return $this->generateZoomLink($link);
            case 'teams':
                return $this->generateTeamsLink($link);
            default:
                return ['link' => null, 'data' => null];
        }
    }

    /**
     * Generate a Google Meet link.
     */
    protected function generateGoogleMeetLink(SchedulingLink $link): array
    {
        // Google Meet links are typically generated when creating calendar events
        // For now, return a placeholder that will be replaced during calendar sync
        return [
            'link' => null,
            'data' => ['provider' => 'google_meet', 'generate_on_sync' => true],
        ];
    }

    /**
     * Generate a Zoom meeting link.
     */
    protected function generateZoomLink(SchedulingLink $link): array
    {
        // TODO: Implement Zoom API integration
        // This would use the Zoom API to create an instant meeting
        return [
            'link' => null,
            'data' => ['provider' => 'zoom', 'status' => 'pending_integration'],
        ];
    }

    /**
     * Generate a Microsoft Teams meeting link.
     */
    protected function generateTeamsLink(SchedulingLink $link): array
    {
        // TODO: Implement Microsoft Graph API for Teams meetings
        return [
            'link' => null,
            'data' => ['provider' => 'teams', 'status' => 'pending_integration'],
        ];
    }

    /**
     * Sync the created event to all connected calendars.
     */
    protected function syncEventToCalendars(ScheduledEvent $event, int $userId): void
    {
        $connections = CalendarConnection::where('user_id', $userId)
            ->where('is_active', true)
            ->where('sync_direction', '!=', 'from_provider')
            ->get();

        foreach ($connections as $connection) {
            try {
                $provider = $this->calendarService->getProvider($connection->provider);
                $externalId = $provider->createEvent($connection, [
                    'title' => $event->title,
                    'description' => $event->description,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'timezone' => $event->timezone,
                    'location' => $event->location,
                    'attendees' => $event->participants->map(fn($p) => [
                        'email' => $p->email,
                        'name' => $p->name,
                    ])->toArray(),
                    'conferencing' => $event->meeting_provider,
                ]);

                // Store the external calendar reference
                $event->syncEvents()->create([
                    'calendar_connection_id' => $connection->id,
                    'external_event_id' => $externalId,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                ]);

                // Update meeting link if generated by calendar
                if (isset($externalId['meeting_link']) && !$event->meeting_link) {
                    $event->update(['meeting_link' => $externalId['meeting_link']]);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to sync event to {$connection->provider}: " . $e->getMessage());
            }
        }
    }

    /**
     * Queue reminder notifications for the event.
     */
    protected function queueReminders(ScheduledEvent $event, SchedulingLink $link): void
    {
        $reminderTimes = $link->reminder_times ?? [24 * 60, 60]; // Default: 24 hours and 1 hour before

        foreach ($reminderTimes as $minutesBefore) {
            $reminderTime = $event->start_time->copy()->subMinutes($minutesBefore);
            
            if ($reminderTime->greaterThan(now())) {
                $event->reminders()->create([
                    'reminder_type' => 'email',
                    'minutes_before' => $minutesBefore,
                    'scheduled_at' => $reminderTime,
                    'is_sent' => false,
                ]);
            }
        }
    }
}
