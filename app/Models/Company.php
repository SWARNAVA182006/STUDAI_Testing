<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'website',
        'industry',
        'company_size',
        'founded_year',
        'headquarters',
        'description',
        'is_verified',
        'is_featured',
        'rating',
        'follower_count',
        // Review stats (cached)
        'review_count',
        'salary_count',
        'interview_count',
        'avg_overall_rating',
        'avg_culture_rating',
        'avg_compensation_rating',
        'avg_worklife_rating',
        'avg_growth_rating',
        'avg_management_rating',
        'ceo_approval_rate',
        'recommend_rate',
        'benefits',
    ];
    
    protected $casts = [
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'rating' => 'float',
        'follower_count' => 'integer',
        'founded_year' => 'integer',
        'review_count' => 'integer',
        'salary_count' => 'integer',
        'interview_count' => 'integer',
        'avg_overall_rating' => 'float',
        'avg_culture_rating' => 'float',
        'avg_compensation_rating' => 'float',
        'avg_worklife_rating' => 'float',
        'avg_growth_rating' => 'float',
        'avg_management_rating' => 'float',
        'ceo_approval_rate' => 'integer',
        'recommend_rate' => 'integer',
        'benefits' => 'array',
    ];
    
    /**
     * Get the jobs posted by this company
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
    
    /**
     * Get the company's reviews
     */
    public function reviews()
    {
        return $this->hasMany(CompanyReview::class);
    }

    /**
     * Get approved reviews
     */
    public function approvedReviews()
    {
        return $this->reviews()->approved();
    }

    /**
     * Get salary reports
     */
    public function salaryReports()
    {
        return $this->hasMany(SalaryReport::class);
    }

    /**
     * Get interview experiences
     */
    public function interviewExperiences()
    {
        return $this->hasMany(InterviewExperience::class);
    }
    
    /**
     * Get users following this company
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withTimestamps();
    }
    
    /**
     * Get the user who owns this company (employer)
     */
    public function owner()
    {
        return $this->hasOne(User::class);
    }
    
    /**
     * Get team members
     */
    public function teamMembers()
    {
        return $this->hasMany(CompanyTeamMember::class);
    }
    
    /**
     * Get active team members with user details
     */
    public function activeTeamMembers()
    {
        return $this->teamMembers()->where('is_active', true)->with('user');
    }
    
    /**
     * Check if a user is following this company
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('user_id', $user->id)->exists();
    }
    
    /**
     * Get average rating
     */
    public function getAverageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0.0;
    }
    
    /**
     * Update rating based on reviews
     */
    public function updateRating(): void
    {
        /** @phpstan-ignore-next-line */
        $this->rating = $this->getAverageRating();
        $this->save();
    }

    /**
     * Recalculate all ratings from reviews
     */
    public function recalculateRatings(): void
    {
        app(\App\Services\CompanyReviewService::class)->recalculateCompanyRatings($this);
    }

    /**
     * Increment salary report count
     */
    public function incrementSalaryCount(): void
    {
        $this->increment('total_salaries');
    }

    /**
     * Increment interview experience count
     */
    public function incrementInterviewCount(): void
    {
        $this->increment('total_interviews');
    }
}