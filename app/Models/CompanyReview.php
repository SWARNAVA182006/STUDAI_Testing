<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'job_title',
        'department',
        'employment_status',
        'is_current_employee',
        'employment_duration',
        'start_date',
        'end_date',
        'overall_rating',
        'culture_rating',
        'compensation_rating',
        'worklife_rating',
        'growth_rating',
        'management_rating',
        'diversity_rating',
        'ceo_approval',
        'recommend_to_friend',
        'business_outlook',
        'review_title',
        'pros',
        'cons',
        'advice_to_management',
        'is_verified',
        'verification_method',
        'is_anonymous',
        'display_name',
        'helpful_count',
        'not_helpful_count',
        'report_count',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'is_featured',
    ];

    protected $casts = [
        'is_current_employee' => 'boolean',
        'is_verified' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_featured' => 'boolean',
        'ceo_approval' => 'boolean',
        'recommend_to_friend' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'overall_rating' => 'integer',
        'culture_rating' => 'integer',
        'compensation_rating' => 'integer',
        'worklife_rating' => 'integer',
        'growth_rating' => 'integer',
        'management_rating' => 'integer',
        'diversity_rating' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'report_count' => 'integer',
    ];

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('overall_rating', $rating);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CompanyReviewVote::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CompanyReviewReport::class);
    }

    // Accessors
    public function getDisplayAuthorAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous Employee';
        }

        return $this->display_name ?? $this->user?->name ?? 'Employee';
    }

    public function getAverageRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->culture_rating,
            $this->compensation_rating,
            $this->worklife_rating,
            $this->growth_rating,
            $this->management_rating,
        ]);

        if (empty($ratings)) {
            return (float) $this->overall_rating;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    public function getEmploymentPeriodAttribute(): string
    {
        if ($this->is_current_employee) {
            $start = $this->start_date?->format('M Y') ?? 'Unknown';
            return "{$start} - Present";
        }

        $start = $this->start_date?->format('M Y') ?? '';
        $end = $this->end_date?->format('M Y') ?? '';

        if ($start && $end) {
            return "{$start} - {$end}";
        }

        return $this->employment_duration ?? 'Duration unknown';
    }

    public function getFormattedRatingAttribute(): string
    {
        return $this->overall_rating . ' ' . ($this->overall_rating === 1 ? 'star' : 'stars');
    }

    public function getExcerptAttribute(): string
    {
        $text = $this->pros ?? '';
        return strlen($text) > 150 ? substr($text, 0, 150) . '...' : $text;
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return match ($this->employment_status) {
            'full_time' => 'Full-time',
            'part_time' => 'Part-time',
            'contract' => 'Contract',
            'internship' => 'Internship',
            'freelance' => 'Freelance',
            default => ucfirst($this->employment_status ?? 'Unknown'),
        };
    }

    // Methods
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        $this->company->recalculateRatings();
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function markHelpful(User $user): void
    {
        $vote = $this->votes()->where('user_id', $user->id)->first();

        if ($vote) {
            if (!$vote->is_helpful) {
                $vote->update(['is_helpful' => true]);
                $this->increment('helpful_count');
                $this->decrement('not_helpful_count');
            }
        } else {
            $this->votes()->create([
                'user_id' => $user->id,
                'is_helpful' => true,
            ]);
            $this->increment('helpful_count');
        }
    }

    public function markNotHelpful(User $user): void
    {
        $vote = $this->votes()->where('user_id', $user->id)->first();

        if ($vote) {
            if ($vote->is_helpful) {
                $vote->update(['is_helpful' => false]);
                $this->decrement('helpful_count');
                $this->increment('not_helpful_count');
            }
        } else {
            $this->votes()->create([
                'user_id' => $user->id,
                'is_helpful' => false,
            ]);
            $this->increment('not_helpful_count');
        }
    }

    public function report(User $user, string $reason, ?string $details = null): void
    {
        $this->reports()->create([
            'user_id' => $user->id,
            'reason' => $reason,
            'details' => $details,
        ]);

        $this->increment('report_count');

        if ($this->report_count >= 5) {
            $this->update(['status' => 'flagged']);
        }
    }
}
