<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SkillGap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'skill_name',
        'category',
        'gap_severity',
        'impact_score',
        'market_demand_score',
        'salary_impact',
        'required_for_roles',
        'learning_time_weeks',
        'difficulty_level',
        'prerequisite_skills',
        'ai_reasoning',
        'is_emerging_skill',
        'trend_score',
        'trend_direction',
        'identified_date',
        'target_completion_date',
        'status',
    ];

    protected $casts = [
        'required_for_roles' => 'array',
        'prerequisite_skills' => 'array',
        'ai_reasoning' => 'array',
        'is_emerging_skill' => 'boolean',
        'salary_impact' => 'decimal:2',
        'identified_date' => 'date',
        'target_completion_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningPath(): HasOne
    {
        return $this->hasOne(LearningPath::class);
    }

    /**
     * Scopes
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('gap_severity', $severity);
    }

    public function scopeHighImpact($query, int $threshold = 70)
    {
        return $query->where('impact_score', '>=', $threshold);
    }

    public function scopeEmergingSkills($query)
    {
        return $query->where('is_emerging_skill', true);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeIdentified($query)
    {
        return $query->where('status', 'identified');
    }

    public function scopeLearning($query)
    {
        return $query->where('status', 'learning');
    }

    public function scopeRankedByPriority($query)
    {
        return $query->orderByDesc('impact_score')
                     ->orderByDesc('market_demand_score')
                     ->orderBy('learning_time_weeks', 'asc');
    }

    /**
     * Accessors
     */
    public function getPriorityScoreAttribute(): int
    {
        // Weighted combination of impact, demand, and urgency
        $impactWeight = 0.4;
        $demandWeight = 0.3;
        $urgencyWeight = 0.3;

        $urgencyScore = $this->is_emerging_skill ? 100 : ($this->trend_score ?? 50);
        
        return (int) round(
            ($this->impact_score * $impactWeight) +
            ($this->market_demand_score * $demandWeight) +
            ($urgencyScore * $urgencyWeight)
        );
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match($this->gap_severity) {
            'critical' => '🔴 Critical',
            'high' => '🟠 High',
            'medium' => '🟡 Medium',
            'low' => '🟢 Low',
            default => '⚪ Unknown'
        };
    }

    public function getDifficultyBadgeAttribute(): string
    {
        return match($this->difficulty_level) {
            'advanced' => '🏔️ Advanced',
            'challenging' => '⛰️ Challenging',
            'moderate' => '🗻 Moderate',
            'easy' => '🏕️ Easy',
            default => '❓ Unknown'
        };
    }

    public function getTrendIndicatorAttribute(): string
    {
        return match($this->trend_direction) {
            'rising' => '📈 Rising',
            'stable' => '➡️ Stable',
            'declining' => '📉 Declining',
            default => '〰️ Unknown'
        };
    }

    public function getEstimatedTimeAttribute(): string
    {
        if (!$this->learning_time_weeks) return 'Unknown';
        
        if ($this->learning_time_weeks < 2) return 'Less than 2 weeks';
        if ($this->learning_time_weeks < 5) return '1 month';
        if ($this->learning_time_weeks < 13) return ($this->learning_time_weeks / 4) . ' months';
        return ($this->learning_time_weeks / 52) . ' years';
    }

    public function getSalaryImpactFormattedAttribute(): string
    {
        if (!$this->salary_impact) return 'N/A';
        return '$' . number_format($this->salary_impact, 0) . '/year';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->target_completion_date) return false;
        return $this->target_completion_date->isPast() && $this->status !== 'completed';
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
    public function markAsLearning(): void
    {
        $this->update(['status' => 'learning']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'target_completion_date' => now(),
        ]);
    }

    public function defer(int $weeks = 4): void
    {
        $this->update([
            'status' => 'deferred',
            'target_completion_date' => now()->addWeeks($weeks),
        ]);
    }

    public function updateImpactScore(int $score): void
    {
        $this->update(['impact_score' => min(100, max(0, $score))]);
    }

    public function setTargetDate(int $weeks): void
    {
        $this->update([
            'target_completion_date' => now()->addWeeks($weeks),
            'learning_time_weeks' => $weeks,
        ]);
    }

    public function hasPrerequisites(): bool
    {
        return !empty($this->prerequisite_skills);
    }

    public function getPrerequisitesMetCount(array $userSkills): int
    {
        if (!$this->hasPrerequisites()) return 0;
        
        $userSkillNames = array_map('strtolower', $userSkills);
        $prerequisitesMet = array_filter($this->prerequisite_skills, function($prereq) use ($userSkillNames) {
            return in_array(strtolower($prereq), $userSkillNames);
        });
        
        return count($prerequisitesMet);
    }

    public function arePrerequisitesMet(array $userSkills): bool
    {
        if (!$this->hasPrerequisites()) return true;
        return $this->getPrerequisitesMetCount($userSkills) === count($this->prerequisite_skills);
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'skill_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'gap_severity' => 'required|in:low,medium,high,critical',
            'impact_score' => 'required|integer|min:0|max:100',
            'market_demand_score' => 'required|integer|min:0|max:100',
            'salary_impact' => 'nullable|numeric|min:0',
            'learning_time_weeks' => 'nullable|integer|min:1',
            'difficulty_level' => 'required|in:easy,moderate,challenging,advanced',
            'target_completion_date' => 'nullable|date|after:today',
        ];
    }
}
