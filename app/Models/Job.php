<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Job extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'job_listings';

    protected $fillable = [
        'company_id',
        'posted_by',
        'title',
        'slug',
        'description',
        'location',
        'location_type',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'required_skills',
        'preferred_skills',
        'requirements',
        'responsibilities',
        'benefits',
        'application_method',
        'external_url',
        'application_email',
        'application_instructions',
        'status',
        'is_featured',
        'is_urgent',
        'published_at',
        'expires_at',
        'filled_at',
        'views_count',
        'applications_count',
        'saves_count',
        'search_keywords',
        'ai_embeddings',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'preferred_skills' => 'array',
        'benefits' => 'array',
        'ai_embeddings' => 'array',
        'is_featured' => 'boolean',
        'is_urgent' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'filled_at' => 'datetime',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'saved_jobs')
            ->withTimestamps();
    }

    public function jobViews(): HasMany
    {
        return $this->hasMany(JobView::class);
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeActive($query)
    {
        return $query->published()
            ->where('status', '!=', 'filled');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    public function scopeRemote($query)
    {
        return $query->where('location_type', 'remote');
    }

    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeInSalaryRange($query, $min, $max)
    {
        return $query->where(function ($q) use ($min, $max) {
            $q->whereBetween('salary_min', [$min, $max])
              ->orWhereBetween('salary_max', [$min, $max])
              ->orWhere(function ($q2) use ($min, $max) {
                  $q2->where('salary_min', '<=', $min)
                     ->where('salary_max', '>=', $max);
              });
        });
    }

    /**
     * Accessors & Mutators
     */
    public function getSalaryRangeAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        $currency = $this->salary_currency;
        $min = $this->salary_min ? number_format((float) $this->salary_min) : '';
        $max = $this->salary_max ? number_format((float) $this->salary_max) : '';

        if ($min && $max) {
            return "{$currency} {$min} - {$max} per {$this->salary_period}";
        }

        return $min ? "{$currency} {$min}+" : "{$currency} up to {$max}";
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'published' 
            && !$this->isExpired 
            && $this->published_at 
            && $this->published_at->isPast();
    }

    /**
     * Scout searchable array
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'company_name' => $this->company?->name,
            'required_skills' => $this->required_skills,
            'employment_type' => $this->employment_type,
            'experience_level' => $this->experience_level,
        ];
    }

    /**
     * Increment views counter
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Check if job matches salary expectations
     */
    public function matchesSalaryExpectations(?float $minSalary, ?float $maxSalary): bool
    {
        if (!$minSalary && !$maxSalary) {
            return true;
        }

        if ($this->salary_max && $minSalary && $this->salary_max < $minSalary) {
            return false;
        }

        return true;
    }
}

