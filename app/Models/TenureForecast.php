<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenureForecast extends Model
{
    protected $table = 'scout_tenure_forecasts';

    protected $fillable = [
        'application_id',
        'company_id',
        'user_id',
        'predicted_tenure_months',
        'tenure_range_min',
        'tenure_range_max',
        'confidence_level',
        'flight_risk_score',
        'risk_category',
        'retention_factors',
        'risk_indicators',
        'ai_insights',
        'recommendation',
        'forecast_date',
        'actual_tenure_months',
        'departure_date',
        'departure_reason',
    ];

    protected $casts = [
        'predicted_tenure_months' => 'integer',
        'tenure_range_min' => 'integer',
        'tenure_range_max' => 'integer',
        'flight_risk_score' => 'decimal:4',
        'retention_factors' => 'array',
        'risk_indicators' => 'array',
        'ai_insights' => 'array',
        'forecast_date' => 'datetime',
        'departure_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeShortTerm($query, int $months = 12)
    {
        return $query->where('predicted_tenure_months', '<', $months);
    }

    public function scopeMediumTerm($query, int $minMonths = 12, int $maxMonths = 24)
    {
        return $query->whereBetween('predicted_tenure_months', [$minMonths, $maxMonths]);
    }

    public function scopeLongTerm($query, int $months = 24)
    {
        return $query->where('predicted_tenure_months', '>=', $months);
    }

    public function scopeCriticalRisk($query, float $threshold = 0.8)
    {
        return $query->where('flight_risk_score', '>=', $threshold);
    }

    public function scopeHighRisk($query, float $min = 0.6, float $max = 0.8)
    {
        return $query->whereBetween('flight_risk_score', [$min, $max]);
    }

    public function scopeMediumRisk($query, float $min = 0.4, float $max = 0.6)
    {
        return $query->whereBetween('flight_risk_score', [$min, $max]);
    }

    public function scopeLowRisk($query, float $threshold = 0.4)
    {
        return $query->where('flight_risk_score', '<', $threshold);
    }

    public function scopeByRiskCategory($query, string $category)
    {
        return $query->where('risk_category', $category);
    }

    public function scopeByConfidence($query, string $level)
    {
        return $query->where('confidence_level', $level);
    }

    public function scopeWithActualTenure($query)
    {
        return $query->whereNotNull('actual_tenure_months');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('forecast_date', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getTenureCategoryAttribute(): string
    {
        if ($this->predicted_tenure_months < 6) {
            return 'Very Short Term';
        } elseif ($this->predicted_tenure_months < 12) {
            return 'Short Term';
        } elseif ($this->predicted_tenure_months < 24) {
            return 'Medium Term';
        } elseif ($this->predicted_tenure_months < 36) {
            return 'Long Term';
        } else {
            return 'Very Long Term';
        }
    }

    public function getRiskLevelAttribute(): string
    {
        return match($this->risk_category) {
            'critical' => 'Critical Risk',
            'high' => 'High Risk',
            'medium' => 'Medium Risk',
            'low' => 'Low Risk',
            default => 'Unknown'
        };
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk_category) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }

    public function getIsFlightRiskAttribute(): bool
    {
        return $this->flight_risk_score >= 0.6;
    }

    public function getConfidenceDisplayAttribute(): string
    {
        return match($this->confidence_level) {
            'very_high' => 'Very High Confidence',
            'high' => 'High Confidence',
            'moderate' => 'Moderate Confidence',
            'low' => 'Low Confidence',
            default => 'Unknown'
        };
    }

    public function getTenureRangeDisplayAttribute(): string
    {
        return "{$this->tenure_range_min} - {$this->tenure_range_max} months";
    }

    public function getTenureYearsAttribute(): float
    {
        return round($this->predicted_tenure_months / 12, 1);
    }

    public function getFlightRiskPercentageAttribute(): float
    {
        return round($this->flight_risk_score * 100, 2);
    }

    public function getRetentionFactorsSummaryAttribute(): string
    {
        $factors = $this->retention_factors ?? [];
        $top = array_slice($factors, 0, 3);
        
        if (empty($top)) {
            return 'No retention factors identified';
        }
        
        return implode(', ', array_map(function($factor) {
            return is_array($factor) ? $factor['name'] : $factor;
        }, $top));
    }

    public function getRiskIndicatorsSummaryAttribute(): string
    {
        $indicators = $this->risk_indicators ?? [];
        $top = array_slice($indicators, 0, 3);
        
        if (empty($top)) {
            return 'No risk indicators identified';
        }
        
        return implode(', ', array_map(function($indicator) {
            return is_array($indicator) ? $indicator['name'] : $indicator;
        }, $top));
    }

    public function getDaysSinceForecastAttribute(): int
    {
        return $this->forecast_date->diffInDays(now());
    }

    public function getWasAccurateAttribute(): ?bool
    {
        if (!$this->actual_tenure_months) {
            return null;
        }

        $margin = max(3, $this->predicted_tenure_months * 0.15);
        $difference = abs($this->predicted_tenure_months - $this->actual_tenure_months);

        return $difference <= $margin;
    }

    public function getExpectedDepartureDateAttribute(): ?\Carbon\Carbon
    {
        if (!$this->application->hired_at) {
            return null;
        }

        return $this->application->hired_at->addMonths($this->predicted_tenure_months);
    }

    /**
     * Methods
     */
    public function recordActualTenure(int $months, string $reason = null, $departureDate = null): bool
    {
        $this->actual_tenure_months = $months;
        $this->departure_reason = $reason;
        $this->departure_date = $departureDate ?? now();
        return $this->save();
    }

    public function isHighRetention(): bool
    {
        return $this->predicted_tenure_months >= 24 && $this->flight_risk_score < 0.4;
    }

    public function isCriticalRisk(): bool
    {
        return $this->flight_risk_score >= 0.8 || $this->predicted_tenure_months < 6;
    }

    public function needsIntervention(): bool
    {
        return $this->isCriticalRisk() || ($this->flight_risk_score >= 0.6 && $this->predicted_tenure_months < 12);
    }

    public function getTopRetentionFactors(int $count = 3): array
    {
        $factors = $this->retention_factors ?? [];
        
        usort($factors, function($a, $b) {
            $scoreA = is_array($a) ? ($a['score'] ?? 0) : 0;
            $scoreB = is_array($b) ? ($b['score'] ?? 0) : 0;
            return $scoreB <=> $scoreA;
        });
        
        return array_slice($factors, 0, $count);
    }

    public function getTopRiskIndicators(int $count = 3): array
    {
        $indicators = $this->risk_indicators ?? [];
        
        usort($indicators, function($a, $b) {
            $severityA = is_array($a) ? ($a['severity'] ?? 0) : 0;
            $severityB = is_array($b) ? ($b['severity'] ?? 0) : 0;
            return $severityB <=> $severityA;
        });
        
        return array_slice($indicators, 0, $count);
    }

    public function updateRiskScore(float $newScore): bool
    {
        $this->flight_risk_score = $newScore;
        
        $this->risk_category = match(true) {
            $newScore >= 0.8 => 'critical',
            $newScore >= 0.6 => 'high',
            $newScore >= 0.4 => 'medium',
            default => 'low'
        };
        
        return $this->save();
    }
}
