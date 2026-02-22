<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Interview extends Model
{
    protected $fillable = [
        'application_id',
        'interview_type',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_link',
        'notes',
        'status',
        'feedback',
        'rating',
        'interviewer_notes',
        'started_at',
        'completed_at',
        'canceled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'feedback' => 'array',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function interviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'interview_interviewer')
            ->withPivot('is_lead', 'availability')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
            ->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('interview_type', $type);
    }

    /**
     * Accessors
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->scheduled_at > now() && $this->status === 'scheduled';
    }

    public function getEndTimeAttribute(): \DateTime
    {
        return (clone $this->scheduled_at)->addMinutes($this->duration_minutes);
    }

    /**
     * Methods
     */
    public function markAsCompleted(array $feedback = [], ?int $rating = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'feedback' => $feedback,
            'rating' => $rating,
            'interviewer_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function reschedule(\DateTime $newTime): void
    {
        $this->update([
            'scheduled_at' => $newTime,
            'status' => 'scheduled',
            'canceled_at' => null,
            'cancellation_reason' => null,
        ]);
    }
}
