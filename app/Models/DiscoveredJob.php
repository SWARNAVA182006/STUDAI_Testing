<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscoveredJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_source_id',
        'external_id',
        'url',
        'title',
        'company_name',
        'description',
        'requirements',
        'extracted_skills',
        'location',
        'is_remote',
        'work_arrangement',
        'salary_min',
        'salary_max',
        'salary_period',
        'salary_currency',
        'employment_type',
        'experience_level',
        'applicant_count',
        'posted_at',
        'expires_at',
        'is_processed',
        'is_duplicate',
        'duplicate_of_id',
        'matched_user_ids',
        'ats_score',
    ];

    protected $casts = [
        'extracted_skills' => 'array',
        'is_remote' => 'boolean',
        'is_processed' => 'boolean',
        'is_duplicate' => 'boolean',
        'matched_user_ids' => 'array',
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
        'ats_score' => 'float',
    ];

    // Relationships
    public function jobSource()
    {
        return $this->belongsTo(JobSource::class);
    }

    public function jobMatches()
    {
        return $this->hasMany(JobMatch::class);
    }

    public function autoApplications()
    {
        return $this->hasMany(AutoApplication::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(DiscoveredJob::class, 'duplicate_of_id');
    }

    public function duplicates()
    {
        return $this->hasMany(DiscoveredJob::class, 'duplicate_of_id');
    }

    // Scopes
    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false)
            ->where('is_duplicate', false);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('posted_at', '>=', now()->subDays($days));
    }

    public function scopeRemote($query)
    {
        return $query->where('is_remote', true);
    }

    public function scopeByCompany($query, string $company)
    {
        return $query->where('company_name', 'like', "%{$company}%");
    }

    // Business Logic
    public function markAsProcessed(): void
    {
        $this->update(['is_processed' => true]);
    }

    public function markAsDuplicate(int $originalJobId): void
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_of_id' => $originalJobId,
        ]);
    }

    public function addMatchedUser(int $userId): void
    {
        $matchedUsers = $this->matched_user_ids ?? [];
        
        if (!in_array($userId, $matchedUsers)) {
            $matchedUsers[] = $userId;
            $this->update(['matched_user_ids' => $matchedUsers]);
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isRecent(int $days = 7): bool
    {
        return $this->posted_at && $this->posted_at->isAfter(now()->subDays($days));
    }

    public function getSalaryRange(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        $currency = $this->salary_currency === 'USD' ? '$' : $this->salary_currency;
        
        if ($this->salary_min && $this->salary_max) {
            return "{$currency}" . number_format($this->salary_min) . " - " . 
                   "{$currency}" . number_format($this->salary_max) . 
                   " {$this->salary_period}";
        }

        if ($this->salary_min) {
            return "{$currency}" . number_format($this->salary_min) . "+ {$this->salary_period}";
        }

        return "Up to {$currency}" . number_format($this->salary_max) . " {$this->salary_period}";
    }

    public function extractSkillsFromDescription(): array
    {
        // This would use AI to extract skills
        // For now, return stored skills or empty array
        return $this->extracted_skills ?? [];
    }

    public function calculateATSScore(): float
    {
        if ($this->ats_score !== null) {
            return $this->ats_score;
        }

        // Calculate based on structure, keywords, clarity
        $score = 0;

        // Has clear salary range
        if ($this->salary_min && $this->salary_max) {
            $score += 20;
        }

        // Has detailed requirements
        if (strlen($this->requirements ?? '') > 200) {
            $score += 20;
        }

        // Has extracted skills
        if (!empty($this->extracted_skills)) {
            $score += 20;
        }

        // Recent posting
        if ($this->isRecent(14)) {
            $score += 20;
        }

        // Clear employment type
        if ($this->employment_type) {
            $score += 10;
        }

        // Clear location/remote info
        if ($this->location || $this->is_remote) {
            $score += 10;
        }

        $this->update(['ats_score' => $score]);

        return $score;
    }

    public function getCompanySize(): ?string
    {
        // This could be enhanced with company data lookup
        return null;
    }

    public function hasSimilarityTo(DiscoveredJob $other): float
    {
        $similarity = 0;

        // Same company + similar title = likely duplicate
        if (strtolower($this->company_name) === strtolower($other->company_name)) {
            $similarity += 50;

            similar_text(
                strtolower($this->title), 
                strtolower($other->title), 
                $titleSimilarity
            );
            $similarity += $titleSimilarity * 0.5;
        }

        return $similarity;
    }

    public function findDuplicates(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('company_name', $this->company_name)
            ->where('id', '!=', $this->id)
            ->get()
            ->filter(function ($job) {
                return $this->hasSimilarityTo($job) > 80;
            });
    }
}
