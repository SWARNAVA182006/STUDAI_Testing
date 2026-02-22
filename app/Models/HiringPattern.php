<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'job_id', 'source_effectiveness', 'channel_conversion_rates', 'best_performing_channel',
        'avg_time_to_hire_days', 'avg_time_to_fill_days', 'avg_candidates_per_role', 'avg_interviews_per_hire',
        'successful_hire_characteristics', 'unsuccessful_hire_patterns', 'top_performer_traits', 'quick_departure_indicators',
        'optimal_experience_ranges', 'essential_skills_by_role', 'nice_to_have_skills', 'overvalued_credentials',
        'education_correlation', 'previous_company_patterns', 'industry_transition_success',
        'compensation_benchmarks', 'offer_acceptance_rate_by_range', 'avg_negotiation_percentage',
        'interview_score_vs_performance', 'assessment_score_vs_performance', 'reference_check_correlation',
        'retention_by_hire_source', 'retention_by_experience_level', 'promotion_rate_by_hire_source',
        'predicted_high_performer_profile', 'predicted_flight_risk_profile', 'cultural_fit_predictors', 'ai_hiring_recommendations',
        'analysis_start_date', 'analysis_end_date', 'total_hires_in_period', 'confidence_score',
    ];

    protected $casts = [
        'source_effectiveness' => 'array', 'channel_conversion_rates' => 'array', 'successful_hire_characteristics' => 'array',
        'unsuccessful_hire_patterns' => 'array', 'top_performer_traits' => 'array', 'quick_departure_indicators' => 'array',
        'optimal_experience_ranges' => 'array', 'essential_skills_by_role' => 'array', 'nice_to_have_skills' => 'array',
        'overvalued_credentials' => 'array', 'education_correlation' => 'array', 'previous_company_patterns' => 'array',
        'industry_transition_success' => 'array', 'compensation_benchmarks' => 'array', 'offer_acceptance_rate_by_range' => 'array',
        'interview_score_vs_performance' => 'array', 'assessment_score_vs_performance' => 'array', 'reference_check_correlation' => 'array',
        'retention_by_hire_source' => 'array', 'retention_by_experience_level' => 'array', 'promotion_rate_by_hire_source' => 'array',
        'predicted_high_performer_profile' => 'array', 'predicted_flight_risk_profile' => 'array', 'cultural_fit_predictors' => 'array',
        'avg_time_to_hire_days' => 'decimal:1', 'avg_time_to_fill_days' => 'decimal:1', 'avg_negotiation_percentage' => 'decimal:2',
        'analysis_start_date' => 'date', 'analysis_end_date' => 'date',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function job(): BelongsTo { return $this->belongsTo(Job::class); }

    public function scopeHighConfidence($query) { return $query->where('confidence_score', '>=', 80); }
    public function scopeRecent($query) { return $query->where('analysis_end_date', '>=', now()->subMonths(6)); }

    public function getHiringEfficiencyScoreAttribute(): int
    {
        $timeScore = $this->avg_time_to_hire_days ? max(0, 100 - ($this->avg_time_to_hire_days * 2)) : 50;
        $candidateScore = $this->avg_candidates_per_role ? max(0, 100 - ($this->avg_candidates_per_role * 3)) : 50;
        return (int) (($timeScore + $candidateScore) / 2);
    }

    public function getBestHiringChannelAttribute(): ?string { return $this->best_performing_channel; }

    public function getHighPerformerProfileAttribute(): array
    {
        return [
            'traits' => $this->top_performer_traits ?? [],
            'characteristics' => $this->successful_hire_characteristics ?? [],
            'predicted_profile' => $this->predicted_high_performer_profile ?? [],
        ];
    }

    public function getRedFlagsAttribute(): array { return $this->unsuccessful_hire_patterns ?? []; }

    public function getCandidateScreeningCriteria(): array
    {
        return [
            'essential_skills' => $this->essential_skills_by_role ?? [],
            'optimal_experience' => $this->optimal_experience_ranges ?? [],
            'cultural_predictors' => $this->cultural_fit_predictors ?? [],
            'red_flags' => $this->getRedFlagsAttribute(),
            'high_performer_traits' => $this->top_performer_traits ?? [],
        ];
    }

    public function predictCandidateSuccess(array $candidateData): int
    {
        $score = 50; // Base score
        $profile = $this->predicted_high_performer_profile ?? [];

        // Experience match
        if (isset($candidateData['years_experience'], $this->optimal_experience_ranges['optimal'])) {
            $optimal = $this->optimal_experience_ranges['optimal'];
            if ($candidateData['years_experience'] >= $optimal['min'] && $candidateData['years_experience'] <= $optimal['max']) {
                $score += 15;
            }
        }

        // Skills match
        $candidateSkills = $candidateData['skills'] ?? [];
        $essentialSkills = $this->essential_skills_by_role ?? [];
        $matchCount = count(array_intersect($candidateSkills, $essentialSkills));
        $score += min(20, $matchCount * 5);

        // Cultural fit indicators
        $culturalMatch = 0;
        foreach ($this->cultural_fit_predictors ?? [] as $predictor => $weight) {
            if (isset($candidateData[$predictor]) && $candidateData[$predictor]) {
                $culturalMatch += $weight;
            }
        }
        $score += min(15, $culturalMatch);

        return min(100, max(0, $score));
    }
}
