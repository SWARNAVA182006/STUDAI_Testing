<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ScheduledEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'organizer_id',
        'title',
        'description',
        'event_type',
        'starts_at',
        'ends_at',
        'timezone',
        'status',
        'location',
        'meeting_type',
        'meeting_link',
        'meeting_password',
        'meeting_provider',
        'meeting_details',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meeting_details' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the organizer.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * Get participants.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class, 'event_id');
    }

    /**
     * Get reminders.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(EventReminder::class, 'event_id');
    }

    /**
     * Get sync records.
     */
    public function syncRecords(): HasMany
    {
        return $this->hasMany(CalendarSyncEvent::class, 'event_id');
    }

    /**
     * Get the interview request if this is an interview event.
     */
    public function interviewRequest()
    {
        return $this->hasOne(InterviewRequest::class, 'event_id');
    }

    /**
     * Get duration in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /**
     * Check if event is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->starts_at->isFuture() && $this->status !== 'cancelled';
    }

    /**
     * Check if event is in progress.
     */
    public function isInProgress(): bool
    {
        return now()->between($this->starts_at, $this->ends_at) && $this->status !== 'cancelled';
    }

    /**
     * Check if event is past.
     */
    public function isPast(): bool
    {
        return $this->ends_at->isPast();
    }

    /**
     * Confirm the event.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Cancel the event.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark as completed.
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedDateRangeAttribute(): string
    {
        if ($this->starts_at->isSameDay($this->ends_at)) {
            return $this->starts_at->format('M j, Y') . ' ' .
                   $this->starts_at->format('g:i A') . ' - ' .
                   $this->ends_at->format('g:i A');
        }

        return $this->starts_at->format('M j, Y g:i A') . ' - ' .
               $this->ends_at->format('M j, Y g:i A');
    }

    /**
     * Scope: Upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at');
    }

    /**
     * Scope: Past events.
     */
    public function scopePast($query)
    {
        return $query->where('ends_at', '<', now())
            ->orderByDesc('starts_at');
    }

    /**
     * Scope: By status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: For user (as organizer or participant).
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('organizer_id', $userId)
            ->orWhereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }

    /**
     * Scope: In date range.
     */
    public function scopeInDateRange($query, $start, $end)
    {
        return $query->where('starts_at', '>=', $start)
            ->where('starts_at', '<=', $end);
    }
}
