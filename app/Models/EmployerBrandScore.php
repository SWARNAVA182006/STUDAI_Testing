<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerBrandScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'measurement_date',
        'measurement_period',
        'overall_brand_score',
        'application_experience_score',
        'communication_score',
        'interview_experience_score',
        'feedback_quality_score',
        'transparency_score',
        'respect_score',
        'average_response_time_hours',
        'total_interactions',
        'positive_interactions',
        'negative_interactions',
        'feedback_requests_sent',
        'feedback_responses_received',
        'feedback_response_rate',
        'positive_feedback_themes',
        'negative_feedback_themes',
        'improvement_recommendations',
        'industry_benchmark_score',
        'brand_health_trend',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'overall_brand_score' => 'decimal:2',
        'application_experience_score' => 'decimal:2',
        'communication_score' => 'decimal:2',
        'interview_experience_score' => 'decimal:2',
        'feedback_quality_score' => 'decimal:2',
        'transparency_score' => 'decimal:2',
        'respect_score' => 'decimal:2',
        'average_response_time_hours' => 'decimal:2',
        'feedback_response_rate' => 'decimal:2',
        'industry_benchmark_score' => 'decimal:2',
        'positive_feedback_themes' => 'array',
        'negative_feedback_themes' => 'array',
        'improvement_recommendations' => 'array',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scopes
     */
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('measurement_period', $period);
    }

    public function scopeRecent($query, $months = 3)
    {
        return $query->where('measurement_date', '>=', now()->subMonths($months));
    }

    public function scopeHealthy($query, $minScore = 70)
    {
        return $query->where('overall_brand_score', '>=', $minScore);
    }

    public function scopeDeclining($query)
    {
        return $query->where('brand_health_trend', 'declining');
    }

    /**
     * Accessors
     */
    public function getBrandHealthStatusAttribute(): string
    {
        $score = $this->overall_brand_score;
        
        if ($score >= 85) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 55) return 'fair';
        if ($score >= 40) return 'poor';
        return 'critical';
    }

    public function getPositiveSentimentRateAttribute(): float
    {
        if ($this->total_interactions === 0) return 0;
        return ($this->positive_interactions / $this->total_interactions) * 100;
    }

    public function getNegativeSentimentRateAttribute(): float
    {
        if ($this->total_interactions === 0) return 0;
        return ($this->negative_interactions / $this->total_interactions) * 100;
    }

    /**
     * Helper methods
     */
    public function calculateOverallScore(): void
    {
        $componentScores = [
            $this->application_experience_score => 0.20,
            $this->communication_score => 0.25,
            $this->interview_experience_score => 0.20,
            $this->feedback_quality_score => 0.15,
            $this->transparency_score => 0.10,
            $this->respect_score => 0.10,
        ];

        $overallScore = 0;
        foreach ($componentScores as $score => $weight) {
            $overallScore += ($score ?? 0) * $weight;
        }

        $this->update(['overall_brand_score' => round($overallScore, 2)]);
    }

    public function determineTrend(): void
    {
        $previousMeasurement = self::where('company_id', $this->company_id)
            ->where('measurement_period', $this->measurement_period)
            ->where('measurement_date', '<', $this->measurement_date)
            ->orderBy('measurement_date', 'desc')
            ->first();

        if (!$previousMeasurement) {
            $this->update(['brand_health_trend' => 'stable']);
            return;
        }

        $scoreDiff = $this->overall_brand_score - $previousMeasurement->overall_brand_score;

        if ($scoreDiff >= 5) {
            $trend = 'improving';
        } elseif ($scoreDiff <= -5) {
            $trend = 'declining';
        } else {
            $trend = 'stable';
        }

        $this->update(['brand_health_trend' => $trend]);
    }
}
