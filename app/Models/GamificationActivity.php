<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GamificationActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'action_category',
        'actionable_type',
        'actionable_id',
        'points_earned',
        'xp_earned',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'actionable_id' => 'integer',
            'points_earned' => 'integer',
            'xp_earned' => 'integer',
            'metadata' => 'array',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants - All Tracked Actions
    // ─────────────────────────────────────────────────────────────

    public const ACTIONS = [
        // Authentication
        'login' => ['category' => 'auth', 'points' => 1, 'xp' => 5],
        'daily_login' => ['category' => 'auth', 'points' => 5, 'xp' => 10],
        
        // Profile
        'profile_created' => ['category' => 'profile', 'points' => 50, 'xp' => 100],
        'profile_updated' => ['category' => 'profile', 'points' => 5, 'xp' => 10],
        'profile_photo_added' => ['category' => 'profile', 'points' => 20, 'xp' => 40],
        'resume_uploaded' => ['category' => 'profile', 'points' => 30, 'xp' => 60],
        'linkedin_connected' => ['category' => 'profile', 'points' => 25, 'xp' => 50],
        'profile_100_complete' => ['category' => 'profile', 'points' => 100, 'xp' => 200],
        
        // Jobs
        'job_applied' => ['category' => 'jobs', 'points' => 10, 'xp' => 25],
        'job_saved' => ['category' => 'jobs', 'points' => 2, 'xp' => 5],
        'job_viewed' => ['category' => 'jobs', 'points' => 1, 'xp' => 2],
        'application_shortlisted' => ['category' => 'jobs', 'points' => 50, 'xp' => 100],
        'interview_scheduled' => ['category' => 'jobs', 'points' => 75, 'xp' => 150],
        'offer_received' => ['category' => 'jobs', 'points' => 200, 'xp' => 500],
        
        // Skills
        'skill_added' => ['category' => 'skills', 'points' => 5, 'xp' => 10],
        'skill_test_started' => ['category' => 'skills', 'points' => 5, 'xp' => 10],
        'skill_test_completed' => ['category' => 'skills', 'points' => 25, 'xp' => 50],
        'skill_test_passed' => ['category' => 'skills', 'points' => 50, 'xp' => 100],
        'skill_certified' => ['category' => 'skills', 'points' => 100, 'xp' => 200],
        'skill_endorsed' => ['category' => 'skills', 'points' => 15, 'xp' => 30],
        
        // Networking
        'connection_request_sent' => ['category' => 'networking', 'points' => 3, 'xp' => 5],
        'connection_accepted' => ['category' => 'networking', 'points' => 10, 'xp' => 20],
        'message_sent' => ['category' => 'networking', 'points' => 2, 'xp' => 5],
        'referral_sent' => ['category' => 'networking', 'points' => 25, 'xp' => 50],
        'referral_hired' => ['category' => 'networking', 'points' => 200, 'xp' => 400],
        
        // Marketplace
        'proposal_submitted' => ['category' => 'marketplace', 'points' => 15, 'xp' => 30],
        'proposal_accepted' => ['category' => 'marketplace', 'points' => 75, 'xp' => 150],
        'project_completed' => ['category' => 'marketplace', 'points' => 100, 'xp' => 250],
        'review_received' => ['category' => 'marketplace', 'points' => 20, 'xp' => 40],
        '5_star_review' => ['category' => 'marketplace', 'points' => 50, 'xp' => 100],
        'contract_milestone_completed' => ['category' => 'marketplace', 'points' => 30, 'xp' => 60],
        
        // AI Features
        'ai_coach_session' => ['category' => 'ai', 'points' => 10, 'xp' => 20],
        'ai_resume_review' => ['category' => 'ai', 'points' => 15, 'xp' => 30],
        'ai_interview_practice' => ['category' => 'ai', 'points' => 20, 'xp' => 40],
        'ai_salary_negotiation' => ['category' => 'ai', 'points' => 25, 'xp' => 50],
        
        // Learning
        'course_started' => ['category' => 'learning', 'points' => 10, 'xp' => 20],
        'course_completed' => ['category' => 'learning', 'points' => 50, 'xp' => 100],
        'certification_earned' => ['category' => 'learning', 'points' => 100, 'xp' => 200],
        
        // Engagement
        'daily_challenge_completed' => ['category' => 'engagement', 'points' => 20, 'xp' => 40],
        'weekly_goal_achieved' => ['category' => 'engagement', 'points' => 50, 'xp' => 100],
        'streak_maintained' => ['category' => 'engagement', 'points' => 10, 'xp' => 20],
        'achievement_unlocked' => ['category' => 'engagement', 'points' => 0, 'xp' => 0], // Varies
        'level_up' => ['category' => 'engagement', 'points' => 25, 'xp' => 0],
    ];

    public const CATEGORIES = [
        'auth' => 'Authentication',
        'profile' => 'Profile Building',
        'jobs' => 'Job Search',
        'skills' => 'Skills & Learning',
        'networking' => 'Networking',
        'marketplace' => 'Marketplace',
        'ai' => 'AI Features',
        'learning' => 'Learning',
        'engagement' => 'Engagement',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getActionNameAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->action));
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->action_category] ?? $this->action_category;
    }

    public static function getRewardsForAction(string $action): array
    {
        return self::ACTIONS[$action] ?? ['category' => 'other', 'points' => 0, 'xp' => 0];
    }

    public static function getUserActionCountToday(int $userId, string $action): int
    {
        return self::forUser($userId)
            ->byAction($action)
            ->today()
            ->count();
    }

    public static function hasUserPerformedToday(int $userId, string $action): bool
    {
        return self::getUserActionCountToday($userId, $action) > 0;
    }
}
