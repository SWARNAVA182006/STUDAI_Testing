<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FairnessMetric extends Model
{
    protected $table = 'scout_fairness_metrics';

    protected $fillable = [
        'company_id',
        'job_id',
        'audit_id',
        'metric_type',
        'metric_value',
        'comparison_group',
        'reference_group',
        'disparate_impact_ratio',
        'passes_threshold',
        'sample_size',
        'statistical_significance',
        'additional_data',
        'measured_at',
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'disparate_impact_ratio' => 'decimal:4',
        'passes_threshold' => 'boolean',
        'statistical_significance' => 'decimal:4',
        'additional_data' => 'array',
        'measured_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(BiasAuditResult::class, 'audit_id');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeFailingThreshold($query)
    {
        return $query->where('passes_threshold', false);
    }

    public function scopePassingThreshold($query)
    {
        return $query->where('passes_threshold', true);
    }

    public function scopeInPeriod($query, $startDate, $endDate = null)
    {
        $query->where('measured_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('measured_at', '<=', $endDate);
        }
        
        return $query;
    }

    public function scopeStatisticallySignificant($query, float $threshold = 0.05)
    {
        return $query->where('statistical_significance', '<=', $threshold);
    }

    public function scopeWithSufficientSample($query, int $minSize = 30)
    {
        return $query->where('sample_size', '>=', $minSize);
    }

    public function scopeDisparateImpact($query)
    {
        return $query->whereNotNull('disparate_impact_ratio')
                     ->where('disparate_impact_ratio', '<', 0.8);
    }

    /**
     * Accessors
     */
    public function getMetricTypeDisplayAttribute(): string
    {
        return match($this->metric_type) {
            'disparate_impact' => 'Disparate Impact Analysis',
            'selection_rate_parity' => 'Selection Rate Parity',
            'advancement_rate' => 'Advancement Rate',
            'offer_rate' => 'Offer Rate',
            'rejection_rate' => 'Rejection Rate',
            'interview_rate' => 'Interview Rate',
            'timeline_consistency' => 'Timeline Consistency',
            default => ucwords(str_replace('_', ' ', $this->metric_type))
        };
    }

    public function getStatusAttribute(): string
    {
        if ($this->passes_threshold) {
            return 'passing';
        }
        
        if ($this->disparate_impact_ratio && $this->disparate_impact_ratio < 0.8) {
            return 'disparate_impact';
        }
        
        return 'failing';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'passing' => 'green',
            'failing' => 'yellow',
            'disparate_impact' => 'red',
            default => 'gray'
        };
    }

    public function getMetricPercentageAttribute(): float
    {
        return round($this->metric_value * 100, 2);
    }

    public function getDisparateImpactPercentageAttribute(): ?float
    {
        if (!$this->disparate_impact_ratio) {
            return null;
        }
        return round($this->disparate_impact_ratio * 100, 2);
    }

    public function getIsStatisticallySignificantAttribute(): bool
    {
        if (!$this->statistical_significance) {
            return false;
        }
        return $this->statistical_significance <= 0.05;
    }

    public function getHasSufficientSampleAttribute(): bool
    {
        return $this->sample_size >= 30;
    }

    public function getComparisonDisplayAttribute(): string
    {
        if (!$this->comparison_group || !$this->reference_group) {
            return 'N/A';
        }
        return "{$this->comparison_group} vs {$this->reference_group}";
    }

    public function getSeverityAttribute(): string
    {
        if ($this->passes_threshold) {
            return 'none';
        }

        if ($this->disparate_impact_ratio) {
            if ($this->disparate_impact_ratio < 0.5) return 'critical';
            if ($this->disparate_impact_ratio < 0.7) return 'high';
            if ($this->disparate_impact_ratio < 0.8) return 'moderate';
        }

        return 'low';
    }

    /**
     * Methods
     */
    public function meetsEEOCStandards(): bool
    {
        // EEOC 4/5ths (80%) rule
        if ($this->disparate_impact_ratio) {
            return $this->disparate_impact_ratio >= 0.8;
        }
        
        return $this->passes_threshold;
    }

    public function updateThresholdStatus(): bool
    {
        $this->passes_threshold = $this->meetsEEOCStandards();
        return $this->save();
    }

    public function getRecommendation(): string
    {
        if ($this->passes_threshold) {
            return 'Metric is within acceptable range. Continue monitoring.';
        }

        if ($this->disparate_impact_ratio && $this->disparate_impact_ratio < 0.8) {
            return 'Disparate impact detected. Review hiring criteria and processes immediately.';
        }

        return 'Metric below threshold. Investigate and address potential bias.';
    }
}
