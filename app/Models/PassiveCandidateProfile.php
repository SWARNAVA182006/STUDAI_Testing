<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PassiveCandidateProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'source_platform',
        'external_profile_url',
        'candidate_name',
        'candidate_email',
        'candidate_phone',
        'current_company',
        'current_title',
        'location',
        'skills',
        'experience_summary',
        'years_of_experience',
        'dna_match_score',
        'dna_alignment_factors',
        'potential_roles',
        'discovery_method',
        'engagement_readiness',
        'engagement_signals',
        'discovered_at',
        'last_monitored_at',
        'optimal_engagement_date',
        'engagement_initiated',
        'engaged_at',
        'engagement_outcome',
    ];

    protected $casts = [
        'candidate_name' => 'encrypted',
        'candidate_email' => 'encrypted',
        'candidate_phone' => 'encrypted',
        'external_profile_url' => 'encrypted',
        'skills' => 'array',
        'experience_summary' => 'array',
        'dna_alignment_factors' => 'array',
        'potential_roles' => 'array',
        'engagement_signals' => 'array',
        'dna_match_score' => 'decimal:2',
        'discovered_at' => 'date',
        'last_monitored_at' => 'date',
        'optimal_engagement_date' => 'date',
        'engagement_initiated' => 'boolean',
        'engaged_at' => 'date',
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

    /**
     * Scopes
     */
    public function scopeReadyForEngagement($query)
    {
        return $query->where('engagement_readiness', 'ready')
            ->where('engagement_initiated', false);
    }

    public function scopeUrgent($query)
    {
        return $query->where('engagement_readiness', 'urgent')
            ->where('engagement_initiated', false);
    }

    public function scopeHighDnaMatch($query, $minScore = 75)
    {
        return $query->where('dna_match_score', '>=', $minScore);
    }

    public function scopeNotEngaged($query)
    {
        return $query->where('engagement_initiated', false);
    }

    public function scopeMonitoring($query)
    {
        return $query->where('engagement_readiness', 'monitor')
            ->where('engagement_initiated', false);
    }

    public function scopeNeedsMonitoring($query, $days = 30)
    {
        return $query->where(function($q) use ($days) {
            $q->whereNull('last_monitored_at')
                ->orWhere('last_monitored_at', '<', now()->subDays($days));
        });
    }

    public function scopeByDiscoveryMethod($query, string $method)
    {
        return $query->where('discovery_method', $method);
    }

    /**
     * Accessors
     */
    public function getDaysSinceDiscoveryAttribute(): int
    {
        return now()->diffInDays($this->discovered_at);
    }

    public function getDaysSinceLastMonitoredAttribute(): ?int
    {
        if (!$this->last_monitored_at) return null;
        return now()->diffInDays($this->last_monitored_at);
    }

    public function getIsOptimalEngagementTimeAttribute(): bool
    {
        return $this->optimal_engagement_date && 
               $this->optimal_engagement_date <= now() &&
               !$this->engagement_initiated;
    }

    public function getEngagementPriorityAttribute(): string
    {
        if ($this->engagement_readiness === 'urgent' && $this->dna_match_score >= 80) {
            return 'critical';
        }
        if ($this->engagement_readiness === 'ready' && $this->dna_match_score >= 75) {
            return 'high';
        }
        if ($this->engagement_readiness === 'ready' || $this->dna_match_score >= 70) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Helper methods
     */
    public function updateMonitoring(array $signals = []): void
    {
        $this->update([
            'last_monitored_at' => now(),
            'engagement_signals' => array_merge($this->engagement_signals ?? [], $signals),
        ]);

        // Determine engagement readiness based on signals
        $this->assessEngagementReadiness();
    }

    public function assessEngagementReadiness(): void
    {
        $signals = $this->engagement_signals ?? [];
        $signalCount = count($signals);

        // Urgent signals: job search activity, company changes
        $urgentSignals = array_filter($signals, function($signal) {
            return in_array($signal['type'] ?? '', ['job_search', 'company_change', 'profile_updated']);
        });

        if (count($urgentSignals) >= 2) {
            $this->update([
                'engagement_readiness' => 'urgent',
                'optimal_engagement_date' => now()->addDays(3),
            ]);
        } elseif ($signalCount >= 3) {
            $this->update([
                'engagement_readiness' => 'ready',
                'optimal_engagement_date' => now()->addWeeks(2),
            ]);
        } elseif ($signalCount >= 1) {
            $this->update([
                'engagement_readiness' => 'monitor',
                'optimal_engagement_date' => now()->addMonths(1),
            ]);
        }
    }

    public function initiateEngagement(): void
    {
        $this->update([
            'engagement_initiated' => true,
            'engaged_at' => now(),
        ]);
    }

    public function recordEngagementOutcome(string $outcome): void
    {
        $this->update(['engagement_outcome' => $outcome]);

        // If converted, create user record if needed
        if ($outcome === 'converted_to_applicant' && !$this->user_id) {
            $user = User::create([
                'name' => $this->candidate_name,
                'email' => $this->candidate_email,
                'account_type' => 'job_seeker',
                // Additional fields...
            ]);
            
            $this->update(['user_id' => $user->id]);
        }
    }
}
