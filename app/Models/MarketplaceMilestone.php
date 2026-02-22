<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'title',
        'description',
        'deliverables',
        'amount',
        'currency',
        'order',
        'status',
        'due_date',
        'funded_at',
        'started_at',
        'submitted_at',
        'approved_at',
        'released_at',
        'submission_note',
        'submission_files',
        'revision_note',
        'revision_count',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submission_files' => 'array',
        'due_date' => 'datetime',
        'funded_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketplaceContract::class, 'contract_id');
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(MarketplaceEscrow::class, 'milestone_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFunded($query)
    {
        return $query->where('status', 'funded');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Helpers
    public function getAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->amount));
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Awaiting Funding',
            'funded' => 'Funded - Ready to Start',
            'in_progress' => 'In Progress',
            'submitted' => 'Submitted for Review',
            'revision_requested' => 'Revision Requested',
            'approved' => 'Approved - Pending Release',
            'released' => 'Payment Released',
            'disputed' => 'Under Dispute',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'funded' => 'blue',
            'in_progress' => 'yellow',
            'submitted' => 'purple',
            'revision_requested' => 'orange',
            'approved' => 'green',
            'released' => 'emerald',
            'disputed' => 'red',
            default => 'gray',
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFunded(): bool
    {
        return $this->status === 'funded';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    public function fund(): void
    {
        $this->update([
            'status' => 'funded',
            'funded_at' => now(),
        ]);

        // Activate contract if this is the first funded milestone
        $contract = $this->contract;
        if ($contract->isPending()) {
            $contract->start();
        }
    }

    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function submit(string $note = null, array $files = null): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submission_note' => $note,
            'submission_files' => $files,
        ]);
    }

    public function requestRevision(string $note): void
    {
        $this->update([
            'status' => 'revision_requested',
            'revision_note' => $note,
            'revision_count' => $this->revision_count + 1,
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function release(): void
    {
        $this->update([
            'status' => 'released',
            'released_at' => now(),
        ]);

        // Release escrow funds
        if ($this->escrow) {
            $this->escrow->release();
        }

        // Check if all milestones are released to complete contract
        $contract = $this->contract;
        $pendingMilestones = $contract->milestones()
            ->whereNotIn('status', ['released'])
            ->count();

        if ($pendingMilestones === 0) {
            $contract->complete();
        }
    }
}
