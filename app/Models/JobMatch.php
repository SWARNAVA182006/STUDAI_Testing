<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'discovered_job_id',
        'overall_match_score',
        'score_breakdown',
        'matching_skills',
        'missing_skills',
        'agent_decision',
        'decision_reasoning',
        'confidence_score',
        'user_override',
        'user_notes',
        'has_applied',
        'applied_at',
        'auto_application_id',
    ];

    protected $casts = [
        'overall_match_score' => 'float',
        'score_breakdown' => 'array',
        'matching_skills' => 'array',
        'missing_skills' => 'array',
        'confidence_score' => 'float',
        'has_applied' => 'boolean',
        'applied_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discoveredJob()
    {
        return $this->belongsTo(DiscoveredJob::class);
    }

    public function autoApplication()
    {
        return $this->belongsTo(AutoApplication::class);
    }

    // Scopes
    public function scopePendingDecision($query)
    {
        return $query->where('agent_decision', 'review')
            ->whereNull('user_override');
    }

    public function scopeApproved($query)
    {
        return $query->where(function ($q) {
            $q->where('agent_decision', 'apply')
              ->orWhere('user_override', 'approve');
        })->where('has_applied', false);
    }

    public function scopeApplied($query)
    {
        return $query->where('has_applied', true);
    }

    public function scopeHighMatch($query, float $threshold = 80.0)
    {
        return $query->where('overall_match_score', '>=', $threshold);
    }

    // Business Logic
    public function isApproved(): bool
    {
        return $this->agent_decision === 'apply' || $this->user_override === 'approve';
    }

    public function isRejected(): bool
    {
        return $this->agent_decision === 'skip' || $this->user_override === 'reject';
    }

    public function requiresReview(): bool
    {
        return $this->agent_decision === 'review' && $this->user_override === null;
    }

    public function approve(string $notes = null): void
    {
        $this->update([
            'user_override' => 'approve',
            'user_notes' => $notes,
        ]);
    }

    public function reject(string $notes = null): void
    {
        $this->update([
            'user_override' => 'reject',
            'user_notes' => $notes,
        ]);
    }

    public function markAsApplied(AutoApplication $application): void
    {
        $this->update([
            'has_applied' => true,
            'applied_at' => now(),
            'auto_application_id' => $application->id,
        ]);
    }

    public function getMatchGrade(): string
    {
        $score = $this->overall_match_score;

        return match (true) {
            $score >= 95 => 'Perfect',
            $score >= 85 => 'Excellent',
            $score >= 75 => 'Great',
            $score >= 65 => 'Good',
            $score >= 50 => 'Fair',
            default => 'Poor',
        };
    }

    public function getMatchColor(): string
    {
        $score = $this->overall_match_score;

        return match (true) {
            $score >= 85 => 'green',
            $score >= 70 => 'blue',
            $score >= 50 => 'yellow',
            default => 'red',
        };
    }

    public function getSkillMatchPercentage(): float
    {
        $matchingCount = count($this->matching_skills ?? []);
        $missingCount = count($this->missing_skills ?? []);
        $totalCount = $matchingCount + $missingCount;

        if ($totalCount === 0) {
            return 0;
        }

        return ($matchingCount / $totalCount) * 100;
    }

    public function getSalaryMatch(): ?float
    {
        $job = $this->discoveredJob;
        $config = $this->user->agentConfiguration;

        if (!$job || !$config) {
            return null;
        }

        if (!$job->salary_min && !$job->salary_max) {
            return null; // Can't calculate
        }

        $jobSalary = $job->salary_max ?? $job->salary_min;
        $userMin = $config->min_salary ?? 0;
        $userMax = $config->max_salary ?? PHP_INT_MAX;

        if ($jobSalary >= $userMin && $jobSalary <= $userMax) {
            return 100; // Perfect match
        }

        if ($jobSalary < $userMin) {
            return max(0, 100 - (($userMin - $jobSalary) / $userMin * 100));
        }

        return 80; // Exceeds max (still good)
    }

    public function getLocationMatch(): float
    {
        $job = $this->discoveredJob;
        $config = $this->user->agentConfiguration;

        if (!$job || !$config) {
            return 50;
        }

        // Remote jobs always match if remote is in preferences
        if ($job->is_remote && in_array('remote', $config->preferred_locations ?? [])) {
            return 100;
        }

        // Check if job location matches preferred locations
        foreach ($config->preferred_locations ?? [] as $preferredLocation) {
            if (stripos($job->location ?? '', $preferredLocation) !== false) {
                return 100;
            }
        }

        return 0;
    }

    public function calculateDetailedScoreBreakdown(): array
    {
        return [
            'skills' => $this->getSkillMatchPercentage(),
            'salary' => $this->getSalaryMatch() ?? 50,
            'location' => $this->getLocationMatch(),
            'experience' => $this->getExperienceMatch(),
            'company_fit' => $this->getCompanyFitScore(),
        ];
    }

    protected function getExperienceMatch(): float
    {
        // Simplified - would need more sophisticated logic
        return 70;
    }

    protected function getCompanyFitScore(): float
    {
        // Could analyze company size, industry, etc.
        return 70;
    }
}
