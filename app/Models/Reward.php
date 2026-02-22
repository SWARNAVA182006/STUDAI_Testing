<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'type',
        'points_cost',
        'level_required',
        'reward_type',
        'reward_data',
        'duration_days',
        'stock',
        'redeemed_count',
        'per_user_limit',
        'image',
        'is_featured',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'level_required' => 'integer',
            'reward_data' => 'array',
            'duration_days' => 'integer',
            'stock' => 'integer',
            'redeemed_count' => 'integer',
            'per_user_limit' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const CATEGORIES = [
        'premium_feature' => 'Premium Features',
        'badge' => 'Exclusive Badges',
        'boost' => 'Profile Boosts',
        'physical' => 'Physical Items',
        'partner' => 'Partner Rewards',
    ];

    public const TYPES = [
        'one_time' => 'One-Time Purchase',
        'subscription' => 'Time-Limited',
        'consumable' => 'Consumable',
    ];

    public const REWARD_TYPES = [
        'feature_unlock' => 'Feature Unlock',
        'badge' => 'Badge Reward',
        'xp_boost' => 'XP Multiplier',
        'streak_freeze' => 'Streak Protection',
        'profile_boost' => 'Profile Visibility Boost',
        'resume_review' => 'AI Resume Review',
        'interview_prep' => 'Interview Preparation',
        'priority_support' => 'Priority Support',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function userRewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', now());
            });
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('stock')
                ->orWhereRaw('stock > redeemed_count');
        });
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeAffordableBy(Builder $query, int $points): Builder
    {
        return $query->where('points_cost', '<=', $points);
    }

    public function scopeForLevel(Builder $query, int $level): Builder
    {
        return $query->where('level_required', '<=', $level);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getRewardTypeNameAttribute(): string
    {
        return self::REWARD_TYPES[$this->reward_type] ?? $this->reward_type;
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }

        if ($this->available_until && $this->available_until->isPast()) {
            return false;
        }

        return true;
    }

    public function hasStock(): bool
    {
        if ($this->stock === null) {
            return true;
        }

        return $this->stock > $this->redeemed_count;
    }

    public function getRemainingStockAttribute(): ?int
    {
        if ($this->stock === null) {
            return null;
        }

        return max(0, $this->stock - $this->redeemed_count);
    }

    public function canBeRedeemedBy(User $user): array
    {
        $profile = $user->gamificationProfile;
        
        if (!$profile) {
            return ['can_redeem' => false, 'reason' => 'No gamification profile found'];
        }

        if (!$this->isAvailable()) {
            return ['can_redeem' => false, 'reason' => 'This reward is not currently available'];
        }

        if (!$this->hasStock()) {
            return ['can_redeem' => false, 'reason' => 'This reward is out of stock'];
        }

        if ($profile->available_points < $this->points_cost) {
            return ['can_redeem' => false, 'reason' => 'Not enough points'];
        }

        if ($profile->level < $this->level_required) {
            return ['can_redeem' => false, 'reason' => "Requires level {$this->level_required}"];
        }

        if ($this->per_user_limit !== null) {
            $userRedeemCount = $this->userRewards()
                ->where('user_id', $user->id)
                ->count();

            if ($userRedeemCount >= $this->per_user_limit) {
                return ['can_redeem' => false, 'reason' => 'You have reached the limit for this reward'];
            }
        }

        return ['can_redeem' => true, 'reason' => null];
    }

    public function incrementRedeemCount(): void
    {
        $this->increment('redeemed_count');
    }
}
