<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VideoInterviewInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_interview_session_id',
        'invited_by',
        'candidate_id',
        'job_id',
        'invitation_token',
        'status',
        'message',
        'deadline',
        'accepted_at',
        'declined_at',
        'decline_reason',
        'sent_at',
        'viewed_at',
        'reminder_count',
        'last_reminder_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'reminder_count' => 'integer',
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_EXPIRED => 'Expired',
    ];

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invitation) {
            if (empty($invitation->invitation_token)) {
                $invitation->invitation_token = Str::random(64);
            }
        });
    }

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewSession::class, 'video_interview_session_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('deadline')
                    ->orWhere('deadline', '>', now());
            });
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->is_expired;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_expired && $this->status === self::STATUS_PENDING) {
            return 'Expired';
        }
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->is_expired && $this->status === self::STATUS_PENDING) {
            return 'gray';
        }

        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_DECLINED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    public function getInvitationUrlAttribute(): string
    {
        return route('video-interview.invitation', ['token' => $this->invitation_token]);
    }

    // Methods
    public function accept(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function decline(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }

    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    public function recordReminder(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);
    }

    public function canSendReminder(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->reminder_count >= 3) {
            return false;
        }

        if ($this->last_reminder_at && $this->last_reminder_at->diffInHours(now()) < 24) {
            return false;
        }

        return true;
    }
}
