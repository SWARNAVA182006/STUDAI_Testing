<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FreelancerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'professional_title',
        'bio',
        'overview',
        'hourly_rate',
        'currency',
        'skills',
        'languages',
        'experience_level',
        'availability',
        'hours_per_week',
        'available_for_remote',
        'available_for_onsite',
        'preferred_project_size',
        'total_earnings',
        'completed_projects',
        'ongoing_projects',
        'success_rate',
        'average_rating',
        'total_reviews',
        'is_verified',
        'is_featured',
        'verified_at',
        'portfolio',
        'certifications',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'portfolio' => 'array',
        'certifications' => 'array',
        'hourly_rate' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'available_for_remote' => 'boolean',
        'available_for_onsite' => 'boolean',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(MarketplaceProposal::class, 'freelancer_id', 'user_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(MarketplaceContract::class, 'freelancer_id', 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'reviewee_id', 'user_id');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserSkillBadge::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('availability', ['full_time', 'part_time', 'hourly']);
    }

    public function scopeTopRated($query)
    {
        return $query->where('average_rating', '>=', 4.5)
                     ->where('total_reviews', '>=', 5);
    }

    public function scopeWithSkills($query, array $skills)
    {
        return $query->where(function ($q) use ($skills) {
            foreach ($skills as $skill) {
                $q->orWhereJsonContains('skills', $skill);
            }
        });
    }

    public function scopeByHourlyRateRange($query, float $min, float $max)
    {
        return $query->whereBetween('hourly_rate', [$min, $max]);
    }

    // Helpers
    public function getHourlyRateDisplayAttribute(): string
    {
        return $this->hourly_rate 
            ? sprintf('%s %s/hr', $this->currency, number_format($this->hourly_rate))
            : 'Rate negotiable';
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return match($this->availability) {
            'full_time' => 'Full-time (40+ hrs/week)',
            'part_time' => 'Part-time (20-30 hrs/week)',
            'hourly' => 'Hourly (< 20 hrs/week)',
            'not_available' => 'Not currently available',
            default => $this->availability,
        };
    }

    public function getExperienceLevelLabelAttribute(): string
    {
        return match($this->experience_level) {
            'entry' => 'Entry Level',
            'intermediate' => 'Intermediate',
            'expert' => 'Expert',
            default => $this->experience_level,
        };
    }

    public function updateStats(): void
    {
        $contracts = $this->contracts()->get();
        
        $this->completed_projects = $contracts->where('status', 'completed')->count();
        $this->ongoing_projects = $contracts->whereIn('status', ['active', 'pending'])->count();
        
        $reviews = $this->reviews()->where('status', 'published')->get();
        $this->total_reviews = $reviews->count();
        $this->average_rating = $reviews->avg('overall_rating') ?? 0;
        
        // Calculate success rate
        $totalContracts = $contracts->count();
        if ($totalContracts > 0) {
            $successfulContracts = $contracts->where('status', 'completed')->count();
            $this->success_rate = ($successfulContracts / $totalContracts) * 100;
        }
        
        $this->save();
    }

    public function canApplyToProject(MarketplaceProject $project): bool
    {
        // Check if already applied
        if ($this->proposals()->where('project_id', $project->id)->exists()) {
            return false;
        }

        // Check if project is open
        return $project->canReceiveProposals();
    }
}
