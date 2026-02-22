<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningResource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'learning_path_id',
        'title',
        'description',
        'url',
        'resource_type',
        'provider',
        'provider_name',
        'cost',
        'currency',
        'is_free',
        'duration_hours',
        'difficulty_level',
        'rating',
        'reviews_count',
        'skills_covered',
        'language',
        'has_certificate',
        'is_hands_on',
        'prerequisites',
        'step_order',
        'ai_relevance_score',
        'tags',
        'last_updated',
    ];

    protected $casts = [
        'skills_covered' => 'array',
        'prerequisites' => 'array',
        'tags' => 'array',
        'cost' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_free' => 'boolean',
        'has_certificate' => 'boolean',
        'is_hands_on' => 'boolean',
        'last_updated' => 'date',
    ];

    /**
     * Relationships
     */
    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    /**
     * Scopes
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('resource_type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
    }

    public function scopeHighRated($query, float $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeHandsOn($query)
    {
        return $query->where('is_hands_on', true);
    }

    public function scopeWithCertificate($query)
    {
        return $query->where('has_certificate', true);
    }

    public function scopeRecent($query, int $months = 6)
    {
        return $query->where('last_updated', '>=', now()->subMonths($months));
    }

    /**
     * Accessors
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->resource_type) {
            'course' => '📚 Course',
            'video' => '🎥 Video',
            'article' => '📄 Article',
            'book' => '📖 Book',
            'tutorial' => '🛠️ Tutorial',
            'project' => '🎯 Project',
            'documentation' => '📋 Docs',
            'podcast' => '🎧 Podcast',
            'interactive' => '🎮 Interactive',
            default => '📌 Resource'
        };
    }

    public function getProviderBadgeAttribute(): string
    {
        return match($this->provider) {
            'coursera' => 'Coursera',
            'udemy' => 'Udemy',
            'pluralsight' => 'Pluralsight',
            'youtube' => 'YouTube',
            'medium' => 'Medium',
            'github' => 'GitHub',
            'official_docs' => 'Official Docs',
            'free_code_camp' => 'freeCodeCamp',
            'khan_academy' => 'Khan Academy',
            default => $this->provider_name ?? 'Other'
        };
    }

    public function getDifficultyBadgeAttribute(): string
    {
        return match($this->difficulty_level) {
            'advanced' => '🔴 Advanced',
            'intermediate' => '🟡 Intermediate',
            'beginner' => '🟢 Beginner',
            'all_levels' => '⚪ All Levels',
            default => '❓ Unknown'
        };
    }

    public function getCostFormattedAttribute(): string
    {
        if ($this->is_free) return 'Free';
        return $this->currency . ' ' . number_format($this->cost, 2);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_hours) return 'N/A';
        
        if ($this->duration_hours < 1) {
            $minutes = $this->duration_hours * 60;
            return round($minutes) . ' minutes';
        }
        
        if ($this->duration_hours < 10) {
            return number_format($this->duration_hours, 1) . ' hours';
        }
        
        return round($this->duration_hours) . ' hours';
    }

    public function getRatingStarsAttribute(): string
    {
        if (!$this->rating) return '☆☆☆☆☆';
        
        $fullStars = floor($this->rating);
        $halfStar = ($this->rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
        
        return str_repeat('★', $fullStars) . 
               ($halfStar ? '⯨' : '') . 
               str_repeat('☆', $emptyStars) . 
               ' (' . number_format($this->rating, 1) . ')';
    }

    public function getReviewsTextAttribute(): string
    {
        if (!$this->reviews_count) return 'No reviews';
        if ($this->reviews_count === 1) return '1 review';
        return number_format($this->reviews_count) . ' reviews';
    }

    public function getIsFreshAttribute(): bool
    {
        if (!$this->last_updated) return false;
        return $this->last_updated->gte(now()->subMonths(6));
    }

    public function getRelevancePercentageAttribute(): int
    {
        return $this->ai_relevance_score ?? 0;
    }

    /**
     * Helper Methods
     */
    public function markAsCompleted(int $userId): void
    {
        LearningProgress::create([
            'user_id' => $userId,
            'learning_resource_id' => $this->id,
            'learning_path_id' => $this->learning_path_id,
            'progress_date' => now(),
            'completion_percentage' => 100,
            'activity_type' => $this->getActivityType(),
            'time_spent_minutes' => ($this->duration_hours ?? 0) * 60,
        ]);
        
        if ($this->learningPath) {
            $this->learningPath->updateProgress($this->learningPath->completed_resources + 1);
        }
    }

    public function updateRelevanceScore(int $score): void
    {
        $this->update(['ai_relevance_score' => min(100, max(0, $score))]);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTags(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        $this->update(['tags' => array_values($tags)]);
    }

    protected function getActivityType(): string
    {
        return match($this->resource_type) {
            'video' => 'watching',
            'article', 'book', 'documentation' => 'reading',
            'project' => 'project',
            'interactive' => 'practice',
            default => 'reading'
        };
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'url' => 'nullable|url',
            'resource_type' => 'required|in:course,video,article,book,tutorial,project,documentation,podcast,interactive',
            'provider' => 'nullable|in:coursera,udemy,pluralsight,youtube,medium,github,official_docs,free_code_camp,khan_academy,other',
            'cost' => 'nullable|numeric|min:0',
            'is_free' => 'required|boolean',
            'duration_hours' => 'nullable|numeric|min:0',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced,all_levels',
            'rating' => 'nullable|numeric|min:0|max:5',
            'step_order' => 'required|integer|min:0',
        ];
    }
}
