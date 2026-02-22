<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerCoachSuggestion extends Model
{
    use HasFactory;

    // Suggestion Types
    public const TYPE_SKILL_RECOMMENDATION = 'skill_recommendation';
    public const TYPE_JOB_OPPORTUNITY = 'job_opportunity';
    public const TYPE_NETWORKING_TIP = 'networking_tip';
    public const TYPE_LEARNING_RESOURCE = 'learning_resource';
    public const TYPE_INDUSTRY_INSIGHT = 'industry_insight';
    public const TYPE_MOTIVATION = 'motivation';
    public const TYPE_DEADLINE_REMINDER = 'deadline_reminder';
    public const TYPE_GOAL_NUDGE = 'goal_nudge';
    public const TYPE_CELEBRATION = 'celebration';

    // Priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'user_id',
        'goal_id',
        'title',
        'content',
        'type',
        'priority',
        'action_link',
        'metadata',
        'is_read',
        'is_dismissed',
        'is_acted_upon',
        'expires_at',
    ];

    protected $casts = [
        'action_link' => 'array',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'is_acted_upon' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the suggestion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the goal associated with the suggestion.
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(CareerGoal::class, 'goal_id');
    }

    /**
     * Scope for unread suggestions.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for active suggestions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get type labels.
     */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_SKILL_RECOMMENDATION => 'Skill Recommendation',
            self::TYPE_JOB_OPPORTUNITY => 'Job Opportunity',
            self::TYPE_NETWORKING_TIP => 'Networking Tip',
            self::TYPE_LEARNING_RESOURCE => 'Learning Resource',
            self::TYPE_INDUSTRY_INSIGHT => 'Industry Insight',
            self::TYPE_MOTIVATION => 'Motivation',
            self::TYPE_DEADLINE_REMINDER => 'Deadline Reminder',
            self::TYPE_GOAL_NUDGE => 'Goal Nudge',
            self::TYPE_CELEBRATION => 'Celebration',
        ];
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return self::getTypeLabels()[$this->type] ?? $this->type;
    }

    /**
     * Mark as read.
     */
    public function markRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Dismiss suggestion.
     */
    public function dismiss(): void
    {
        $this->update(['is_dismissed' => true]);
    }

    /**
     * Mark as acted upon.
     */
    public function markActedUpon(): void
    {
        $this->update([
            'is_acted_upon' => true,
            'is_read' => true,
        ]);
    }

    /**
     * Check if suggestion is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get icon for suggestion type.
     */
    public function getIcon(): string
    {
        return match ($this->type) {
            self::TYPE_SKILL_RECOMMENDATION => 'heroicon-o-academic-cap',
            self::TYPE_JOB_OPPORTUNITY => 'heroicon-o-briefcase',
            self::TYPE_NETWORKING_TIP => 'heroicon-o-user-group',
            self::TYPE_LEARNING_RESOURCE => 'heroicon-o-book-open',
            self::TYPE_INDUSTRY_INSIGHT => 'heroicon-o-chart-bar',
            self::TYPE_MOTIVATION => 'heroicon-o-fire',
            self::TYPE_DEADLINE_REMINDER => 'heroicon-o-clock',
            self::TYPE_GOAL_NUDGE => 'heroicon-o-bell',
            self::TYPE_CELEBRATION => 'heroicon-o-sparkles',
            default => 'heroicon-o-light-bulb',
        };
    }
}
