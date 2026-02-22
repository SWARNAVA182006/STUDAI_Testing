<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerCoachCheckin extends Model
{
    use HasFactory;

    // Statuses
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_RESCHEDULED = 'rescheduled';

    protected $fillable = [
        'user_id',
        'session_id',
        'scheduled_for',
        'completed_at',
        'status',
        'goals_reviewed',
        'wins_this_week',
        'challenges_this_week',
        'focus_for_next_week',
        'ai_summary',
        'overall_sentiment_score',
        'notes',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'completed_at' => 'date',
        'goals_reviewed' => 'array',
        'wins_this_week' => 'array',
        'challenges_this_week' => 'array',
        'focus_for_next_week' => 'array',
        'ai_summary' => 'array',
    ];

    /**
     * Get the user that owns the check-in.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session associated with the check-in.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CareerCoachSession::class, 'session_id');
    }

    /**
     * Scope for pending check-ins.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope for completed check-ins.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for due today.
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('scheduled_for', today());
    }

    /**
     * Scope for overdue.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereDate('scheduled_for', '<', today());
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(array $data = []): void
    {
        $this->update(array_merge($data, [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]));
    }

    /**
     * Mark as skipped.
     */
    public function markSkipped(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'notes' => $reason,
        ]);
    }

    /**
     * Reschedule check-in.
     */
    public function reschedule(\DateTimeInterface $newDate): void
    {
        $this->update([
            'status' => self::STATUS_RESCHEDULED,
            'scheduled_for' => $newDate,
        ]);
    }

    /**
     * Check if check-in is due.
     */
    public function isDue(): bool
    {
        return $this->status === self::STATUS_SCHEDULED 
            && $this->scheduled_for <= today();
    }
}
