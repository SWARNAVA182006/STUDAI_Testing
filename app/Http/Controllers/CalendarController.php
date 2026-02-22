<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Models\ScheduledEvent;
use App\Models\UserAvailability;
use App\Models\SchedulingLink;
use App\Services\Calendar\CalendarService;
use App\Services\Calendar\GoogleCalendarService;
use App\Services\Calendar\OutlookCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Show calendar dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        $connections = CalendarConnection::where('user_id', $user->id)
            ->get();

        $upcomingEvents = $this->calendarService->getUpcomingEvents($user, 14);

        $availability = UserAvailability::where('user_id', $user->id)
            ->active()
            ->orderBy('day_of_week')
            ->get();

        $schedulingLinks = SchedulingLink::where('user_id', $user->id)
            ->active()
            ->get();

        return view('calendar.index', compact(
            'connections',
            'upcomingEvents',
            'availability',
            'schedulingLinks'
        ));
    }

    /**
     * Get events for calendar view (JSON).
     */
    public function events(Request $request)
    {
        $user = Auth::user();

        $start = Carbon::parse($request->input('start', now()->startOfMonth()));
        $end = Carbon::parse($request->input('end', now()->endOfMonth()));

        $events = ScheduledEvent::forUser($user->id)
            ->inDateRange($start, $end)
            ->with(['participants', 'organizer'])
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->uuid,
                    'title' => $event->title,
                    'start' => $event->starts_at->toIso8601String(),
                    'end' => $event->ends_at->toIso8601String(),
                    'color' => $this->getEventColor($event),
                    'extendedProps' => [
                        'status' => $event->status,
                        'event_type' => $event->event_type,
                        'meeting_type' => $event->meeting_type,
                        'meeting_link' => $event->meeting_link,
                    ],
                ];
            });

        return response()->json($events);
    }

    /**
     * Get event color based on type/status.
     */
    protected function getEventColor(ScheduledEvent $event): string
    {
        if ($event->status === 'cancelled') {
            return '#9CA3AF'; // gray
        }

        return match ($event->event_type) {
            'interview' => '#8B5CF6', // purple
            'meeting' => '#3B82F6',   // blue
            'call' => '#10B981',      // green
            default => '#6366F1',     // indigo
        };
    }

    /**
     * Create a new event.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:interview,meeting,call,other',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'timezone' => 'nullable|string',
            'meeting_type' => 'required|in:in_person,video,phone',
            'meeting_provider' => 'nullable|in:zoom,google_meet,teams,custom',
            'location' => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'participants.*.email' => 'required|email',
            'participants.*.name' => 'nullable|string',
        ]);

        $event = $this->calendarService->createEvent($validated, Auth::user());

        // Generate meeting link if requested
        if ($validated['meeting_type'] === 'video' && !empty($validated['meeting_provider'])) {
            $link = $this->calendarService->generateMeetingLink(
                $validated['meeting_provider'],
                $event
            );

            if ($link) {
                $event->update(['meeting_link' => $link]);
            }
        }

        return response()->json([
            'success' => true,
            'event' => $event,
            'message' => 'Event created successfully',
        ]);
    }

    /**
     * Show event details.
     */
    public function show(string $uuid)
    {
        $event = ScheduledEvent::where('uuid', $uuid)
            ->with(['participants', 'organizer'])
            ->firstOrFail();

        // Check access
        $this->authorize('view', $event);

        return view('calendar.event', compact('event'));
    }

    /**
     * Update an event.
     */
    public function update(Request $request, string $uuid)
    {
        $event = ScheduledEvent::where('uuid', $uuid)->firstOrFail();

        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'sometimes|required|date',
            'ends_at' => 'sometimes|required|date|after:starts_at',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'location' => 'nullable|string|max:255',
        ]);

        $event = $this->calendarService->updateEvent($event, $validated);

        return response()->json([
            'success' => true,
            'event' => $event,
            'message' => 'Event updated successfully',
        ]);
    }

    /**
     * Cancel an event.
     */
    public function cancel(Request $request, string $uuid)
    {
        $event = ScheduledEvent::where('uuid', $uuid)->firstOrFail();

        $this->authorize('update', $event);

        $this->calendarService->cancelEvent($event, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Event cancelled successfully',
        ]);
    }

    /**
     * Respond to an event invitation.
     */
    public function respond(Request $request, string $uuid)
    {
        $event = ScheduledEvent::where('uuid', $uuid)->firstOrFail();
        $user = Auth::user();

        $participant = $event->participants()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->firstOrFail();

        $validated = $request->validate([
            'response' => 'required|in:accepted,declined,tentative',
            'note' => 'nullable|string|max:500',
        ]);

        match ($validated['response']) {
            'accepted' => $participant->accept($validated['note'] ?? null),
            'declined' => $participant->decline($validated['note'] ?? null),
            'tentative' => $participant->tentative($validated['note'] ?? null),
        };

        return response()->json([
            'success' => true,
            'message' => 'Response recorded successfully',
        ]);
    }

    // ========== Calendar Connections ==========

    /**
     * Connect a calendar provider.
     */
    public function connect(string $provider)
    {
        $redirectUri = route('calendar.callback', ['provider' => $provider]);

        $service = match ($provider) {
            'google' => app(GoogleCalendarService::class),
            'outlook' => app(OutlookCalendarService::class),
            default => abort(400, 'Unknown provider'),
        };

        $authUrl = $service->getAuthUrl($redirectUri);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request, string $provider)
    {
        $code = $request->input('code');

        if (!$code) {
            return redirect()->route('calendar.index')
                ->with('error', 'Authorization failed. Please try again.');
        }

        $redirectUri = route('calendar.callback', ['provider' => $provider]);

        try {
            $service = match ($provider) {
                'google' => app(GoogleCalendarService::class),
                'outlook' => app(OutlookCalendarService::class),
                default => throw new \Exception('Unknown provider'),
            };

            $tokens = $service->exchangeCode($code, $redirectUri);

            // Create or update connection
            $connection = CalendarConnection::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'provider' => $provider,
                ],
                [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                    'is_active' => true,
                ]
            );

            // Fetch available calendars
            $calendars = $service->getCalendars($connection);
            $connection->update(['calendars' => $calendars]);

            // Set primary calendar
            $primaryCalendar = collect($calendars)->firstWhere('primary', true);
            if ($primaryCalendar) {
                $connection->update(['calendar_id' => $primaryCalendar['id']]);
            }

            return redirect()->route('calendar.index')
                ->with('success', ucfirst($provider) . ' Calendar connected successfully!');

        } catch (\Exception $e) {
            return redirect()->route('calendar.index')
                ->with('error', 'Failed to connect calendar: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect a calendar.
     */
    public function disconnect(string $provider)
    {
        CalendarConnection::where('user_id', Auth::id())
            ->where('provider', $provider)
            ->delete();

        return redirect()->route('calendar.index')
            ->with('success', ucfirst($provider) . ' Calendar disconnected.');
    }

    // ========== Availability ==========

    /**
     * Show availability settings.
     */
    public function availability()
    {
        $user = Auth::user();

        $availability = UserAvailability::where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->get()
            ->groupBy('day_of_week');

        return view('calendar.availability', compact('availability'));
    }

    /**
     * Update availability settings.
     */
    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|array',
            'schedule.*' => 'array',
            'schedule.*.*.start' => 'required|date_format:H:i',
            'schedule.*.*.end' => 'required|date_format:H:i|after:schedule.*.*.start',
        ]);

        $this->calendarService->setWeeklyAvailability(
            Auth::user(),
            $validated['schedule']
        );

        return redirect()->route('calendar.availability')
            ->with('success', 'Availability updated successfully!');
    }

    // ========== Scheduling Links ==========

    /**
     * List scheduling links.
     */
    public function schedulingLinks()
    {
        $links = SchedulingLink::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('calendar.scheduling-links', compact('links'));
    }

    /**
     * Create a scheduling link.
     */
    public function createSchedulingLink(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'buffer_before' => 'integer|min:0|max:60',
            'buffer_after' => 'integer|min:0|max:60',
            'min_notice_hours' => 'integer|min:1|max:168',
            'max_days_ahead' => 'integer|min:1|max:365',
            'meeting_type' => 'required|in:in_person,video,phone',
            'meeting_provider' => 'nullable|in:zoom,google_meet,teams',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(6);

        $link = SchedulingLink::create($validated);

        return redirect()->route('calendar.scheduling-links')
            ->with('success', 'Scheduling link created!');
    }

    /**
     * Delete a scheduling link.
     */
    public function deleteSchedulingLink(SchedulingLink $link)
    {
        $this->authorize('delete', $link);

        $link->delete();

        return redirect()->route('calendar.scheduling-links')
            ->with('success', 'Scheduling link deleted.');
    }

    // ========== Public Booking ==========

    /**
     * Show public booking page.
     */
    public function showBookingPage(string $slug)
    {
        $link = SchedulingLink::where('slug', $slug)
            ->where('is_active', true)
            ->with('user')
            ->firstOrFail();

        // Get available slots for the next 2 weeks
        $startDate = $link->getEarliestBookableTime();
        $endDate = min($link->getLatestBookableTime(), now()->addDays(14));

        $availableSlots = $this->calendarService->getAvailableSlots(
            $link->user,
            $startDate,
            $endDate,
            $link->duration_minutes,
            $link->buffer_before + $link->buffer_after
        );

        return view('calendar.booking', compact('link', 'availableSlots'));
    }

    /**
     * Book a slot.
     */
    public function book(Request $request, string $slug)
    {
        $link = SchedulingLink::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'starts_at' => 'required|date|after:now',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $event = $this->calendarService->bookFromLink($link, $validated);

            return redirect()->route('calendar.booking.confirmation', [
                'slug' => $slug,
                'event' => $event->uuid,
            ])->with('success', 'Booking confirmed!');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show booking confirmation.
     */
    public function bookingConfirmation(string $slug, string $eventUuid)
    {
        $link = SchedulingLink::where('slug', $slug)->firstOrFail();
        $event = ScheduledEvent::where('uuid', $eventUuid)->firstOrFail();

        return view('calendar.booking-confirmation', compact('link', 'event'));
    }

    /**
     * Get available slots for a date (AJAX).
     */
    public function getAvailableSlots(Request $request, string $slug)
    {
        $link = SchedulingLink::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $date = Carbon::parse($request->input('date'));

        $slots = $this->calendarService->getAvailableSlots(
            $link->user,
            $date->startOfDay(),
            $date->endOfDay(),
            $link->duration_minutes,
            $link->buffer_before + $link->buffer_after
        );

        return response()->json($slots[$date->toDateString()] ?? []);
    }
}
