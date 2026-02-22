<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillTrend extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_name',
        'skill_category',
        'related_role',
        'demand_score',
        'job_mentions_count',
        'mention_frequency',
        'growth_rate',
        'salary_premium',
        'interview_rate_boost',
        'value_score',
        'trend_status',
        'trend_velocity',
        'predicted_demand_6m',
        'predicted_demand_12m',
        'related_skills',
        'replacement_skills',
        'market_insight',
        'trend_date',
    ];

    protected $casts = [
        'demand_score' => 'integer',
        'job_mentions_count' => 'integer',
        'mention_frequency' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'salary_premium' => 'decimal:2',
        'interview_rate_boost' => 'decimal:2',
        'value_score' => 'integer',
        'trend_velocity' => 'integer',
        'predicted_demand_6m' => 'decimal:2',
        'predicted_demand_12m' => 'decimal:2',
        'related_skills' => 'array',
        'replacement_skills' => 'array',
        'trend_date' => 'date',
    ];

    /**
     * Get latest trend for skill
     */
    public static function getLatest(string $skillName): ?self
    {
        return static::where('skill_name', $skillName)
            ->latest('trend_date')
            ->first();
    }

    /**
     * Get trending skills (by status)
     */
    public static function getTrending(string $status = 'emerging', int $limit = 20): \Illuminate\Support\Collection
    {
        return static::where('trend_status', $status)
            ->where('trend_date', '>=', now()->subDays(30))
            ->orderByDesc('demand_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get declining skills (to avoid)
     */
    public static function getDeclining(int $limit = 20): \Illuminate\Support\Collection
    {
        return static::whereIn('trend_status', ['declining', 'obsolete'])
            ->where('trend_date', '>=', now()->subDays(30))
            ->orderBy('trend_velocity')
            ->limit($limit)
            ->get();
    }

    /**
     * Get skill status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->trend_status) {
            'emerging' => 'blue',
            'hot' => 'red',
            'stable' => 'green',
            'declining' => 'yellow',
            'obsolete' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get skill status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->trend_status) {
            'emerging' => '🌱 Emerging',
            'hot' => '🔥 Hot',
            'stable' => '✓ Stable',
            'declining' => '📉 Declining',
            'obsolete' => '⚠️ Obsolete',
            default => 'Unknown',
        };
    }

    /**
     * Check if skill is worth learning
     */
    public function isWorthLearning(): bool
    {
        return $this->trend_status === 'emerging' 
            || $this->trend_status === 'hot'
            || ($this->trend_status === 'stable' && $this->value_score >= 70);
    }
}
