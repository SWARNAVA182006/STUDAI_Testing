<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SilverMedalist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'job_id',
        'application_id',
        'silver_medal_reason',
        'interview_score',
        'skill_score',
        'cultural_fit_score',
        'strengths',
        'development_areas',
        'interviewer_feedback',
        'ai_recommendation',
        'suitable_future_roles',
        're_engagement_status',
        'silver_medal_date',
        'last_contacted_at',
        'contact_attempts',
        'next_reach_out_date',
        'added_to_talent_pipeline',
        'talent_pipeline_id',
    ];

    protected $casts = [
        'interview_score' => 'decimal:2',
        'skill_score' => 'decimal:2',
        'cultural_fit_score' => 'decimal:2',
        'strengths' => 'array',
        'development_areas' => 'array',
        'suitable_future_roles' => 'array',
        'silver_medal_date' => 'date',
        'last_contacted_at' => 'date',
        'next_reach_out_date' => 'date',
        'added_to_talent_pipeline' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function talentPipeline(): BelongsTo
    {
        return $this->belongsTo(TalentPipeline::class);
    }

    /**
     * Scopes
     */
    public function scopeNotContacted($query)
    {
        return $query->where('re_engagement_status', 'not_contacted');
    }

    public function scopeReadyForReEngagement($query)
    {
        return $query->whereNotNull('next_reach_out_date')
            ->where('next_reach_out_date', '<=', now())
            ->whereIn('re_engagement_status', ['not_contacted', 'contacted']);
    }

    public function scopeStillInterested($query)
    {
        return $query->whereIn('re_engagement_status', ['not_contacted', 'contacted', 'interested']);
    }

    public function scopeHighPotential($query)
    {
        return $query->where(function($q) {
            $q->where('interview_score', '>=', 75)
                ->orWhere('skill_score', '>=', 75)
                ->orWhere('cultural_fit_score', '>=', 75);
        });
    }

    public function scopeRecent($query, $months = 6)
    {
        return $query->where('silver_medal_date', '>=', now()->subMonths($months));
    }

    public function scopeNotAddedToPipeline($query)
    {
        return $query->where('added_to_talent_pipeline', false);
    }

    /**
     * Accessors
     */
    public function getOverallScoreAttribute(): float
    {
        $scores = array_filter([
            $this->interview_score,
            $this->skill_score,
            $this->cultural_fit_score,
        ]);

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    public function getMonthsSinceSilverMedalAttribute(): int
    {
        return now()->diffInMonths($this->silver_medal_date);
    }

    public function getIsReadyForReEngagementAttribute(): bool
    {
        return $this->next_reach_out_date && 
               $this->next_reach_out_date <= now() &&
               in_array($this->re_engagement_status, ['not_contacted', 'contacted']);
    }

    public function getShouldAddToPipelineAttribute(): bool
    {
        return !$this->added_to_talent_pipeline && 
               $this->overall_score >= 70 &&
               $this->re_engagement_status !== 'not_interested';
    }

    /**
     * Helper methods
     */
    public function recordContactAttempt(string $outcome = null): void
    {
        $this->increment('contact_attempts');
        
        $this->update([
            'last_contacted_at' => now(),
            're_engagement_status' => $outcome ?? 'contacted',
        ]);

        // Set next reach out date based on outcome
        if ($outcome === 'interested') {
            $this->update(['next_reach_out_date' => now()->addWeeks(2)]);
        } elseif ($outcome === 'contacted' && !$this->next_reach_out_date) {
            $this->update(['next_reach_out_date' => now()->addMonths(1)]);
        }
    }

    public function addToTalentPipeline(TalentPipeline $pipeline): PipelineCandidate
    {
        $pipelineCandidate = $pipeline->addCandidate($this->user, [
            'pipeline_stage' => 'qualified',
            'match_score' => $this->overall_score,
            'dna_compatibility_score' => $this->cultural_fit_score ?? 0,
            'sourcing_notes' => "Silver medalist from {$this->job->title} - {$this->silver_medal_reason}",
        ]);

        $this->update([
            'added_to_talent_pipeline' => true,
            'talent_pipeline_id' => $pipeline->id,
        ]);

        return $pipelineCandidate;
    }

    public function generateRecommendation(): string
    {
        $strengths = implode(', ', $this->strengths ?? []);
        $developmentAreas = implode(', ', $this->development_areas ?? []);
        
        return "Strong candidate with demonstrated {$strengths}. " .
               "Reason for not selecting: {$this->silver_medal_reason}. " .
               ($developmentAreas ? "Development opportunities: {$developmentAreas}. " : "") .
               "Recommended for: " . implode(', ', $this->suitable_future_roles ?? ['similar roles']);
    }
}
