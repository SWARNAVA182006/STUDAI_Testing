<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareerCoachSession extends Model
{
    use HasFactory, SoftDeletes;

    // Session Types
    public const TYPE_GENERAL_ADVICE = 'general_advice';
    public const TYPE_CAREER_PLANNING = 'career_planning';
    public const TYPE_SKILL_DEVELOPMENT = 'skill_development';
    public const TYPE_JOB_SEARCH = 'job_search';
    public const TYPE_INTERVIEW_PREP = 'interview_prep';
    public const TYPE_SALARY_NEGOTIATION = 'salary_negotiation';
    public const TYPE_CAREER_TRANSITION = 'career_transition';
    public const TYPE_GOAL_REVIEW = 'goal_review';
    public const TYPE_WEEKLY_CHECKIN = 'weekly_checkin';

    // Session Statuses
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'title',
        'session_type',
        'context',
        'summary',
        'action_items',
        'key_insights',
        'message_count',
        'last_message_at',
        'status',
    ];

    protected $casts = [
        'context' => 'array',
        'summary' => 'array',
        'action_items' => 'array',
        'key_insights' => 'array',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(CareerCoachMessage::class, 'session_id');
    }

    /**
     * Get the check-in associated with this session.
     */
    public function checkin(): BelongsTo
    {
        return $this->belongsTo(CareerCoachCheckin::class, 'id', 'session_id');
    }

    /**
     * Scope for active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for specific session type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('session_type', $type);
    }

    /**
     * Get session type labels.
     */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_GENERAL_ADVICE => 'General Advice',
            self::TYPE_CAREER_PLANNING => 'Career Planning',
            self::TYPE_SKILL_DEVELOPMENT => 'Skill Development',
            self::TYPE_JOB_SEARCH => 'Job Search',
            self::TYPE_INTERVIEW_PREP => 'Interview Prep',
            self::TYPE_SALARY_NEGOTIATION => 'Salary Negotiation',
            self::TYPE_CAREER_TRANSITION => 'Career Transition',
            self::TYPE_GOAL_REVIEW => 'Goal Review',
            self::TYPE_WEEKLY_CHECKIN => 'Weekly Check-in',
        ];
    }

    /**
     * Get the label for current session type.
     */
    public function getTypeLabel(): string
    {
        return self::getTypeLabels()[$this->session_type] ?? $this->session_type;
    }

    /**
     * Increment message count.
     */
    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Mark session as completed.
     */
    public function markCompleted(array $summary = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'summary' => $summary,
        ]);
    }

    /**
     * Get the most recent messages.
     */
    public function getRecentMessages(int $limit = 10)
    {
        return $this->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
