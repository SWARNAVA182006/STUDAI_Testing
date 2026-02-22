<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlert extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'keywords',
        'location',
        'location_type',
        'employment_type',
        'experience_level',
        'salary_min',
        'required_skills',
        'frequency',
        'is_active',
        'last_sent_at',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'salary_min' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForNotification($query)
    {
        return $query->active()->where(function ($q) {
            $q->whereNull('last_sent_at')
              ->orWhere(function ($q2) {
                  $q2->where('frequency', 'daily')
                     ->where('last_sent_at', '<', now()->subDay());
              })
              ->orWhere(function ($q3) {
                  $q3->where('frequency', 'weekly')
                     ->where('last_sent_at', '<', now()->subWeek());
              });
        });
    }

    /**
     * Check if job matches this alert criteria
     */
    public function matchesJob(Job $job): bool
    {
        // Keywords match (title or description)
        if ($this->keywords && !str_contains(strtolower($job->title . ' ' . $job->description), strtolower($this->keywords))) {
            return false;
        }

        // Location match
        if ($this->location && !str_contains(strtolower($job->location ?? ''), strtolower($this->location))) {
            return false;
        }

        // Location type match
        if ($this->location_type && $job->location_type !== $this->location_type) {
            return false;
        }

        // Employment type match
        if ($this->employment_type && $job->employment_type !== $this->employment_type) {
            return false;
        }

        // Experience level match
        if ($this->experience_level && $job->experience_level !== $this->experience_level) {
            return false;
        }

        // Salary minimum
        if ($this->salary_min && $job->salary_max && $job->salary_max < $this->salary_min) {
            return false;
        }

        // Skills match (at least one required skill should be in job's required skills)
        if ($this->required_skills && is_array($this->required_skills) && count($this->required_skills) > 0) {
            $jobSkills = array_map('strtolower', $job->required_skills ?? []);
            $alertSkills = array_map('strtolower', $this->required_skills);
            $hasMatchingSkill = !empty(array_intersect($alertSkills, $jobSkills));
            
            if (!$hasMatchingSkill) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark alert as sent
     */
    public function markAsSent(): void
    {
        $this->update(['last_sent_at' => now()]);
    }
}

