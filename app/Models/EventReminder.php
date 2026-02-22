<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'minutes_before',
        'channel',
        'is_sent',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'minutes_before' => 'integer',
        'is_sent' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ScheduledEvent::class, 'event_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Check if reminder should be sent.
     */
    public function shouldSendNow(): bool
    {
        return !$this->is_sent && $this->scheduled_at->isPast();
    }

    /**
     * Scope: Unsent.
     */
    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }

    /**
     * Scope: Due now.
     */
    public function scopeDue($query)
    {
        return $query->unsent()->where('scheduled_at', '<=', now());
    }
}
