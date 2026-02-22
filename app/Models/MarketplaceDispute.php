<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispute_number',
        'contract_id',
        'milestone_id',
        'raised_by_id',
        'against_id',
        'dispute_type',
        'description',
        'evidence',
        'disputed_amount',
        'status',
        'resolution',
        'resolution_amount',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'disputed_amount' => 'decimal:2',
        'resolution_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($dispute) {
            if (empty($dispute->dispute_number)) {
                $dispute->dispute_number = 'DSP-' . strtoupper(uniqid());
            }
        });
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketplaceContract::class, 'contract_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(MarketplaceMilestone::class, 'milestone_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_id');
    }

    public function against(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'under_review', 'mediation']);
    }

    // Helpers
    public function getDisputeTypeLabelAttribute(): string
    {
        return match($this->dispute_type) {
            'payment' => 'Payment Issue',
            'quality' => 'Quality of Work',
            'deadline' => 'Missed Deadline',
            'scope' => 'Scope Disagreement',
            'communication' => 'Communication Issues',
            'other' => 'Other',
            default => ucfirst($this->dispute_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'under_review' => 'Under Review',
            'mediation' => 'In Mediation',
            'resolved' => 'Resolved',
            'escalated' => 'Escalated',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }

    public function getResolutionLabelAttribute(): ?string
    {
        return $this->resolution ? match($this->resolution) {
            'refund_full' => 'Full Refund to Employer',
            'refund_partial' => 'Partial Refund',
            'release_full' => 'Full Release to Freelancer',
            'release_partial' => 'Partial Release',
            'split' => 'Split Between Parties',
            'dismissed' => 'Dispute Dismissed',
            default => ucfirst($this->resolution),
        } : null;
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function startReview(): void
    {
        $this->update(['status' => 'under_review']);

        // Pause the contract
        $this->contract->update(['status' => 'disputed']);

        // Mark related escrow as disputed
        if ($this->milestone_id) {
            $this->milestone->escrow?->dispute();
        }
    }

    public function startMediation(): void
    {
        $this->update(['status' => 'mediation']);
    }

    public function resolve(
        string $resolution,
        float $resolutionAmount = null,
        string $notes = null,
        int $resolvedById = null
    ): void {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolution_amount' => $resolutionAmount,
            'resolution_notes' => $notes,
            'resolved_by' => $resolvedById,
            'resolved_at' => now(),
        ]);

        // Handle escrow based on resolution
        $escrow = $this->milestone?->escrow ?? $this->contract->escrowTransactions()->pending()->first();
        
        if ($escrow) {
            match($resolution) {
                'refund_full' => $escrow->refund('Dispute resolved - full refund'),
                'release_full' => $escrow->release('Dispute resolved - full release'),
                default => null, // Partial resolutions handled separately
            };
        }

        // Reactivate contract if not cancelled
        if (!in_array($resolution, ['refund_full'])) {
            $this->contract->update(['status' => 'active']);
        }
    }

    public function escalate(): void
    {
        $this->update(['status' => 'escalated']);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }
}
