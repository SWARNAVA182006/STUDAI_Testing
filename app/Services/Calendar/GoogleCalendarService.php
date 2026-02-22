<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use App\Models\ScheduledEvent;
use App\Models\CalendarSyncEvent;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceData;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService implements CalendarProviderInterface
{
    protected GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Get authorization URL for OAuth.
     */
    public function getAuthUrl(string $redirectUri, array $scopes = []): string
    {
        $this->client->setRedirectUri($redirectUri);
        
        $defaultScopes = [
            GoogleCalendar::CALENDAR,
            GoogleCalendar::CALENDAR_EVENTS,
        ];
        
        $this->client->setScopes(array_merge($defaultScopes, $scopes));
        
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $this->client->setRedirectUri($redirectUri);
        $tokens = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($tokens['error'])) {
            throw new \Exception('Failed to exchange code: ' . $tokens['error_description']);
        }

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_in' => $tokens['expires_in'] ?? 3600,
        ];
    }

    /**
     * Refresh the access token.
     */
    public function refreshToken(CalendarConnection $connection): array
    {
        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
        ]);

        if ($this->client->isAccessTokenExpired() && $connection->refresh_token) {
            $tokens = $this->client->fetchAccessTokenWithRefreshToken($connection->refresh_token);

            if (isset($tokens['error'])) {
                throw new \Exception('Failed to refresh token: ' . $tokens['error_description']);
            }

            $connection->updateTokens(
                $tokens['access_token'],
                $tokens['refresh_token'] ?? null,
                $tokens['expires_in'] ?? 3600
            );

            return $tokens;
        }

        return [];
    }

    /**
     * Get authenticated Google Calendar service.
     */
    protected function getService(CalendarConnection $connection): GoogleCalendar
    {
        // Refresh token if needed
        if ($connection->needsTokenRefresh()) {
            $this->refreshToken($connection);
        }

        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
        ]);

        return new GoogleCalendar($this->client);
    }

    /**
     * Get list of calendars.
     */
    public function getCalendars(CalendarConnection $connection): array
    {
        $service = $this->getService($connection);
        $calendarList = $service->calendarList->listCalendarList();

        $calendars = [];
        foreach ($calendarList->getItems() as $calendar) {
            $calendars[] = [
                'id' => $calendar->getId(),
                'summary' => $calendar->getSummary(),
                'description' => $calendar->getDescription(),
                'primary' => $calendar->getPrimary() ?? false,
                'accessRole' => $calendar->getAccessRole(),
            ];
        }

        return $calendars;
    }

    /**
     * Sync an event to the calendar.
     */
    public function syncEvent(CalendarConnection $connection, ScheduledEvent $event): CalendarSyncEvent
    {
        $service = $this->getService($connection);
        $calendarId = $connection->calendar_id ?? 'primary';

        // Check if already synced
        $syncRecord = CalendarSyncEvent::where('connection_id', $connection->id)
            ->where('event_id', $event->id)
            ->first();

        $googleEvent = $this->buildGoogleEvent($event);

        if ($syncRecord) {
            // Update existing event
            try {
                $updatedEvent = $service->events->update(
                    $calendarId,
                    $syncRecord->external_event_id,
                    $googleEvent
                );

                $syncRecord->markSynced();
            } catch (\Exception $e) {
                Log::error('Failed to update Google Calendar event', [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                ]);
                $syncRecord->markFailed();
            }
        } else {
            // Create new event
            try {
                $createdEvent = $service->events->insert($calendarId, $googleEvent, [
                    'conferenceDataVersion' => 1, // Enable Meet link creation
                ]);

                $syncRecord = CalendarSyncEvent::create([
                    'connection_id' => $connection->id,
                    'event_id' => $event->id,
                    'external_event_id' => $createdEvent->getId(),
                    'calendar_id' => $calendarId,
                    'sync_direction' => 'push',
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                    'sync_data' => [
                        'html_link' => $createdEvent->getHtmlLink(),
                        'hangout_link' => $createdEvent->getHangoutLink(),
                    ],
                ]);

                // Update event with Meet link if generated
                if ($createdEvent->getHangoutLink()) {
                    $event->update([
                        'meeting_link' => $createdEvent->getHangoutLink(),
                        'meeting_provider' => 'google_meet',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create Google Calendar event', [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                ]);
                throw $e;
            }
        }

        return $syncRecord;
    }

    /**
     * Build Google Event from ScheduledEvent.
     */
    protected function buildGoogleEvent(ScheduledEvent $event): GoogleEvent
    {
        $googleEvent = new GoogleEvent();
        $googleEvent->setSummary($event->title);
        $googleEvent->setDescription($event->description);

        // Set times
        $start = new EventDateTime();
        $start->setDateTime($event->starts_at->toRfc3339String());
        $start->setTimeZone($event->timezone);
        $googleEvent->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($event->ends_at->toRfc3339String());
        $end->setTimeZone($event->timezone);
        $googleEvent->setEnd($end);

        // Set location
        if ($event->location) {
            $googleEvent->setLocation($event->location);
        }

        // Add attendees
        $attendees = [];
        foreach ($event->participants as $participant) {
            $attendee = new EventAttendee();
            $attendee->setEmail($participant->email);
            if ($participant->name) {
                $attendee->setDisplayName($participant->name);
            }
            $attendee->setResponseStatus($this->mapStatus($participant->status));
            $attendees[] = $attendee;
        }
        $googleEvent->setAttendees($attendees);

        // Add Google Meet if video meeting
        if ($event->meeting_type === 'video' && !$event->meeting_link) {
            $conferenceData = new ConferenceData();
            $createRequest = new CreateConferenceRequest();
            $solutionKey = new ConferenceSolutionKey();
            $solutionKey->setType('hangoutsMeet');
            $createRequest->setConferenceSolutionKey($solutionKey);
            $createRequest->setRequestId(uniqid('meet-'));
            $conferenceData->setCreateRequest($createRequest);
            $googleEvent->setConferenceData($conferenceData);
        }

        return $googleEvent;
    }

    /**
     * Map our status to Google status.
     */
    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'accepted' => 'accepted',
            'declined' => 'declined',
            'tentative' => 'tentative',
            default => 'needsAction',
        };
    }

    /**
     * Delete an event from the calendar.
     */
    public function deleteEvent(CalendarConnection $connection, string $externalEventId): bool
    {
        try {
            $service = $this->getService($connection);
            $calendarId = $connection->calendar_id ?? 'primary';
            $service->events->delete($calendarId, $externalEventId);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete Google Calendar event', [
                'error' => $e->getMessage(),
                'external_event_id' => $externalEventId,
            ]);
            return false;
        }
    }

    /**
     * Get events from the calendar.
     */
    public function getEvents(CalendarConnection $connection, \DateTime $start, \DateTime $end): array
    {
        $service = $this->getService($connection);
        $calendarId = $connection->calendar_id ?? 'primary';

        $events = $service->events->listEvents($calendarId, [
            'timeMin' => $start->format(\DateTime::RFC3339),
            'timeMax' => $end->format(\DateTime::RFC3339),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        $result = [];
        foreach ($events->getItems() as $event) {
            $result[] = [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'description' => $event->getDescription(),
                'start' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                'end' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                'location' => $event->getLocation(),
                'hangout_link' => $event->getHangoutLink(),
            ];
        }

        return $result;
    }
}
