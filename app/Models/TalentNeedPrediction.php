<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentNeedPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'prediction_horizon_months',
        'prediction_generated_date',
        'prediction_target_date',
        'predicted_roles',
        'predicted_headcount',
        'predicted_skills_demand',
        'predicted_department_growth',
        'growth_factors',
        'industry_trends',
        'seasonality_factors',
        'prediction_basis',
        'confidence_score',
        'data_points_used',
        'recommendations',
        'ai_analysis',
        'actual_headcount',
        'prediction_accuracy',
        'accuracy_analysis',
        'metadata'
    ];

    protected $casts = [
        'prediction_generated_date' => 'date',
        'prediction_target_date' => 'date',
        'prediction_horizon_months' => 'integer',
        'predicted_roles' => 'array',
        'predicted_headcount' => 'integer',
        'predicted_skills_demand' => 'array',
        'predicted_department_growth' => 'array',
        'growth_factors' => 'array',
        'industry_trends' => 'array',
        'seasonality_factors' => 'array',
        'prediction_basis' => 'array',
        'confidence_score' => 'decimal:2',
        'data_points_used' => 'integer',
        'recommendations' => 'array',
        'actual_headcount' => 'integer',
        'prediction_accuracy' => 'decimal:2',
        'accuracy_analysis' => 'array',
        'metadata' => 'array'
    ];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('prediction_target_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('prediction_target_date', '<', now());
    }

    public function scopeValidated($query)
    {
        return $query->whereNotNull('actual_headcount');
    }

    public function scopeHighConfidence($query, float $threshold = 75.0)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByHorizon($query, int $months)
    {
        return $query->where('prediction_horizon_months', $months);
    }

    public function scopeRecent($query, int $months = 3)
    {
        return $query->where('prediction_generated_date', '>=', now()->subMonths($months));
    }

    // Accessors

    public function getIsActiveAttribute(): bool
    {
        return $this->prediction_target_date >= now();
    }

    public function getIsExpiredAttribute(): bool
    {
        return !$this->is_active;
    }

    public function getIsValidatedAttribute(): bool
    {
        return $this->actual_headcount !== null;
    }

    public function getConfidenceLevelAttribute(): string
    {
        $score = $this->confidence_score;

        if ($score >= 85) return 'Very High';
        if ($score >= 75) return 'High';
        if ($score >= 60) return 'Medium';
        if ($score >= 40) return 'Low';
        return 'Very Low';
    }

    public function getAccuracyLevelAttribute(): ?string
    {
        if (!$this->is_validated) {
            return null;
        }

        $accuracy = $this->prediction_accuracy;

        if ($accuracy >= 90) return 'Excellent';
        if ($accuracy >= 75) return 'Good';
        if ($accuracy >= 60) return 'Fair';
        if ($accuracy >= 40) return 'Poor';
        return 'Very Poor';
    }

    public function getDaysUntilTargetAttribute(): int
    {
        return now()->diffInDays($this->prediction_target_date, false);
    }

    public function getDaysSinceGenerationAttribute(): int
    {
        return $this->prediction_generated_date->diffInDays(now());
    }

    public function getHorizonDescriptionAttribute(): string
    {
        $months = $this->prediction_horizon_months;

        if ($months <= 3) return 'Short-term';
        if ($months <= 6) return 'Medium-term';
        if ($months <= 12) return 'Long-term';
        return 'Extended';
    }

    public function getTopPredictedRolesAttribute(): array
    {
        if (!is_array($this->predicted_roles) || empty($this->predicted_roles)) {
            return [];
        }

        return array_slice($this->predicted_roles, 0, 5);
    }

    public function getTopPredictedSkillsAttribute(): array
    {
        if (!isset($this->predicted_skills_demand['top_skills'])) {
            return [];
        }

        return array_slice($this->predicted_skills_demand['top_skills'], 0, 10, true);
    }

    public function getGrowthTrendAttribute(): string
    {
        if (!isset($this->growth_factors['trend_direction'])) {
            return 'unknown';
        }

        return $this->growth_factors['trend_direction'];
    }

    public function getHiringVelocityAttribute(): ?float
    {
        return $this->growth_factors['hiring_velocity'] ?? null;
    }

    public function getPredictionVarianceAttribute(): ?int
    {
        if (!$this->is_validated) {
            return null;
        }

        return $this->actual_headcount - $this->predicted_headcount;
    }

    public function getVariancePercentageAttribute(): ?float
    {
        if (!$this->is_validated || $this->predicted_headcount == 0) {
            return null;
        }

        return ($this->prediction_variance / $this->predicted_headcount) * 100;
    }

    // Methods

    public function validate(int $actualHeadcount, array $accuracyAnalysis = []): void
    {
        if ($this->is_validated) {
            return;
        }

        $accuracy = $this->calculateAccuracy($actualHeadcount);

        $this->update([
            'actual_headcount' => $actualHeadcount,
            'prediction_accuracy' => $accuracy,
            'accuracy_analysis' => $accuracyAnalysis
        ]);
    }

    public function calculateAccuracy(int $actualHeadcount): float
    {
        if ($this->predicted_headcount == 0) {
            return 0.0;
        }

        $variance = abs($actualHeadcount - $this->predicted_headcount);
        $accuracy = (1 - ($variance / max($actualHeadcount, $this->predicted_headcount))) * 100;

        return max(0, min(100, $accuracy));
    }

    public function wasAccurate(float $threshold = 75.0): ?bool
    {
        if (!$this->is_validated) {
            return null;
        }

        return $this->prediction_accuracy >= $threshold;
    }

    public function isNearTarget(int $days = 30): bool
    {
        return $this->is_active && $this->days_until_target <= $days;
    }

    public function hasHighConfidence(float $threshold = 75.0): bool
    {
        return $this->confidence_score >= $threshold;
    }

    public function getRolePrediction(string $role): ?int
    {
        if (!is_array($this->predicted_roles)) {
            return null;
        }

        foreach ($this->predicted_roles as $prediction) {
            if (isset($prediction['role']) && $prediction['role'] === $role) {
                return $prediction['count'] ?? null;
            }
        }

        return null;
    }

    public function getSkillDemand(string $skill): ?int
    {
        return $this->predicted_skills_demand['top_skills'][$skill] ?? null;
    }

    public function getDepartmentGrowth(string $department): ?float
    {
        return $this->predicted_department_growth[$department] ?? null;
    }

    public function getRecommendationsByPriority(): array
    {
        if (!is_array($this->recommendations)) {
            return [];
        }

        return $this->recommendations;
    }

    public function isGrowthAccelerating(): bool
    {
        return $this->growth_trend === 'accelerating';
    }

    public function isGrowthDeclining(): bool
    {
        return $this->growth_trend === 'declining';
    }

    public function isGrowthStable(): bool
    {
        return $this->growth_trend === 'stable';
    }

    public function getPredictionSummary(): string
    {
        $headcount = $this->predicted_headcount;
        $horizon = $this->prediction_horizon_months;
        $confidence = $this->confidence_level;

        return "Predicting {$headcount} hires over next {$horizon} months (Confidence: {$confidence})";
    }
}
