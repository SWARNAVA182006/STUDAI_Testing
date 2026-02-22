<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGamificationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_points',
        'available_points',
        'level',
        'xp_current',
        'xp_required',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'streak_freeze_count',
        'achievements_unlocked',
        'badges_earned',
        'challenges_completed',
        'rewards_redeemed',
        'show_on_leaderboard',
        'leaderboard_display_name',
        'primary_industry',
        'xp_multiplier',
        'multiplier_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'integer',
            'available_points' => 'integer',
            'level' => 'integer',
            'xp_current' => 'integer',
            'xp_required' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_activity_date' => 'date',
            'streak_freeze_count' => 'integer',
            'achievements_unlocked' => 'integer',
            'badges_earned' => 'integer',
            'challenges_completed' => 'integer',
            'rewards_redeemed' => 'integer',
            'show_on_leaderboard' => 'boolean',
            'xp_multiplier' => 'decimal:2',
            'multiplier_expires_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'user_id', 'user_id');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'user_id', 'user_id');
    }

    public function pointsTransactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class, 'user_id', 'user_id');
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class, 'user_id', 'user_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeOnLeaderboard($query)
    {
        return $query->where('show_on_leaderboard', true);
    }

    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('primary_industry', $industry);
    }

    public function scopeTopPlayers($query, int $limit = 100)
    {
        return $query->onLeaderboard()
            ->orderByDesc('total_points')
            ->limit($limit);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        return $this->leaderboard_display_name ?? $this->user->name ?? 'Anonymous';
    }

    public function getLevelProgressAttribute(): float
    {
        if ($this->xp_required <= 0) {
            return 100;
        }

        return min(100, ($this->xp_current / $this->xp_required) * 100);
    }

    public function getActiveMultiplierAttribute(): float
    {
        if ($this->multiplier_expires_at && $this->multiplier_expires_at->isFuture()) {
            return (float) $this->xp_multiplier;
        }

        return 1.0;
    }

    public function hasActiveStreak(): bool
    {
        if (!$this->last_activity_date) {
            return false;
        }

        return $this->last_activity_date->isToday() || $this->last_activity_date->isYesterday();
    }

    public function needsStreakUpdate(): bool
    {
        if (!$this->last_activity_date) {
            return true;
        }

        return !$this->last_activity_date->isToday();
    }

    public function calculateXpForLevel(int $level): int
    {
        // XP formula: 100 * (level ^ 1.5)
        return (int) round(100 * pow($level, 1.5));
    }
}
