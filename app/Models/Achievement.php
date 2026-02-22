<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'icon',
        'tier',
        'trigger_type',
        'trigger_action',
        'trigger_count',
        'trigger_conditions',
        'points_reward',
        'xp_reward',
        'badge_reward',
        'is_secret',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'trigger_count' => 'integer',
            'trigger_conditions' => 'array',
            'points_reward' => 'integer',
            'xp_reward' => 'integer',
            'is_secret' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const CATEGORIES = [
        'profile' => 'Profile Building',
        'applications' => 'Job Applications',
        'skills' => 'Skills & Learning',
        'networking' => 'Networking',
        'marketplace' => 'Marketplace',
        'career' => 'Career Growth',
        'social' => 'Community',
        'special' => 'Special',
    ];

    public const TIERS = [
        'bronze' => ['name' => 'Bronze', 'color' => '#CD7F32', 'multiplier' => 1.0],
        'silver' => ['name' => 'Silver', 'color' => '#C0C0C0', 'multiplier' => 1.5],
        'gold' => ['name' => 'Gold', 'color' => '#FFD700', 'multiplier' => 2.0],
        'platinum' => ['name' => 'Platinum', 'color' => '#E5E4E2', 'multiplier' => 3.0],
        'diamond' => ['name' => 'Diamond', 'color' => '#B9F2FF', 'multiplier' => 5.0],
    ];

    public const TRIGGER_TYPES = [
        'count' => 'Count Based',
        'milestone' => 'Milestone',
        'special' => 'Special Condition',
        'daily' => 'Daily Task',
        'weekly' => 'Weekly Task',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_secret', false);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByTier(Builder $query, string $tier): Builder
    {
        return $query->where('tier', $tier);
    }

    public function scopeByTriggerAction(Builder $query, string $action): Builder
    {
        return $query->where('trigger_action', $action);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getTierDataAttribute(): array
    {
        return self::TIERS[$this->tier] ?? self::TIERS['bronze'];
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getTotalRewardPointsAttribute(): int
    {
        $multiplier = $this->tier_data['multiplier'] ?? 1.0;
        return (int) round($this->points_reward * $multiplier);
    }

    public function isUnlockedBy(User $user): bool
    {
        return $this->userAchievements()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->exists();
    }

    public function getProgressFor(User $user): array
    {
        $userAchievement = $this->userAchievements()
            ->where('user_id', $user->id)
            ->first();

        if (!$userAchievement) {
            return [
                'progress' => 0,
                'target' => $this->trigger_count,
                'percentage' => 0,
                'is_completed' => false,
            ];
        }

        return [
            'progress' => $userAchievement->progress,
            'target' => $userAchievement->target,
            'percentage' => min(100, ($userAchievement->progress / max(1, $userAchievement->target)) * 100),
            'is_completed' => $userAchievement->is_completed,
        ];
    }
}
