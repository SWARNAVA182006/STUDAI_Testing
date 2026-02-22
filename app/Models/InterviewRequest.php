<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'requested_by',
        'candidate_id',
        'event_id',
        'interview_type',
        'duration_minutes',
        'proposed_times',
        'message',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'proposed_times' => 'array',
        'duration_minutes' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the job application.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    /**
     * Get the requester (employer/HR).
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the candidate.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * Get the scheduled event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(ScheduledEvent::class, 'event_id');
    }

    /**
     * Check if expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'times_proposed';
    }

    /**
     * Check if scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->event_id !== null;
    }

    /**
     * Get interview type display name.
     */
    public function getInterviewTypeNameAttribute(): string
    {
        return match ($this->interview_type) {
            'phone' => 'Phone Screen',
            'video' => 'Video Interview',
            'onsite' => 'On-site Interview',
            'technical' => 'Technical Interview',
            'panel' => 'Panel Interview',
            'final' => 'Final Interview',
            default => ucfirst($this->interview_type),
        };
    }

    /**
     * Scope: Pending.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'times_proposed']);
    }

    /**
     * Scope: For candidate.
     */
    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }
}
