<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerGoalUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'user_id',
        'session_id',
        'update_content',
        'progress_before',
        'progress_after',
        'milestones_completed',
        'challenges_faced',
        'next_steps',
        'ai_feedback',
        'mood',
    ];

    protected $casts = [
        'milestones_completed' => 'array',
        'challenges_faced' => 'array',
        'next_steps' => 'array',
        'ai_feedback' => 'array',
    ];

    /**
     * Get the goal that owns the update.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(CareerGoal::class, 'goal_id');
    }

    /**
     * Get the user that created the update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session associated with the update.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CareerCoachSession::class, 'session_id');
    }

    /**
     * Get progress change.
     */
    public function getProgressChange(): int
    {
        return ($this->progress_after ?? 0) - ($this->progress_before ?? 0);
    }

    /**
     * Check if progress improved.
     */
    public function hasProgressImproved(): bool
    {
        return $this->getProgressChange() > 0;
    }
}
