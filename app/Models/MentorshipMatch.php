<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MentorshipMatch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'mentor_id',
        'mentee_id',
        'status',
        'match_score',
        'ai_reasoning',
        'mentee_goals',
        'mentor_focus_areas',
        'matched_skills',
        'meeting_frequency',
        'preferred_communication',
        'first_meeting_at',
        'last_meeting_at',
        'next_meeting_at',
        'mentor_notes',
        'mentee_feedback',
        'milestones',
        'completed_at',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'ai_reasoning' => 'array',
        'mentee_goals' => 'array',
        'mentor_focus_areas' => 'array',
        'matched_skills' => 'array',
        'preferred_communication' => 'array',
        'first_meeting_at' => 'datetime',
        'last_meeting_at' => 'datetime',
        'next_meeting_at' => 'datetime',
        'milestones' => 'array',
        'completed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    // Meeting frequency constants
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_BIWEEKLY = 'biweekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    // Relationships
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('mentor_id', $userId)
            ->orWhere('mentee_id', $userId);
    }

    public function scopeAsMentor($query, int $userId)
    {
        return $query->where('mentor_id', $userId);
    }

    public function scopeAsMentee($query, int $userId)
    {
        return $query->where('mentee_id', $userId);
    }

    public function scopeHighMatch($query, float $minScore = 0.7)
    {
        return $query->where('match_score', '>=', $minScore);
    }

    // Accessors
    public function getOtherPartyAttribute(): ?User
    {
        if (! auth()->check()) {
            return null;
        }

        return auth()->id() === $this->mentor_id
            ? $this->mentee
            : $this->mentor;
    }

    public function getIsMentorAttribute(): bool
    {
        return auth()->check() && auth()->id() === $this->mentor_id;
    }

    public function getIsMenteeAttribute(): bool
    {
        return auth()->check() && auth()->id() === $this->mentee_id;
    }

    public function getMatchPercentageAttribute(): int
    {
        return (int) round($this->match_score * 100);
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->first_meeting_at) {
            return null;
        }

        $end = $this->completed_at ?? now();

        return $this->first_meeting_at->diffForHumans($end, true);
    }

    public function getMeetingsCountAttribute(): int
    {
        // Could be calculated from a meetings table if we add one
        return is_array($this->milestones) ? count($this->milestones) : 0;
    }

    // Methods
    public function accept(): self
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'first_meeting_at' => $this->first_meeting_at ?? now(),
        ]);

        return $this;
    }

    public function reject(): self
    {
        $this->update(['status' => self::STATUS_REJECTED]);

        return $this;
    }

    public function pause(): self
    {
        $this->update(['status' => self::STATUS_PAUSED]);

        return $this;
    }

    public function resume(): self
    {
        $this->update(['status' => self::STATUS_ACTIVE]);

        return $this;
    }

    public function complete(): self
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return $this;
    }

    public function addMilestone(string $title, ?string $description = null): self
    {
        $milestones = $this->milestones ?? [];
        $milestones[] = [
            'title' => $title,
            'description' => $description,
            'achieved_at' => now()->toIso8601String(),
        ];

        $this->update(['milestones' => $milestones]);

        return $this;
    }

    public function recordMeeting(): self
    {
        $this->update([
            'last_meeting_at' => now(),
        ]);

        // Calculate next meeting based on frequency
        $nextMeeting = match ($this->meeting_frequency) {
            self::FREQUENCY_WEEKLY => now()->addWeek(),
            self::FREQUENCY_BIWEEKLY => now()->addWeeks(2),
            self::FREQUENCY_MONTHLY => now()->addMonth(),
            default => now()->addWeeks(2),
        };

        $this->update(['next_meeting_at' => $nextMeeting]);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canMessage(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_PAUSED,
        ]);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'info',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_PAUSED => 'gray',
            self::STATUS_COMPLETED => 'primary',
            self::STATUS_REJECTED => 'danger',
            default => 'gray',
        };
    }
}
