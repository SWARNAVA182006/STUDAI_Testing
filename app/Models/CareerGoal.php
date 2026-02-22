<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareerGoal extends Model
{
    use HasFactory, SoftDeletes;

    // Categories
    public const CATEGORY_ROLE_CHANGE = 'role_change';
    public const CATEGORY_SALARY_INCREASE = 'salary_increase';
    public const CATEGORY_SKILL_ACQUISITION = 'skill_acquisition';
    public const CATEGORY_CERTIFICATION = 'certification';
    public const CATEGORY_PROMOTION = 'promotion';
    public const CATEGORY_CAREER_PIVOT = 'career_pivot';
    public const CATEGORY_SIDE_PROJECT = 'side_project';
    public const CATEGORY_NETWORKING = 'networking';
    public const CATEGORY_WORK_LIFE_BALANCE = 'work_life_balance';
    public const CATEGORY_LEADERSHIP = 'leadership';
    public const CATEGORY_ENTREPRENEURSHIP = 'entrepreneurship';
    public const CATEGORY_EDUCATION = 'education';
    public const CATEGORY_OTHER = 'other';

    // Timeframes
    public const TIMEFRAME_1_MONTH = '1_month';
    public const TIMEFRAME_3_MONTHS = '3_months';
    public const TIMEFRAME_6_MONTHS = '6_months';
    public const TIMEFRAME_1_YEAR = '1_year';
    public const TIMEFRAME_2_YEARS = '2_years';
    public const TIMEFRAME_5_YEARS = '5_years';
    public const TIMEFRAME_ONGOING = 'ongoing';

    // Priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    // Statuses
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'timeframe',
        'target_date',
        'priority',
        'status',
        'progress_percentage',
        'milestones',
        'metrics',
        'obstacles',
        'resources',
        'ai_recommendations',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'milestones' => 'array',
        'metrics' => 'array',
        'obstacles' => 'array',
        'resources' => 'array',
        'ai_recommendations' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the progress updates for the goal.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(CareerGoalUpdate::class, 'goal_id');
    }

    /**
     * Get the suggestions for the goal.
     */
    public function suggestions(): HasMany
    {
        return $this->hasMany(CareerCoachSuggestion::class, 'goal_id');
    }

    /**
     * Scope for active goals.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope for completed goals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for high priority goals.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    /**
     * Get category labels.
     */
    public static function getCategoryLabels(): array
    {
        return [
            self::CATEGORY_ROLE_CHANGE => 'Role Change',
            self::CATEGORY_SALARY_INCREASE => 'Salary Increase',
            self::CATEGORY_SKILL_ACQUISITION => 'Skill Acquisition',
            self::CATEGORY_CERTIFICATION => 'Certification',
            self::CATEGORY_PROMOTION => 'Promotion',
            self::CATEGORY_CAREER_PIVOT => 'Career Pivot',
            self::CATEGORY_SIDE_PROJECT => 'Side Project',
            self::CATEGORY_NETWORKING => 'Networking',
            self::CATEGORY_WORK_LIFE_BALANCE => 'Work-Life Balance',
            self::CATEGORY_LEADERSHIP => 'Leadership Development',
            self::CATEGORY_ENTREPRENEURSHIP => 'Entrepreneurship',
            self::CATEGORY_EDUCATION => 'Education',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * Get timeframe labels.
     */
    public static function getTimeframeLabels(): array
    {
        return [
            self::TIMEFRAME_1_MONTH => '1 Month',
            self::TIMEFRAME_3_MONTHS => '3 Months',
            self::TIMEFRAME_6_MONTHS => '6 Months',
            self::TIMEFRAME_1_YEAR => '1 Year',
            self::TIMEFRAME_2_YEARS => '2 Years',
            self::TIMEFRAME_5_YEARS => '5 Years',
            self::TIMEFRAME_ONGOING => 'Ongoing',
        ];
    }

    /**
     * Get status labels.
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_NOT_STARTED => 'Not Started',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_ABANDONED => 'Abandoned',
        ];
    }

    /**
     * Get priority labels.
     */
    public static function getPriorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabel(): string
    {
        return self::getCategoryLabels()[$this->category] ?? $this->category;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabel(): string
    {
        return self::getPriorityLabels()[$this->priority] ?? $this->priority;
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $percentage, ?string $notes = null): void
    {
        $oldProgress = $this->progress_percentage;
        
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage)),
            'status' => $percentage >= 100 ? self::STATUS_COMPLETED : self::STATUS_IN_PROGRESS,
            'started_at' => $this->started_at ?? now(),
            'completed_at' => $percentage >= 100 ? now() : null,
        ]);

        // Create update record
        $this->updates()->create([
            'user_id' => $this->user_id,
            'update_content' => $notes ?? "Progress updated to {$percentage}%",
            'progress_before' => $oldProgress,
            'progress_after' => $percentage,
        ]);
    }

    /**
     * Check if goal is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->target_date && $this->target_date->isPast() && !$this->isCompleted();
    }

    /**
     * Check if goal is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get days until target date.
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->target_date) {
            return null;
        }

        return (int) now()->diffInDays($this->target_date, false);
    }
}
