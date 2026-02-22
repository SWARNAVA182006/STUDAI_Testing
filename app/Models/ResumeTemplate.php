<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResumeTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'preview_image',
        'category',
        'industry',
        'color_scheme',
        'layout_config',
        'is_ats_friendly',
        'is_premium',
        'popularity_score',
        'is_active',
    ];

    protected $casts = [
        'color_scheme' => 'array',
        'layout_config' => 'array',
        'is_ats_friendly' => 'boolean',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'popularity_score' => 'integer',
    ];

    /**
     * Relationships
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class, 'template_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeAtsOptimized($query)
    {
        return $query->where('is_ats_friendly', true);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('popularity_score', 'desc')->limit($limit);
    }

    /**
     * Increment popularity score
     */
    public function incrementPopularity(): void
    {
        $this->increment('popularity_score');
    }

    /**
     * Get preview URL
     */
    public function getPreviewUrl(): ?string
    {
        return $this->preview_image ? asset('storage/' . $this->preview_image) : null;
    }

    /**
     * Check if user can access this template
     */
    public function canBeAccessedBy(User $user): bool
    {
        if (!$this->is_premium) {
            return true;
        }

        // Check if user has premium subscription
        return $user->hasActiveSubscription() && 
               $user->subscription->plan->hasFeature('premium_templates');
    }
}
