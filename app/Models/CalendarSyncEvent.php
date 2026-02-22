<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarSyncEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'connection_id',
        'event_id',
        'external_event_id',
        'calendar_id',
        'sync_direction',
        'sync_status',
        'last_synced_at',
        'sync_data',
    ];

    protected $casts = [
        'sync_data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the calendar connection.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(CalendarConnection::class, 'connection_id');
    }

    /**
     * Get the event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ScheduledEvent::class, 'event_id');
    }

    /**
     * Mark as synced.
     */
    public function markSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(): void
    {
        $this->update(['sync_status' => 'failed']);
    }

    /**
     * Scope: Synced.
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope: Pending.
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }
}
