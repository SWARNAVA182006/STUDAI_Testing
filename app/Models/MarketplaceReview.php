<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'reviewer_id',
        'reviewee_id',
        'reviewer_type',
        'overall_rating',
        'communication_rating',
        'quality_rating',
        'timeliness_rating',
        'professionalism_rating',
        'value_rating',
        'cooperation_rating',
        'review_text',
        'private_feedback',
        'would_recommend',
        'would_hire_again',
        'skills_endorsed',
        'status',
        'employer_response',
        'responded_at',
    ];

    protected $casts = [
        'skills_endorsed' => 'array',
        'would_recommend' => 'boolean',
        'would_hire_again' => 'boolean',
        'responded_at' => 'datetime',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketplaceContract::class, 'contract_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForFreelancer($query)
    {
        return $query->where('reviewer_type', 'employer');
    }

    public function scopeForEmployer($query)
    {
        return $query->where('reviewer_type', 'freelancer');
    }

    public function scopeByRating($query, int $minRating)
    {
        return $query->where('overall_rating', '>=', $minRating);
    }

    // Helpers
    public function getAverageRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->communication_rating,
            $this->quality_rating,
            $this->timeliness_rating,
            $this->professionalism_rating,
            $this->value_rating,
            $this->cooperation_rating,
        ]);

        return count($ratings) > 0 
            ? array_sum($ratings) / count($ratings) 
            : $this->overall_rating;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function publish(): void
    {
        $this->update(['status' => 'published']);

        // Update reviewee's stats
        $profile = FreelancerProfile::where('user_id', $this->reviewee_id)->first();
        if ($profile) {
            $profile->updateStats();
        }
    }

    public function hide(): void
    {
        $this->update(['status' => 'hidden']);
    }

    public function respond(string $response): void
    {
        $this->update([
            'employer_response' => $response,
            'responded_at' => now(),
        ]);
    }
}
