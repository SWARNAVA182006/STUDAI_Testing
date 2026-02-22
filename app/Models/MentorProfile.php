<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorProfile extends Model
{
    use HasFactory;

    protected $table = 'mentor_profiles';

    protected $fillable = [
        'user_id',
        'bio',
        'expertise_areas',
        'industries',
        'years_experience',
        'max_mentees',
        'current_mentees',
        'is_accepting',
        'availability',
        'rating',
        'reviews_count',
        'is_verified',
        'is_featured',
    ];

    protected $casts = [
        'expertise_areas' => 'array',
        'industries' => 'array',
        'is_accepting' => 'boolean',
        'rating' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentorships(): HasMany
    {
        return $this->hasMany(Mentorship::class, 'mentor_id', 'user_id');
    }

    public function activeMentorships(): HasMany
    {
        return $this->mentorships()->where('status', 'active');
    }

    public function completedMentorships(): HasMany
    {
        return $this->mentorships()->where('status', 'completed');
    }

    public function hasCapacity(): bool
    {
        return $this->current_mentees < $this->max_mentees;
    }

    public function isAvailable(): bool
    {
        return $this->is_accepting && $this->hasCapacity();
    }

    public function incrementMenteeCount(): void
    {
        $this->increment('current_mentees');
        
        if (!$this->hasCapacity()) {
            $this->update(['is_accepting' => false]);
        }
    }

    public function decrementMenteeCount(): void
    {
        if ($this->current_mentees > 0) {
            $this->decrement('current_mentees');
        }
    }

    public function updateRating(float $newRating): void
    {
        $totalRating = ($this->rating ?? 0) * $this->reviews_count;
        $newTotal = $totalRating + $newRating;
        $newCount = $this->reviews_count + 1;
        
        $this->update([
            'rating' => $newTotal / $newCount,
            'reviews_count' => $newCount,
        ]);
    }

    public function getAvailabilityLabel(): string
    {
        return match ($this->availability) {
            'low' => 'Limited Availability',
            'medium' => 'Moderately Available',
            'high' => 'Highly Available',
            default => 'Unknown',
        };
    }

    public function scopeAccepting($query)
    {
        return $query->where('is_accepting', true)
            ->whereColumn('current_mentees', '<', 'max_mentees');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithExpertise($query, string $expertise)
    {
        return $query->whereJsonContains('expertise_areas', $expertise);
    }

    public function scopeInIndustry($query, string $industry)
    {
        return $query->whereJsonContains('industries', $industry);
    }

    public function scopeHighlyRated($query, float $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }
}
