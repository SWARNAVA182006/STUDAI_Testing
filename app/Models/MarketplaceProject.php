<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MarketplaceProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'company_id',
        'title',
        'slug',
        'description',
        'requirements',
        'deliverables',
        'project_type',
        'category',
        'skills_required',
        'budget_min',
        'budget_max',
        'hourly_rate_min',
        'hourly_rate_max',
        'currency',
        'experience_level',
        'estimated_duration_days',
        'duration_type',
        'status',
        'is_featured',
        'is_urgent',
        'allows_remote',
        'location',
        'proposals_count',
        'views_count',
        'deadline',
        'published_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'skills_required' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'hourly_rate_min' => 'decimal:2',
        'hourly_rate_max' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_urgent' => 'boolean',
        'allows_remote' => 'boolean',
        'deadline' => 'datetime',
        'published_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->title) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(MarketplaceProposal::class, 'project_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(MarketplaceContract::class, 'project_id');
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(MarketplaceContract::class, 'project_id')
            ->whereIn('status', ['pending', 'active']);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(MarketplaceInvitation::class, 'project_id');
    }

    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedProject::class, 'project_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByBudgetRange($query, float $min, float $max)
    {
        return $query->where(function ($q) use ($min, $max) {
            $q->whereBetween('budget_min', [$min, $max])
              ->orWhereBetween('budget_max', [$min, $max]);
        });
    }

    public function scopeWithSkills($query, array $skills)
    {
        return $query->where(function ($q) use ($skills) {
            foreach ($skills as $skill) {
                $q->orWhereJsonContains('skills_required', $skill);
            }
        });
    }

    // Helpers
    public function getBudgetDisplayAttribute(): string
    {
        if ($this->project_type === 'hourly') {
            return sprintf('%s %d - %d/hr', 
                $this->currency, 
                $this->hourly_rate_min, 
                $this->hourly_rate_max
            );
        }

        if ($this->budget_min && $this->budget_max) {
            return sprintf('%s %s - %s', 
                $this->currency,
                number_format($this->budget_min),
                number_format($this->budget_max)
            );
        }

        return $this->budget_max 
            ? sprintf('%s %s', $this->currency, number_format($this->budget_max))
            : 'Budget not specified';
    }

    public function getDurationDisplayAttribute(): string
    {
        if (!$this->estimated_duration_days) {
            return 'Duration not specified';
        }

        return sprintf('%d %s', 
            $this->estimated_duration_days, 
            $this->duration_type
        );
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function canReceiveProposals(): bool
    {
        return $this->isOpen() && 
               (!$this->deadline || $this->deadline->isFuture());
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'open',
            'published_at' => now(),
        ]);
    }
}
