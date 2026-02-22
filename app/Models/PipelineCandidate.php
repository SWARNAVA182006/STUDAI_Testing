<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PipelineCandidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'talent_pipeline_id',
        'user_id',
        'pipeline_stage',
        'match_score',
        'dna_compatibility_score',
        'added_to_pipeline_at',
        'last_engaged_at',
        'engagement_count',
        'engagement_preference',
        'interaction_history',
        'skill_assessment',
        'sourcing_notes',
        'engagement_notes',
        'availability_status',
        'expected_salary_min',
        'expected_salary_max',
        'next_follow_up_date',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'dna_compatibility_score' => 'decimal:2',
        'added_to_pipeline_at' => 'date',
        'last_engaged_at' => 'date',
        'next_follow_up_date' => 'date',
        'interaction_history' => 'array',
        'skill_assessment' => 'array',
        'expected_salary_min' => 'decimal:2',
        'expected_salary_max' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function talentPipeline(): BelongsTo
    {
        return $this->belongsTo(TalentPipeline::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeWarm($query)
    {
        return $query->whereIn('pipeline_stage', ['warm', 'hot']);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->whereNotNull('next_follow_up_date')
            ->where('next_follow_up_date', '<=', now());
    }

    public function scopeHighPriority($query)
    {
        return $query->where('pipeline_stage', 'hot')
            ->where('match_score', '>=', 75);
    }

    public function scopeStale($query, $days = 90)
    {
        return $query->where('last_engaged_at', '<', now()->subDays($days))
            ->orWhereNull('last_engaged_at');
    }

    public function scopeByAvailability($query, string $status)
    {
        return $query->where('availability_status', $status);
    }

    /**
     * Accessors
     */
    public function getPriorityLevelAttribute(): string
    {
        if ($this->pipeline_stage === 'hot' && $this->match_score >= 80) {
            return 'critical';
        }
        if ($this->pipeline_stage === 'hot' || $this->match_score >= 75) {
            return 'high';
        }
        if ($this->pipeline_stage === 'warm' || $this->match_score >= 60) {
            return 'medium';
        }
        return 'low';
    }

    public function getDaysSinceLastEngagementAttribute(): ?int
    {
        if (!$this->last_engaged_at) return null;
        return now()->diffInDays($this->last_engaged_at);
    }

    public function getIsStaleAttribute(): bool
    {
        return $this->days_since_last_engagement > 90;
    }

    public function getImmediatelyAvailableAttribute(): bool
    {
        return $this->availability_status === 'immediately_available';
    }

    /**
     * Helper methods
     */
    public function recordEngagement(array $data = []): void
    {
        $this->increment('engagement_count');
        
        $this->update([
            'last_engaged_at' => now(),
            'engagement_notes' => $data['notes'] ?? $this->engagement_notes,
            'next_follow_up_date' => $data['next_follow_up_date'] ?? null,
        ]);

        // Update interaction history
        $history = $this->interaction_history ?? [];
        $history[] = [
            'date' => now()->toDateString(),
            'type' => $data['type'] ?? 'general',
            'summary' => $data['summary'] ?? null,
        ];
        $this->update(['interaction_history' => array_slice($history, -10)]); // Keep last 10
    }

    public function advanceStage(string $newStage): void
    {
        $stageHierarchy = [
            'sourced' => 1,
            'engaged' => 2,
            'qualified' => 3,
            'pre_screened' => 4,
            'warm' => 5,
            'hot' => 6,
        ];

        $currentLevel = $stageHierarchy[$this->pipeline_stage] ?? 0;
        $newLevel = $stageHierarchy[$newStage] ?? 0;

        if ($newLevel > $currentLevel) {
            $this->update(['pipeline_stage' => $newStage]);
        }
    }

    public function coolDown(): void
    {
        if ($this->pipeline_stage === 'hot') {
            $this->update(['pipeline_stage' => 'warm']);
        } elseif ($this->pipeline_stage === 'warm') {
            $this->update(['pipeline_stage' => 'cool']);
        }
    }
}
