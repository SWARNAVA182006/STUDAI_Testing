<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionTrace extends Model
{
    protected $table = 'scout_decision_traces';

    protected $fillable = [
        'application_id',
        'prediction_type',
        'explanation_json',
        'model_version',
        'final_score',
        'confidence_level',
        'traced_at',
    ];

    protected $casts = [
        'explanation_json' => 'array',
        'final_score' => 'decimal:4',
        'traced_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Scopes
     */
    public function scopeForApplication($query, int $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeByType($query, string $predictionType)
    {
        return $query->where('prediction_type', $predictionType);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('traced_at', '>=', now()->subDays($days));
    }

    public function scopeByConfidence($query, string $level)
    {
        return $query->where('confidence_level', $level);
    }

    public function scopeByModelVersion($query, string $version)
    {
        return $query->where('model_version', $version);
    }

    /**
     * Accessors
     */
    public function getConfidenceDisplayAttribute(): string
    {
        return match ($this->confidence_level) {
            'very_high' => 'Very High Confidence',
            'high' => 'High Confidence',
            'moderate' => 'Moderate Confidence',
            'low' => 'Low Confidence',
            default => 'Unknown',
        };
    }

    public function getPredictionTypeDisplayAttribute(): string
    {
        return match ($this->prediction_type) {
            'success_probability' => 'Success Probability',
            'tenure_forecast' => 'Tenure Forecast',
            'productivity_estimate' => 'Productivity Estimate',
            'flight_risk' => 'Flight Risk Assessment',
            'development_plan' => 'Development Plan',
            'onboarding_plan' => 'Onboarding Plan',
            'career_path' => 'Career Path Prediction',
            default => ucwords(str_replace('_', ' ', $this->prediction_type)),
        };
    }

    public function getKeyDriversAttribute(): array
    {
        return $this->explanation_json['key_drivers'] ?? [];
    }

    public function getInputFactorsAttribute(): array
    {
        return $this->explanation_json['input_factors'] ?? [];
    }

    public function getScoringBreakdownAttribute(): array
    {
        return $this->explanation_json['scoring_breakdown'] ?? [];
    }

    /**
     * Methods
     */
    public function getTopDrivers(int $count = 3): array
    {
        return array_slice($this->key_drivers, 0, $count);
    }

    public function wasGeneratedByVersion(string $version): bool
    {
        return $this->model_version === $version;
    }
}
