<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiasAuditResult extends Model
{
    protected $table = 'scout_bias_audit_results';

    protected $fillable = [
        'company_id',
        'audit_period_start',
        'audit_period_end',
        'total_applications_analyzed',
        'bias_score',
        'fairness_rating',
        'demographic_analysis',
        'proxy_discrimination_findings',
        'decision_patterns',
        'fairness_metrics',
        'ai_detected_patterns',
        'recommendations',
        'requires_attention',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'audit_period_start' => 'datetime',
        'audit_period_end' => 'datetime',
        'demographic_analysis' => 'array',
        'proxy_discrimination_findings' => 'array',
        'decision_patterns' => 'array',
        'fairness_metrics' => 'array',
        'ai_detected_patterns' => 'array',
        'recommendations' => 'array',
        'requires_attention' => 'boolean',
        'reviewed_at' => 'datetime',
        'bias_score' => 'decimal:4',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fairnessMetrics(): HasMany
    {
        return $this->hasMany(FairnessMetric::class, 'audit_id');
    }

    public function mitigationActions(): HasMany
    {
        return $this->hasMany(BiasMitigationAction::class, 'audit_id');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRequiringAttention($query)
    {
        return $query->where('requires_attention', true);
    }

    public function scopeReviewed($query)
    {
        return $query->whereNotNull('reviewed_at');
    }

    public function scopePendingReview($query)
    {
        return $query->whereNull('reviewed_at')
                     ->where('requires_attention', true);
    }

    public function scopeByRating($query, string $rating)
    {
        return $query->where('fairness_rating', $rating);
    }

    public function scopeInPeriod($query, $startDate, $endDate = null)
    {
        $query->where('audit_period_start', '>=', $startDate);
        
        if ($endDate) {
            $query->where('audit_period_end', '<=', $endDate);
        }
        
        return $query;
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeHighBias($query, float $threshold = 0.3)
    {
        return $query->where('bias_score', '>', $threshold);
    }

    /**
     * Accessors
     */
    public function getRatingDisplayAttribute(): string
    {
        return match($this->fairness_rating) {
            'excellent' => 'Excellent - No Bias Detected',
            'good' => 'Good - Minimal Bias',
            'fair' => 'Fair - Some Concerns',
            'needs_improvement' => 'Needs Improvement',
            'concerning' => 'Concerning - Action Required',
            default => 'Unknown'
        };
    }

    public function getRatingColorAttribute(): string
    {
        return match($this->fairness_rating) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            'needs_improvement' => 'orange',
            'concerning' => 'red',
            default => 'gray'
        };
    }

    public function getBiasPercentageAttribute(): float
    {
        return round($this->bias_score * 100, 2);
    }

    public function getAuditPeriodDaysAttribute(): int
    {
        return $this->audit_period_start->diffInDays($this->audit_period_end);
    }

    public function getRecommendationsCountAttribute(): int
    {
        return count($this->recommendations ?? []);
    }

    public function getProxyAlertsCountAttribute(): int
    {
        return count($this->proxy_discrimination_findings['alerts'] ?? []);
    }

    public function getAiPatternsCountAttribute(): int
    {
        return count($this->ai_detected_patterns['patterns_detected'] ?? []);
    }

    public function getIsReviewedAttribute(): bool
    {
        return !is_null($this->reviewed_at);
    }

    public function getAuditPeriodDisplayAttribute(): string
    {
        return $this->audit_period_start->format('M d, Y') . ' - ' . $this->audit_period_end->format('M d, Y');
    }

    public function getSeverityLevelAttribute(): string
    {
        if ($this->bias_score < 0.1) return 'none';
        if ($this->bias_score < 0.2) return 'low';
        if ($this->bias_score < 0.3) return 'moderate';
        if ($this->bias_score < 0.5) return 'high';
        return 'critical';
    }

    /**
     * Methods
     */
    public function markAsReviewed(int $userId): bool
    {
        $this->reviewed_at = now();
        $this->reviewed_by = $userId;
        return $this->save();
    }

    public function flagForAttention(bool $flag = true): bool
    {
        $this->requires_attention = $flag;
        return $this->save();
    }

    public function addRecommendation(string $recommendation): bool
    {
        $recommendations = $this->recommendations ?? [];
        $recommendations[] = $recommendation;
        $this->recommendations = $recommendations;
        return $this->save();
    }

    public function getTopConcerns(int $limit = 5): array
    {
        $concerns = [];

        // From AI patterns
        $aiPatterns = $this->ai_detected_patterns['specific_concerns'] ?? [];
        $concerns = array_merge($concerns, $aiPatterns);

        // From proxy discrimination
        foreach ($this->proxy_discrimination_findings['alerts'] ?? [] as $alert) {
            $concerns[] = $alert['recommendation'];
        }

        return array_slice($concerns, 0, $limit);
    }

    public function needsImmediateAction(): bool
    {
        return $this->bias_score > 0.5 || $this->fairness_rating === 'concerning';
    }
}
