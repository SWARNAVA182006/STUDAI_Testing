<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NetworkEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'network_events';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'type',
        'location',
        'virtual_link',
        'starts_at',
        'ends_at',
        'timezone',
        'organizer_id',
        'group_id',
        'capacity',
        'attendee_count',
        'requires_approval',
        'is_featured',
        'tags',
        'settings',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'requires_approval' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'settings' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NetworkEvent $event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title) . '-' . Str::random(6);
            }
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class, 'event_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventRsvp::class, 'event_id')
            ->where('status', 'going');
    }

    public function interestedUsers(): HasMany
    {
        return $this->hasMany(EventRsvp::class, 'event_id')
            ->where('status', 'interested');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at->isFuture();
    }

    public function isPast(): bool
    {
        return $this->starts_at->isPast() && ($this->ends_at?->isPast() ?? true);
    }

    public function isHappening(): bool
    {
        $now = now();
        return $this->starts_at->isPast() && 
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function hasCapacity(): bool
    {
        if ($this->capacity === null) {
            return true;
        }
        return $this->attendee_count < $this->capacity;
    }

    public function isFull(): bool
    {
        return !$this->hasCapacity();
    }

    public function getRsvpForUser(?User $user): ?EventRsvp
    {
        if (!$user) {
            return null;
        }
        return $this->rsvps()->where('user_id', $user->id)->first();
    }

    public function isUserAttending(?User $user): bool
    {
        return $this->getRsvpForUser($user)?->status === 'going';
    }

    public function isUserInterested(?User $user): bool
    {
        return $this->getRsvpForUser($user)?->status === 'interested';
    }

    public function incrementAttendeeCount(): void
    {
        $this->increment('attendee_count');
    }

    public function decrementAttendeeCount(): void
    {
        if ($this->attendee_count > 0) {
            $this->decrement('attendee_count');
        }
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now())
            ->orderBy('starts_at', 'asc');
    }

    public function scopePast($query)
    {
        return $query->where('starts_at', '<', now())
            ->orderBy('starts_at', 'desc');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVirtual($query)
    {
        return $query->whereIn('type', ['virtual', 'hybrid']);
    }

    public function scopeInPerson($query)
    {
        return $query->whereIn('type', ['in_person', 'hybrid']);
    }

    public function getFormattedDateRange(): string
    {
        if ($this->ends_at === null) {
            return $this->starts_at->format('M j, Y g:i A');
        }

        if ($this->starts_at->isSameDay($this->ends_at)) {
            return $this->starts_at->format('M j, Y') . ' ' . 
                   $this->starts_at->format('g:i A') . ' - ' . 
                   $this->ends_at->format('g:i A');
        }

        return $this->starts_at->format('M j, Y g:i A') . ' - ' . 
               $this->ends_at->format('M j, Y g:i A');
    }
}
