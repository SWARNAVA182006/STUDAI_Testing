<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use App\Models\ScheduledEvent;
use App\Models\CalendarSyncEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutlookCalendarService implements CalendarProviderInterface
{
    protected string $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    protected string $tokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    protected string $graphUrl = 'https://graph.microsoft.com/v1.0';

    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->clientId = config('services.microsoft.client_id', '');
        $this->clientSecret = config('services.microsoft.client_secret', '');
    }

    /**
     * Get authorization URL for OAuth.
     */
    public function getAuthUrl(string $redirectUri, array $scopes = []): string
    {
        $defaultScopes = [
            'openid',
            'profile',
            'email',
            'offline_access',
            'Calendars.ReadWrite',
            'OnlineMeetings.ReadWrite',
        ];

        $params = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', array_merge($defaultScopes, $scopes)),
            'response_mode' => 'query',
        ]);

        return $this->authUrl . '?' . $params;
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange code: ' . $response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? 3600,
        ];
    }

    /**
     * Refresh the access token.
     */
    public function refreshToken(CalendarConnection $connection): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to refresh token: ' . $response->body());
        }

        $data = $response->json();

        $connection->updateTokens(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            $data['expires_in'] ?? 3600
        );

        return $data;
    }

    /**
     * Make authenticated API request.
     */
    protected function makeRequest(CalendarConnection $connection, string $method, string $endpoint, array $data = [])
    {
        // Refresh token if needed
        if ($connection->needsTokenRefresh()) {
            $this->refreshToken($connection);
        }

        $request = Http::withToken($connection->access_token)
            ->withHeaders(['Content-Type' => 'application/json']);

        $url = $this->graphUrl . $endpoint;

        $response = match ($method) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PATCH' => $request->patch($url, $data),
            'DELETE' => $request->delete($url),
            default => throw new \Exception("Unknown method: {$method}"),
        };

        if (!$response->successful()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get list of calendars.
     */
    public function getCalendars(CalendarConnection $connection): array
    {
        $data = $this->makeRequest($connection, 'GET', '/me/calendars');

        $calendars = [];
        foreach ($data['value'] ?? [] as $calendar) {
            $calendars[] = [
                'id' => $calendar['id'],
                'summary' => $calendar['name'],
                'description' => null,
                'primary' => $calendar['isDefaultCalendar'] ?? false,
                'accessRole' => $calendar['canEdit'] ? 'writer' : 'reader',
            ];
        }

        return $calendars;
    }

    /**
     * Sync an event to the calendar.
     */
    public function syncEvent(CalendarConnection $connection, ScheduledEvent $event): CalendarSyncEvent
    {
        $calendarId = $connection->calendar_id ?? 'primary';
        $endpoint = $calendarId === 'primary' 
            ? '/me/calendar/events' 
            : "/me/calendars/{$calendarId}/events";

        // Check if already synced
        $syncRecord = CalendarSyncEvent::where('connection_id', $connection->id)
            ->where('event_id', $event->id)
            ->first();

        $outlookEvent = $this->buildOutlookEvent($event);

        if ($syncRecord) {
            // Update existing event
            try {
                $updated = $this->makeRequest(
                    $connection,
                    'PATCH',
                    $endpoint . '/' . $syncRecord->external_event_id,
                    $outlookEvent
                );

                $syncRecord->markSynced();
            } catch (\Exception $e) {
                Log::error('Failed to update Outlook event', [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                ]);
                $syncRecord->markFailed();
            }
        } else {
            // Create new event
            try {
                $created = $this->makeRequest($connection, 'POST', $endpoint, $outlookEvent);

                $syncRecord = CalendarSyncEvent::create([
                    'connection_id' => $connection->id,
                    'event_id' => $event->id,
                    'external_event_id' => $created['id'],
                    'calendar_id' => $calendarId,
                    'sync_direction' => 'push',
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                    'sync_data' => [
                        'web_link' => $created['webLink'] ?? null,
                        'online_meeting' => $created['onlineMeeting'] ?? null,
                    ],
                ]);

                // Update event with Teams link if generated
                if (isset($created['onlineMeeting']['joinUrl'])) {
                    $event->update([
                        'meeting_link' => $created['onlineMeeting']['joinUrl'],
                        'meeting_provider' => 'teams',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create Outlook event', [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                ]);
                throw $e;
            }
        }

        return $syncRecord;
    }

    /**
     * Build Outlook event from ScheduledEvent.
     */
    protected function buildOutlookEvent(ScheduledEvent $event): array
    {
        $outlookEvent = [
            'subject' => $event->title,
            'body' => [
                'contentType' => 'text',
                'content' => $event->description ?? '',
            ],
            'start' => [
                'dateTime' => $event->starts_at->toIso8601String(),
                'timeZone' => $event->timezone,
            ],
            'end' => [
                'dateTime' => $event->ends_at->toIso8601String(),
                'timeZone' => $event->timezone,
            ],
        ];

        // Set location
        if ($event->location) {
            $outlookEvent['location'] = [
                'displayName' => $event->location,
            ];
        }

        // Add attendees
        $attendees = [];
        foreach ($event->participants as $participant) {
            if ($participant->role !== 'organizer') {
                $attendees[] = [
                    'emailAddress' => [
                        'address' => $participant->email,
                        'name' => $participant->name ?? $participant->email,
                    ],
                    'type' => $participant->role === 'optional' ? 'optional' : 'required',
                ];
            }
        }
        $outlookEvent['attendees'] = $attendees;

        // Add Teams meeting if video meeting
        if ($event->meeting_type === 'video' && !$event->meeting_link) {
            $outlookEvent['isOnlineMeeting'] = true;
            $outlookEvent['onlineMeetingProvider'] = 'teamsForBusiness';
        }

        return $outlookEvent;
    }

    /**
     * Delete an event from the calendar.
     */
    public function deleteEvent(CalendarConnection $connection, string $externalEventId): bool
    {
        try {
            $calendarId = $connection->calendar_id ?? 'primary';
            $endpoint = $calendarId === 'primary'
                ? "/me/calendar/events/{$externalEventId}"
                : "/me/calendars/{$calendarId}/events/{$externalEventId}";

            $this->makeRequest($connection, 'DELETE', $endpoint);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete Outlook event', [
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
        $calendarId = $connection->calendar_id ?? 'primary';
        $endpoint = $calendarId === 'primary'
            ? '/me/calendar/calendarView'
            : "/me/calendars/{$calendarId}/calendarView";

        $data = $this->makeRequest($connection, 'GET', $endpoint, [
            'startDateTime' => $start->format('c'),
            'endDateTime' => $end->format('c'),
            '$orderby' => 'start/dateTime',
        ]);

        $result = [];
        foreach ($data['value'] ?? [] as $event) {
            $result[] = [
                'id' => $event['id'],
                'summary' => $event['subject'],
                'description' => $event['body']['content'] ?? null,
                'start' => $event['start']['dateTime'],
                'end' => $event['end']['dateTime'],
                'location' => $event['location']['displayName'] ?? null,
                'online_meeting_url' => $event['onlineMeeting']['joinUrl'] ?? null,
            ];
        }

        return $result;
    }
}
