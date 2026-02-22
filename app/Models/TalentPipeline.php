<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TalentPipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'pipeline_name',
        'target_role',
        'role_description',
        'pipeline_status',
        'pipeline_type',
        'required_skills',
        'preferred_experience',
        'cultural_fit_criteria',
        'target_pipeline_size',
        'current_pipeline_size',
        'pipeline_health_score',
        'hiring_frequency_days',
        'last_hired_at',
        'next_projected_hire_date',
        'pipeline_metrics',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'preferred_experience' => 'array',
        'cultural_fit_criteria' => 'array',
        'pipeline_metrics' => 'array',
        'pipeline_health_score' => 'decimal:2',
        'last_hired_at' => 'date',
        'next_projected_hire_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(PipelineCandidate::class);
    }

    public function silverMedalists(): HasMany
    {
        return $this->hasMany(SilverMedalist::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('pipeline_status', 'active');
    }

    public function scopeHealthy($query, $minScore = 70)
    {
        return $query->where('pipeline_health_score', '>=', $minScore);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where('pipeline_health_score', '<', 50)
            ->orWhere('current_pipeline_size', '<', 5);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('target_role', 'like', "%{$role}%");
    }

    /**
     * Accessors
     */
    public function getHealthStatusAttribute(): string
    {
        $score = $this->pipeline_health_score;
        
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'needs_improvement';
    }

    public function getFillRateAttribute(): float
    {
        if ($this->target_pipeline_size === 0) return 0;
        return ($this->current_pipeline_size / $this->target_pipeline_size) * 100;
    }

    public function getIsUnderstaffedAttribute(): bool
    {
        return $this->current_pipeline_size < ($this->target_pipeline_size * 0.5);
    }

    /**
     * Helper methods
     */
    public function updateHealthScore(): void
    {
        $fillRateScore = min(100, ($this->current_pipeline_size / max(1, $this->target_pipeline_size)) * 100);
        
        $qualifiedCount = $this->candidates()
            ->whereIn('pipeline_stage', ['warm', 'hot'])
            ->count();
        $qualificationScore = $this->current_pipeline_size > 0
            ? ($qualifiedCount / $this->current_pipeline_size) * 100
            : 0;
        
        $recentEngagementCount = $this->candidates()
            ->where('last_engaged_at', '>=', now()->subDays(30))
            ->count();
        $engagementScore = $this->current_pipeline_size > 0
            ? ($recentEngagementCount / $this->current_pipeline_size) * 100
            : 0;
        
        $avgMatchScore = $this->candidates()
            ->avg('match_score') ?? 0;
        
        $healthScore = (
            $fillRateScore * 0.30 +
            $qualificationScore * 0.30 +
            $engagementScore * 0.20 +
            $avgMatchScore * 0.20
        );
        
        $this->update(['pipeline_health_score' => round($healthScore, 2)]);
    }

    public function addCandidate(User $user, array $data = []): PipelineCandidate
    {
        $pipelineCandidate = $this->candidates()->create([
            'user_id' => $user->id,
            'added_to_pipeline_at' => now(),
            'pipeline_stage' => $data['pipeline_stage'] ?? 'sourced',
            'match_score' => $data['match_score'] ?? 0,
            'dna_compatibility_score' => $data['dna_compatibility_score'] ?? 0,
            'sourcing_notes' => $data['sourcing_notes'] ?? null,
        ]);

        $this->increment('current_pipeline_size');
        $this->updateHealthScore();

        return $pipelineCandidate;
    }

    public function removeCandidate(PipelineCandidate $candidate): void
    {
        $candidate->delete();
        $this->decrement('current_pipeline_size');
        $this->updateHealthScore();
    }
}
