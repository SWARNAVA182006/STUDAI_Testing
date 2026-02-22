<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'industry',
        'metric',
        'period_start',
        'period_end',
        'rank_rewards',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'rank_rewards' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const TYPES = [
        'global' => 'Global All-Time',
        'industry' => 'Industry Specific',
        'weekly' => 'Weekly Challenge',
        'monthly' => 'Monthly Challenge',
        'seasonal' => 'Seasonal Event',
    ];

    public const METRICS = [
        'total_points' => 'Total Points',
        'weekly_xp' => 'Weekly XP Earned',
        'monthly_xp' => 'Monthly XP Earned',
        'achievements' => 'Achievements Unlocked',
        'streak' => 'Longest Streak',
        'level' => 'Current Level',
        'badges' => 'Badges Collected',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function entries(): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('period_start')
                ->orWhere('period_start', '<=', now());
        })->where(function ($q) {
            $q->whereNull('period_end')
                ->orWhere('period_end', '>=', now());
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getMetricNameAttribute(): string
    {
        return self::METRICS[$this->metric] ?? $this->metric;
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->period_start && $this->period_start->isFuture()) {
            return false;
        }

        if ($this->period_end && $this->period_end->isPast()) {
            return false;
        }

        return true;
    }

    public function getRewardForRank(int $rank): ?array
    {
        if (!$this->rank_rewards) {
            return null;
        }

        foreach ($this->rank_rewards as $reward) {
            $minRank = $reward['min_rank'] ?? $reward['rank'] ?? 1;
            $maxRank = $reward['max_rank'] ?? $reward['rank'] ?? 1;

            if ($rank >= $minRank && $rank <= $maxRank) {
                return $reward;
            }
        }

        return null;
    }

    public function getTopEntries(int $limit = 100): \Illuminate\Support\Collection
    {
        return $this->entries()
            ->with('user')
            ->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    public function getUserRank(int $userId): ?LeaderboardEntry
    {
        return $this->entries()
            ->where('user_id', $userId)
            ->first();
    }
}
