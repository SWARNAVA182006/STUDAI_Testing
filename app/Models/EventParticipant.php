<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'email',
        'name',
        'role',
        'status',
        'responded_at',
        'response_note',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
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
     * Accept the invitation.
     */
    public function accept(?string $note = null): void
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'response_note' => $note,
        ]);
    }

    /**
     * Decline the invitation.
     */
    public function decline(?string $note = null): void
    {
        $this->update([
            'status' => 'declined',
            'responded_at' => now(),
            'response_note' => $note,
        ]);
    }

    /**
     * Mark as tentative.
     */
    public function tentative(?string $note = null): void
    {
        $this->update([
            'status' => 'tentative',
            'responded_at' => now(),
            'response_note' => $note,
        ]);
    }

    /**
     * Get display name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->name ?? $this->email;
    }

    /**
     * Scope: By status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Accepted.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: Pending.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
