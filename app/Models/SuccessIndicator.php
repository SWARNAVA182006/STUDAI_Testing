<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'employee_type', 'tenure_months', 'promotions_count', 'performance_rating', 'is_culture_champion',
        'technical_skills', 'soft_skills', 'leadership_qualities', 'domain_expertise',
        'work_preferences', 'communication_style', 'problem_solving_approach', 'learning_style',
        'values_alignment_score', 'culture_fit_score', 'team_collaboration_score', 'initiative_score',
        'education_level', 'previous_companies', 'years_of_experience_at_hire', 'hire_source',
        'promotion_path', 'skill_development_path', 'project_successes', 'impact_score',
        'peer_feedback_score', 'mentorship_activity', 'is_knowledge_sharer', 'collaboration_metrics',
        'success_factors', 'unique_strengths', 'transferable_patterns', 'ai_success_summary',
    ];

    protected $casts = [
        'technical_skills' => 'array', 'soft_skills' => 'array', 'leadership_qualities' => 'array', 'domain_expertise' => 'array',
        'work_preferences' => 'array', 'communication_style' => 'array', 'problem_solving_approach' => 'array', 'learning_style' => 'array',
        'previous_companies' => 'array', 'promotion_path' => 'array', 'skill_development_path' => 'array', 'project_successes' => 'array',
        'collaboration_metrics' => 'array', 'success_factors' => 'array', 'unique_strengths' => 'array', 'transferable_patterns' => 'array',
        'performance_rating' => 'decimal:2', 'impact_score' => 'decimal:2', 'peer_feedback_score' => 'decimal:2',
        'is_culture_champion' => 'boolean', 'is_knowledge_sharer' => 'boolean',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeTopPerformers($query) { return $query->where('employee_type', 'top_performer'); }
    public function scopeCulturalChampions($query) { return $query->where('is_culture_champion', true); }
    public function scopeHighCultureFit($query) { return $query->where('culture_fit_score', '>=', 80); }

    public function getPerformanceCategoryAttribute(): string
    {
        if ($this->employee_type === 'top_performer') return 'Top Performer';
        if ($this->employee_type === 'average') return 'Average Performer';
        return 'Underperformer';
    }

    public function getCulturalFitLevelAttribute(): string
    {
        $score = $this->culture_fit_score;
        if ($score >= 90) return 'Exceptional Fit';
        if ($score >= 75) return 'Strong Fit';
        if ($score >= 60) return 'Moderate Fit';
        return 'Weak Fit';
    }

    public function getCareerProgressionRateAttribute(): string
    {
        if ($this->tenure_months == 0) return 'New Hire';
        $monthsPerPromotion = $this->promotions_count > 0 ? $this->tenure_months / $this->promotions_count : 0;
        if ($monthsPerPromotion > 0 && $monthsPerPromotion <= 18) return 'Fast Track';
        if ($monthsPerPromotion > 18 && $monthsPerPromotion <= 36) return 'Steady Growth';
        return 'Stable Contributor';
    }

    public function getOverallSuccessScoreAttribute(): int
    {
        return (int) (
            ($this->performance_rating * 10 * 0.30) + // 30% weight
            ($this->culture_fit_score * 0.25) + // 25% weight
            ($this->team_collaboration_score * 0.20) + // 20% weight
            ($this->initiative_score * 0.15) + // 15% weight
            ($this->impact_score * 10 * 0.10) // 10% weight
        );
    }

    public function isHighPerformer(): bool { return $this->employee_type === 'top_performer'; }
    public function isCultureFit(): bool { return $this->culture_fit_score >= 75; }

    public function getTransferableTraits(): array
    {
        return [
            'soft_skills' => $this->soft_skills ?? [],
            'work_preferences' => $this->work_preferences ?? [],
            'communication_style' => $this->communication_style ?? [],
            'problem_solving' => $this->problem_solving_approach ?? [],
            'learning_style' => $this->learning_style ?? [],
            'transferable_patterns' => $this->transferable_patterns ?? [],
        ];
    }

    public function getCandidateMatchingProfile(): array
    {
        return [
            'required_technical_skills' => $this->technical_skills ?? [],
            'valued_soft_skills' => $this->soft_skills ?? [],
            'work_style_preferences' => $this->work_preferences ?? [],
            'communication_approach' => $this->communication_style ?? [],
            'cultural_attributes' => [
                'values_alignment' => $this->values_alignment_score,
                'culture_champion' => $this->is_culture_champion,
                'collaboration_focus' => $this->team_collaboration_score >= 70,
            ],
            'success_indicators' => $this->success_factors ?? [],
        ];
    }
}
