<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class GamificationBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'icon',
        'color',
        'rarity',
        'earn_type',
        'earn_reference_id',
        'purchase_cost',
        'is_displayable',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected function casts(): array
    {
        return [
            'earn_reference_id' => 'integer',
            'purchase_cost' => 'integer',
            'is_displayable' => 'boolean',
            'is_active' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const CATEGORIES = [
        'skill' => 'Skills',
        'career' => 'Career',
        'social' => 'Social',
        'special' => 'Special',
        'seasonal' => 'Seasonal',
    ];

    public const RARITIES = [
        'common' => ['name' => 'Common', 'color' => '#9CA3AF', 'points' => 10],
        'uncommon' => ['name' => 'Uncommon', 'color' => '#22C55E', 'points' => 25],
        'rare' => ['name' => 'Rare', 'color' => '#3B82F6', 'points' => 50],
        'epic' => ['name' => 'Epic', 'color' => '#A855F7', 'points' => 100],
        'legendary' => ['name' => 'Legendary', 'color' => '#F59E0B', 'points' => 250],
    ];

    public const EARN_TYPES = [
        'achievement' => 'Achievement Unlock',
        'purchase' => 'Points Purchase',
        'event' => 'Event Participation',
        'admin' => 'Admin Granted',
        'skill_test' => 'Skill Test Completion',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'badge_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDisplayable(Builder $query): Builder
    {
        return $query->where('is_displayable', true);
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

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByRarity(Builder $query, string $rarity): Builder
    {
        return $query->where('rarity', $rarity);
    }

    public function scopePurchasable(Builder $query): Builder
    {
        return $query->where('earn_type', 'purchase')
            ->whereNotNull('purchase_cost');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getRarityDataAttribute(): array
    {
        return self::RARITIES[$this->rarity] ?? self::RARITIES['common'];
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
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

    public function isPurchasable(): bool
    {
        return $this->earn_type === 'purchase' && $this->purchase_cost !== null;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->userBadges()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function getOwnerCountAttribute(): int
    {
        return $this->userBadges()->count();
    }
}
