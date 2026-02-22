<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'freelancer_id',
        'cover_letter',
        'proposed_amount',
        'hourly_rate',
        'currency',
        'estimated_duration_days',
        'milestones',
        'relevant_experience',
        'attachments',
        'status',
        'is_boosted',
        'boosted_at',
        'viewed_at',
        'responded_at',
    ];

    protected $casts = [
        'milestones' => 'array',
        'attachments' => 'array',
        'proposed_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_boosted' => 'boolean',
        'boosted_at' => 'datetime',
        'viewed_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProject::class, 'project_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function freelancerProfile(): BelongsTo
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_id', 'user_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(MarketplaceContract::class, 'proposal_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeBoosted($query)
    {
        return $query->where('is_boosted', true);
    }

    // Helpers
    public function getProposedAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->proposed_amount));
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isShortlisted(): bool
    {
        return $this->status === 'shortlisted';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }

    public function shortlist(): void
    {
        $this->update([
            'status' => 'shortlisted',
            'responded_at' => now(),
        ]);
    }

    public function accept(): MarketplaceContract
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Reject other proposals
        $this->project->proposals()
            ->where('id', '!=', $this->id)
            ->where('status', '!=', 'withdrawn')
            ->update(['status' => 'rejected', 'responded_at' => now()]);

        // Update project status
        $this->project->update(['status' => 'in_progress', 'started_at' => now()]);

        // Create contract
        $platformFeePercent = 10; // 10% platform fee
        $platformFeeAmount = $this->proposed_amount * ($platformFeePercent / 100);
        $freelancerAmount = $this->proposed_amount - $platformFeeAmount;

        return MarketplaceContract::create([
            'contract_number' => 'CTR-' . strtoupper(uniqid()),
            'project_id' => $this->project_id,
            'proposal_id' => $this->id,
            'employer_id' => $this->project->employer_id,
            'freelancer_id' => $this->freelancer_id,
            'terms' => $this->cover_letter,
            'total_amount' => $this->proposed_amount,
            'platform_fee_percent' => $platformFeePercent,
            'platform_fee_amount' => $platformFeeAmount,
            'freelancer_amount' => $freelancerAmount,
            'currency' => $this->currency,
            'payment_type' => $this->project->project_type,
            'status' => 'pending',
            'deadline' => $this->project->deadline,
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);
    }

    public function withdraw(): void
    {
        $this->update(['status' => 'withdrawn']);
        $this->project->decrement('proposals_count');
    }

    public function boost(): void
    {
        $this->update([
            'is_boosted' => true,
            'boosted_at' => now(),
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($proposal) {
            $proposal->project->increment('proposals_count');
        });
    }
}
