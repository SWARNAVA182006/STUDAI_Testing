<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRsvp extends Model
{
    use HasFactory;

    protected $table = 'event_rsvps';

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'note',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(NetworkEvent::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isGoing(): bool
    {
        return $this->status === 'going';
    }

    public function isInterested(): bool
    {
        return $this->status === 'interested';
    }

    public function isNotGoing(): bool
    {
        return $this->status === 'not_going';
    }

    public function markAsGoing(): void
    {
        $wasGoing = $this->isGoing();
        $this->update(['status' => 'going']);
        
        if (!$wasGoing) {
            $this->event->incrementAttendeeCount();
        }
    }

    public function markAsInterested(): void
    {
        if ($this->isGoing()) {
            $this->event->decrementAttendeeCount();
        }
        $this->update(['status' => 'interested']);
    }

    public function markAsNotGoing(): void
    {
        if ($this->isGoing()) {
            $this->event->decrementAttendeeCount();
        }
        $this->update(['status' => 'not_going']);
    }

    public function scopeGoing($query)
    {
        return $query->where('status', 'going');
    }

    public function scopeInterested($query)
    {
        return $query->where('status', 'interested');
    }
}
