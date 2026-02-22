<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnonymizedScreening extends Model
{
    use HasFactory;

    protected $table = 'scout_anonymized_screenings';

    protected $fillable = [
        'application_id',
        'job_id',
        'company_id',
        'anonymized_id',
        'anonymized_data',
        'original_data_hash',
        'anonymization_level',
        'removed_attributes',
        'is_active',
        'expires_at',
        'deanonymized_at',
        'deanonymized_by',
    ];

    protected $casts = [
        'anonymized_data' => 'array',
        'removed_attributes' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'deanonymized_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function deanonymizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deanonymized_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                     ->orWhereNotNull('deanonymized_at');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('anonymization_level', $level);
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->where('expires_at', '<=', now()->addDays($days))
                     ->where('is_active', true);
    }

    /**
     * Accessors
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsDeanonymizedAttribute(): bool
    {
        return !is_null($this->deanonymized_at);
    }

    public function getRemovedAttributesCountAttribute(): int
    {
        return count($this->removed_attributes ?? []);
    }

    public function getAnonymizationLevelDisplayAttribute(): string
    {
        return match($this->anonymization_level) {
            'minimal' => 'Minimal (Basic Info Removed)',
            'standard' => 'Standard (Recommended)',
            'strict' => 'Strict (Maximum Privacy)',
            default => ucfirst($this->anonymization_level),
        };
    }

    public function getExpiresInDaysAttribute(): ?int
    {
        if (!$this->expires_at || $this->is_expired) {
            return null;
        }
        
        return now()->diffInDays($this->expires_at, false);
    }

    public function getSkillsAttribute(): array
    {
        return $this->anonymized_data['skills'] ?? [];
    }

    public function getExperienceRangeAttribute(): ?string
    {
        return $this->anonymized_data['total_experience_range'] ?? null;
    }

    public function getEducationLevelAttribute(): ?string
    {
        return $this->anonymized_data['highest_education_level'] ?? null;
    }

    /**
     * Methods
     */
    public function deanonymize(User $user): bool
    {
        if ($this->is_deanonymized) {
            return false;
        }

        $this->update([
            'is_active' => false,
            'deanonymized_at' => now(),
            'deanonymized_by' => $user->id,
        ]);

        return true;
    }

    public function extend(int $days): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $newExpiry = $this->expires_at 
            ? max($this->expires_at, now())->addDays($days)
            : now()->addDays($days);

        $this->update(['expires_at' => $newExpiry]);

        return true;
    }

    public function verifyDataIntegrity(): bool
    {
        // Verify the original data hash hasn't changed
        $application = $this->application()->first();
        if (!$application) {
            return false;
        }

        $currentHash = hash('sha256', json_encode($application->toArray()));
        return $this->original_data_hash === $currentHash;
    }

    public function hasSkill(string $skill): bool
    {
        $skills = $this->skills;
        foreach ($skills as $s) {
            if (is_string($s) && strtolower($s) === strtolower($skill)) {
                return true;
            }
            if (is_array($s) && isset($s['name']) && strtolower($s['name']) === strtolower($skill)) {
                return true;
            }
        }
        return false;
    }
}
