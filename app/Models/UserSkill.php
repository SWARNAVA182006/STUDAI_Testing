<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSkill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'skill_name',
        'category',
        'proficiency_level',
        'proficiency_score',
        'source',
        'evidence',
        'acquired_date',
        'last_used_date',
        'is_verified',
        'market_demand_score',
        'average_salary_impact',
        'related_skills',
        'metadata',
    ];

    protected $casts = [
        'evidence' => 'array',
        'related_skills' => 'array',
        'metadata' => 'array',
        'acquired_date' => 'date',
        'last_used_date' => 'date',
        'is_verified' => 'boolean',
        'average_salary_impact' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(SkillValidation::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(SkillAssessment::class);
    }

    /**
     * Scopes
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByProficiency($query, string $level)
    {
        return $query->where('proficiency_level', $level);
    }

    public function scopeHighDemand($query, int $threshold = 70)
    {
        return $query->where('market_demand_score', '>=', $threshold);
    }

    public function scopeRecentlyUsed($query, int $days = 30)
    {
        return $query->where('last_used_date', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getProficiencyPercentageAttribute(): int
    {
        return $this->proficiency_score;
    }

    public function getMarketValueAttribute(): string
    {
        if ($this->market_demand_score >= 80) return 'Very High';
        if ($this->market_demand_score >= 60) return 'High';
        if ($this->market_demand_score >= 40) return 'Moderate';
        if ($this->market_demand_score >= 20) return 'Low';
        return 'Very Low';
    }

    public function getProficiencyBadgeAttribute(): string
    {
        return match($this->proficiency_level) {
            'expert' => '🏆 Expert',
            'advanced' => '⭐ Advanced',
            'intermediate' => '📘 Intermediate',
            'beginner' => '🌱 Beginner',
            default => '❓ Unknown'
        };
    }

    public function getIsStaleAttribute(): bool
    {
        if (!$this->last_used_date) return false;
        return $this->last_used_date->lt(now()->subMonths(6));
    }

    public function getSalaryImpactFormattedAttribute(): string
    {
        if (!$this->average_salary_impact) return 'N/A';
        return '$' . number_format($this->average_salary_impact, 0) . '/year';
    }

    /**
     * Mutators
     */
    public function setSkillNameAttribute($value)
    {
        $this->attributes['skill_name'] = ucwords(strtolower($value));
    }

    /**
     * Helper Methods
     */
    public function updateProficiencyScore(int $score): void
    {
        $this->update([
            'proficiency_score' => min(100, max(0, $score)),
            'proficiency_level' => $this->calculateProficiencyLevel($score),
        ]);
    }

    public function markAsVerified(string $source = 'assessment'): void
    {
        $this->update([
            'is_verified' => true,
            'source' => $source,
        ]);
    }

    public function updateMarketDemand(int $score, ?float $salaryImpact = null): void
    {
        $this->update([
            'market_demand_score' => min(100, max(0, $score)),
            'average_salary_impact' => $salaryImpact ?? $this->average_salary_impact,
        ]);
    }

    public function addEvidence(string $type, array $data): void
    {
        $evidence = $this->evidence ?? [];
        $evidence[] = [
            'type' => $type,
            'data' => $data,
            'added_at' => now()->toISOString(),
        ];
        $this->update(['evidence' => $evidence]);
    }

    public function recordUsage(): void
    {
        $this->update(['last_used_date' => now()]);
    }

    protected function calculateProficiencyLevel(int $score): string
    {
        if ($score >= 90) return 'expert';
        if ($score >= 70) return 'advanced';
        if ($score >= 40) return 'intermediate';
        return 'beginner';
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'skill_name' => 'required|string|max:255',
            'category' => 'nullable|string|in:technical,soft,domain,language,tool',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'proficiency_score' => 'nullable|integer|min:0|max:100',
            'source' => 'nullable|in:self_reported,validated,ai_detected,assessment',
            'acquired_date' => 'nullable|date',
            'last_used_date' => 'nullable|date',
        ];
    }
}
