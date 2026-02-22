<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class DailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'difficulty',
        'action_type',
        'action_count',
        'action_conditions',
        'points_reward',
        'xp_reward',
        'streak_bonus',
        'day_of_week',
        'is_active',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'action_count' => 'integer',
            'action_conditions' => 'array',
            'points_reward' => 'integer',
            'xp_reward' => 'integer',
            'streak_bonus' => 'integer',
            'is_active' => 'boolean',
            'weight' => 'integer',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const DIFFICULTIES = [
        'easy' => ['name' => 'Easy', 'color' => '#22C55E', 'multiplier' => 1.0],
        'medium' => ['name' => 'Medium', 'color' => '#F59E0B', 'multiplier' => 1.5],
        'hard' => ['name' => 'Hard', 'color' => '#EF4444', 'multiplier' => 2.0],
    ];

    public const ACTION_TYPES = [
        'login' => 'Daily Login',
        'apply_job' => 'Apply to Jobs',
        'update_profile' => 'Update Profile',
        'complete_skill_test' => 'Complete Skill Tests',
        'send_message' => 'Send Messages',
        'earn_badge' => 'Earn Badges',
        'view_jobs' => 'View Job Listings',
        'save_job' => 'Save Jobs',
        'update_resume' => 'Update Resume',
        'network_connect' => 'Network Connections',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserDailyChallenge::class, 'challenge_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByDifficulty(Builder $query, string $difficulty): Builder
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeForToday(Builder $query): Builder
    {
        $dayOfWeek = strtolower(now()->format('l'));
        
        return $query->active()
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('day_of_week')
                    ->orWhere('day_of_week', $dayOfWeek);
            });
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getDifficultyDataAttribute(): array
    {
        return self::DIFFICULTIES[$this->difficulty] ?? self::DIFFICULTIES['easy'];
    }

    public function getActionTypeNameAttribute(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }

    public function getTotalRewardsForStreak(int $streak): array
    {
        $multiplier = $this->difficulty_data['multiplier'] ?? 1.0;
        $streakBonus = $streak > 0 ? ($this->streak_bonus * min($streak, 7)) : 0;

        return [
            'points' => (int) round(($this->points_reward * $multiplier) + $streakBonus),
            'xp' => (int) round($this->xp_reward * $multiplier),
        ];
    }

    public static function selectRandomForToday(int $count = 3): \Illuminate\Support\Collection
    {
        $challenges = self::forToday()->get();
        
        if ($challenges->isEmpty()) {
            return collect();
        }

        // Weighted random selection
        $weighted = [];
        foreach ($challenges as $challenge) {
            for ($i = 0; $i < $challenge->weight; $i++) {
                $weighted[] = $challenge;
            }
        }

        shuffle($weighted);
        
        return collect($weighted)
            ->unique('id')
            ->take($count);
    }
}
