<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use App\Models\ScheduledEvent;
use App\Models\CalendarSyncEvent;

interface CalendarProviderInterface
{
    /**
     * Get authorization URL for OAuth.
     */
    public function getAuthUrl(string $redirectUri, array $scopes = []): string;

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code, string $redirectUri): array;

    /**
     * Refresh the access token.
     */
    public function refreshToken(CalendarConnection $connection): array;

    /**
     * Get list of calendars.
     */
    public function getCalendars(CalendarConnection $connection): array;

    /**
     * Sync an event to the calendar.
     */
    public function syncEvent(CalendarConnection $connection, ScheduledEvent $event): CalendarSyncEvent;

    /**
     * Delete an event from the calendar.
     */
    public function deleteEvent(CalendarConnection $connection, string $externalEventId): bool;

    /**
     * Get events from the calendar.
     */
    public function getEvents(CalendarConnection $connection, \DateTime $start, \DateTime $end): array;
}
