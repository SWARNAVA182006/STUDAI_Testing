<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMarketPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'readiness_score',
        'readiness_breakdown',
        'overall_percentile',
        'experience_percentile',
        'skills_percentile',
        'compensation_percentile',
        'competitive_advantages',
        'competitive_weaknesses',
        'skill_gaps',
        'best_fit_roles',
        'trending_opportunities',
        'roles_to_avoid',
        'recommendations',
        'recommendation_priority',
        'calculated_at',
        'next_update_at',
    ];

    protected $casts = [
        'readiness_score' => 'decimal:2',
        'readiness_breakdown' => 'array',
        'overall_percentile' => 'decimal:2',
        'experience_percentile' => 'decimal:2',
        'skills_percentile' => 'decimal:2',
        'compensation_percentile' => 'decimal:2',
        'competitive_advantages' => 'array',
        'competitive_weaknesses' => 'array',
        'skill_gaps' => 'array',
        'best_fit_roles' => 'array',
        'trending_opportunities' => 'array',
        'roles_to_avoid' => 'array',
        'recommendations' => 'array',
        'recommendation_priority' => 'integer',
        'calculated_at' => 'datetime',
        'next_update_at' => 'datetime',
    ];

    /**
     * Get the user that owns the market position
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if position needs update
     */
    public function needsUpdate(): bool
    {
        if (!$this->calculated_at) {
            return true;
        }

        // Update daily or if next_update_at has passed
        return $this->calculated_at->lt(now()->subDay()) 
            || ($this->next_update_at && $this->next_update_at->isPast());
    }

    /**
     * Get position status color
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->readiness_score >= 80) return 'green';
        if ($this->readiness_score >= 60) return 'yellow';
        if ($this->readiness_score >= 40) return 'orange';
        return 'red';
    }

    /**
     * Get position status label
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->readiness_score >= 80) return 'Excellent';
        if ($this->readiness_score >= 60) return 'Good';
        if ($this->readiness_score >= 40) return 'Fair';
        return 'Needs Improvement';
    }
}
