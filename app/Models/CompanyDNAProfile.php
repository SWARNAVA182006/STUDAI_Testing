<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyDNAProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'mission_statement',
        'vision_statement',
        'core_values',
        'cultural_dna',
        'success_traits',
        'work_style_preferences',
        'communication_patterns',
        'decision_making_style',
        'company_size_category',
        'growth_stage',
        'industry_vertical',
        'employee_count',
        'avg_tenure_months',
        'retention_rate_1yr',
        'promotion_rate',
        'dna_completeness_score',
        'data_quality_score',
        'analysis_confidence',
        'last_analyzed_at',
        'total_employees_analyzed',
        'total_hires_analyzed',
        'ai_analysis_summary',
    ];

    protected $casts = [
        'core_values' => 'array',
        'cultural_dna' => 'array',
        'success_traits' => 'array',
        'work_style_preferences' => 'array',
        'communication_patterns' => 'array',
        'decision_making_style' => 'array',
        'ai_analysis_summary' => 'array',
        'last_analyzed_at' => 'datetime',
        'avg_tenure_months' => 'decimal:1',
        'retention_rate_1yr' => 'decimal:2',
        'promotion_rate' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cultureAnalysis(): HasOne
    {
        return $this->hasOne(CultureAnalysis::class);
    }

    public function hiringPatterns(): HasMany
    {
        return $this->hasMany(HiringPattern::class, 'company_id', 'company_id');
    }

    public function successIndicators(): HasMany
    {
        return $this->hasMany(SuccessIndicator::class, 'company_id', 'company_id');
    }

    public function teamDynamics(): HasMany
    {
        return $this->hasMany(TeamDynamic::class, 'company_id', 'company_id');
    }

    // Scopes
    public function scopeComplete($query)
    {
        return $query->where('dna_completeness_score', '>=', 70);
    }

    public function scopeHighConfidence($query)
    {
        return $query->where('analysis_confidence', '>=', 80);
    }

    public function scopeRecentlyAnalyzed($query)
    {
        return $query->where('last_analyzed_at', '>=', now()->subWeek());
    }

    public function scopeNeedsRefresh($query)
    {
        return $query->where('last_analyzed_at', '<', now()->subMonth())
            ->orWhereNull('last_analyzed_at');
    }

    // Accessors
    public function getIsCompleteAttribute(): bool
    {
        return $this->dna_completeness_score >= 70;
    }

    public function getCompletionStatusAttribute(): string
    {
        if ($this->dna_completeness_score >= 90) return 'Excellent';
        if ($this->dna_completeness_score >= 70) return 'Good';
        if ($this->dna_completeness_score >= 50) return 'Fair';
        return 'Incomplete';
    }

    public function getConfidenceLevelAttribute(): string
    {
        if ($this->analysis_confidence >= 80) return 'High';
        if ($this->analysis_confidence >= 60) return 'Medium';
        return 'Low';
    }

    public function getDnaHealthScoreAttribute(): int
    {
        return (int) (
            ($this->dna_completeness_score * 0.4) +
            ($this->data_quality_score * 0.3) +
            ($this->analysis_confidence * 0.3)
        );
    }

    public function getCulturalArchetypesAttribute(): array
    {
        $archetypes = [];
        $dna = $this->cultural_dna ?? [];

        if (($dna['innovation_focus'] ?? 0) >= 70) $archetypes[] = 'Innovative';
        if (($dna['hierarchy_level'] ?? 0) < 30) $archetypes[] = 'Flat';
        if (($dna['pace'] ?? 0) >= 70) $archetypes[] = 'Fast-Paced';
        if (($dna['collaboration_score'] ?? 0) >= 70) $archetypes[] = 'Collaborative';
        if (($dna['remote_score'] ?? 0) >= 70) $archetypes[] = 'Remote-First';

        return $archetypes;
    }

    public function getTopSuccessTraitsAttribute(): array
    {
        $traits = $this->success_traits ?? [];
        if (empty($traits)) return [];

        arsort($traits);
        return array_slice($traits, 0, 5, true);
    }

    public function getDataQualityBadgeAttribute(): string
    {
        $score = $this->data_quality_score;
        if ($score >= 90) return '🟢 Excellent';
        if ($score >= 70) return '🟡 Good';
        if ($score >= 50) return '🟠 Fair';
        return '🔴 Poor';
    }

    // Helper Methods
    public function needsAnalysis(): bool
    {
        return !$this->last_analyzed_at || $this->last_analyzed_at->lt(now()->subMonth());
    }

    public function canGenerateJobRequirements(): bool
    {
        return $this->dna_completeness_score >= 60 && $this->analysis_confidence >= 60;
    }

    public function getCulturalFitCriteria(): array
    {
        return [
            'values' => $this->core_values ?? [],
            'work_style' => $this->work_style_preferences ?? [],
            'communication' => $this->communication_patterns ?? [],
            'success_traits' => $this->getTopSuccessTraitsAttribute(),
            'decision_style' => $this->decision_making_style ?? [],
        ];
    }

    public function updateCompletionScore(): void
    {
        $score = 0;
        $maxScore = 100;

        // Mission/Vision (10 points each)
        if ($this->mission_statement) $score += 10;
        if ($this->vision_statement) $score += 10;

        // Core values (10 points)
        if (!empty($this->core_values)) $score += 10;

        // DNA components (10 points each)
        if (!empty($this->cultural_dna)) $score += 10;
        if (!empty($this->success_traits)) $score += 10;
        if (!empty($this->work_style_preferences)) $score += 10;
        if (!empty($this->communication_patterns)) $score += 10;

        // Metrics (5 points each)
        if ($this->avg_tenure_months) $score += 5;
        if ($this->retention_rate_1yr) $score += 5;
        if ($this->promotion_rate) $score += 5;

        // Analysis data (10 points)
        if ($this->total_employees_analyzed >= 10) $score += 10;
        if ($this->total_hires_analyzed >= 5) $score += 10;

        $this->dna_completeness_score = min($score, $maxScore);
        $this->save();
    }
}
