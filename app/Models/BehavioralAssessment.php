<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BehavioralAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scout_behavioral_assessments';

    protected $fillable = [
        'application_id',
        'job_id',
        'company_id',
        'status',
        'scenario_count',
        'cultural_fit_score',
        'emotional_intelligence_score',
        'leadership_score',
        'communication_score',
        'approach_quality_score',
        'reasoning_quality_score',
        'overall_score',
        'assessment_type',
        'focus_areas',
        'company_culture_context',
        'thriving_likelihood',
        'recommendation',
        'final_insights',
        'metadata',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'focus_areas' => 'array',
        'company_culture_context' => 'array',
        'final_insights' => 'array',
        'metadata' => 'array',
        'cultural_fit_score' => 'float',
        'emotional_intelligence_score' => 'float',
        'leadership_score' => 'float',
        'communication_score' => 'float',
        'approach_quality_score' => 'float',
        'reasoning_quality_score' => 'float',
        'overall_score' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the application this assessment belongs to
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the job this assessment is for
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the company this assessment belongs to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all scenarios for this assessment
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(SituationalScenario::class);
    }

    /**
     * Get all responses for this assessment
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ScenarioResponse::class);
    }

    /**
     * Scope: Get pending assessments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get in-progress assessments
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Get completed assessments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get expired assessments
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope: Get assessments with high cultural fit (75+)
     */
    public function scopeHighCulturalFit($query)
    {
        return $query->where('cultural_fit_score', '>=', 75);
    }

    /**
     * Scope: Get assessments with low cultural fit (<50)
     */
    public function scopeLowCulturalFit($query)
    {
        return $query->where('cultural_fit_score', '<', 50);
    }

    /**
     * Scope: Get assessments with strong hire recommendation
     */
    public function scopeStrongHire($query)
    {
        return $query->where('recommendation', 'like', 'STRONG HIRE%');
    }

    /**
     * Scope: Get assessments for a specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get assessments for a specific job
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope: Get assessments by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('assessment_type', $type);
    }

    /**
     * Accessor: Get overall fit score (weighted average)
     */
    public function getOverallFitScoreAttribute(): float
    {
        if (!$this->cultural_fit_score) {
            return 0.0;
        }

        // Weighted scoring: Cultural fit is most important
        $culturalWeight = 0.45;
        $eiWeight = 0.25;
        $leadershipWeight = 0.15;
        $communicationWeight = 0.15;

        return round(
            ($this->cultural_fit_score * $culturalWeight) +
            (($this->emotional_intelligence_score ?? 0) * $eiWeight) +
            (($this->leadership_score ?? 0) * $leadershipWeight) +
            (($this->communication_score ?? 0) * $communicationWeight),
            1
        );
    }

    /**
     * Accessor: Get cultural fit level (text description)
     */
    public function getCulturalFitLevelAttribute(): string
    {
        $score = $this->cultural_fit_score ?? 0;

        return match(true) {
            $score >= 85 => 'Excellent Fit',
            $score >= 70 => 'Good Fit',
            $score >= 55 => 'Moderate Fit',
            $score >= 40 => 'Poor Fit',
            default => 'Misalignment'
        };
    }

    /**
     * Accessor: Get EI level (text description)
     */
    public function getEiLevelAttribute(): string
    {
        $score = $this->emotional_intelligence_score ?? 0;

        return match(true) {
            $score >= 85 => 'Very High',
            $score >= 70 => 'High',
            $score >= 55 => 'Moderate',
            $score >= 40 => 'Developing',
            default => 'Low'
        };
    }

    /**
     * Accessor: Get leadership potential level
     */
    public function getLeadershipPotentialAttribute(): string
    {
        $score = $this->leadership_score ?? 0;

        return match(true) {
            $score >= 80 => 'Executive Level',
            $score >= 65 => 'Senior Management',
            $score >= 50 => 'Team Lead',
            $score >= 35 => 'Emerging Leader',
            default => 'Individual Contributor'
        };
    }

    /**
     * Accessor: Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->scenario_count === 0) {
            return 0.0;
        }

        $completedResponses = $this->responses()->count();
        return round(($completedResponses / $this->scenario_count) * 100, 1);
    }

    /**
     * Accessor: Check if assessment is complete
     */
    public function getIsCompleteAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Accessor: Check if assessment has started
     */
    public function getHasStartedAttribute(): bool
    {
        return in_array($this->status, ['in_progress', 'completed']);
    }

    /**
     * Accessor: Check if assessment is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Accessor: Get thriving probability (numeric value)
     */
    public function getThrivingProbabilityAttribute(): float
    {
        return $this->overall_fit_score;
    }

    /**
     * Accessor: Get recommendation level (numeric for sorting)
     */
    public function getRecommendationLevelAttribute(): int
    {
        if (!$this->recommendation) {
            return 0;
        }

        return match(true) {
            str_contains($this->recommendation, 'STRONG HIRE') => 5,
            str_contains($this->recommendation, 'RECOMMEND') => 4,
            str_contains($this->recommendation, 'CONSIDER') => 3,
            str_contains($this->recommendation, 'CAUTION') => 2,
            str_contains($this->recommendation, 'NOT RECOMMENDED') => 1,
            default => 0
        };
    }

    /**
     * Accessor: Get remaining scenarios count
     */
    public function getRemainingScenariosCountAttribute(): int
    {
        return max(0, $this->scenario_count - $this->responses()->count());
    }

    /**
     * Accessor: Get completed scenarios count
     */
    public function getCompletedScenariosCountAttribute(): int
    {
        return $this->responses()->count();
    }

    /**
     * Mark assessment as started
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }

    /**
     * Mark assessment as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Mark assessment as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => 'expired'
        ]);
    }

    /**
     * Update cultural fit score
     */
    public function updateCulturalFitScore(float $score): bool
    {
        return $this->update(['cultural_fit_score' => $score]);
    }

    /**
     * Update emotional intelligence score
     */
    public function updateEIScore(float $score): bool
    {
        return $this->update(['emotional_intelligence_score' => $score]);
    }

    /**
     * Update leadership score
     */
    public function updateLeadershipScore(float $score): bool
    {
        return $this->update(['leadership_score' => $score]);
    }

    /**
     * Check if assessment can still be taken
     */
    public function canBeTaken(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']) && !$this->is_complete;
    }

    /**
     * Get next unanswered scenario
     */
    public function getNextScenario(): ?SituationalScenario
    {
        $answeredScenarioIds = $this->responses()->pluck('situational_scenario_id');
        
        return $this->scenarios()
            ->whereNotIn('id', $answeredScenarioIds)
            ->orderBy('scenario_number')
            ->first();
    }

    /**
     * Get scenario by number
     */
    public function getScenarioByNumber(int $number): ?SituationalScenario
    {
        return $this->scenarios()->where('scenario_number', $number)->first();
    }

    /**
     * Check if candidate is likely to thrive
     */
    public function isLikelyToThrive(): bool
    {
        return $this->thriving_likelihood && 
               str_contains(strtolower($this->thriving_likelihood), 'likely to thrive');
    }

    /**
     * Check if recommendation is positive (STRONG HIRE or RECOMMEND)
     */
    public function hasPositiveRecommendation(): bool
    {
        if (!$this->recommendation) {
            return false;
        }

        return str_contains($this->recommendation, 'STRONG HIRE') || 
               str_contains($this->recommendation, 'RECOMMEND');
    }

    /**
     * Get key strengths from final insights
     */
    public function getKeyStrengthsAttribute(): array
    {
        return $this->final_insights['key_strengths'] ?? [];
    }

    /**
     * Get development areas from final insights
     */
    public function getDevelopmentAreasAttribute(): array
    {
        return $this->final_insights['development_areas'] ?? [];
    }

    /**
     * Get onboarding recommendations from final insights
     */
    public function getOnboardingRecommendationsAttribute(): array
    {
        return $this->final_insights['onboarding_recommendations'] ?? [];
    }
}
