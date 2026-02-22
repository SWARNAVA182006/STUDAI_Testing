<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'project_id',
        'proposal_id',
        'employer_id',
        'freelancer_id',
        'terms',
        'total_amount',
        'platform_fee_percent',
        'platform_fee_amount',
        'freelancer_amount',
        'currency',
        'payment_type',
        'status',
        'started_at',
        'deadline',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_fee_percent' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'freelancer_amount' => 'decimal:2',
        'started_at' => 'datetime',
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProject::class, 'project_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProposal::class, 'proposal_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(MarketplaceMilestone::class, 'contract_id')->orderBy('order');
    }

    public function escrowTransactions(): HasMany
    {
        return $this->hasMany(MarketplaceEscrow::class, 'contract_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MarketplaceMessage::class, 'contract_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'contract_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(MarketplaceTimeLog::class, 'contract_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(MarketplaceDispute::class, 'contract_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('employer_id', $userId)
              ->orWhere('freelancer_id', $userId);
        });
    }

    // Helpers
    public function getTotalAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->total_amount));
    }

    public function getFreelancerAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->freelancer_amount));
    }

    public function getProgressAttribute(): float
    {
        if ($this->milestones->isEmpty()) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $completedMilestones = $this->milestones->whereIn('status', ['approved', 'released'])->count();
        return ($completedMilestones / $this->milestones->count()) * 100;
    }

    public function getFundedAmountAttribute(): float
    {
        return $this->escrowTransactions()
            ->where('status', 'funded')
            ->sum('amount');
    }

    public function getReleasedAmountAttribute(): float
    {
        return $this->escrowTransactions()
            ->where('status', 'released')
            ->sum('net_amount');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasActiveDispute(): bool
    {
        return $this->disputes()->whereIn('status', ['open', 'under_review', 'mediation'])->exists();
    }

    public function start(): void
    {
        $this->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->project->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Update freelancer stats
        $freelancerProfile = FreelancerProfile::where('user_id', $this->freelancer_id)->first();
        if ($freelancerProfile) {
            $freelancerProfile->increment('completed_projects');
            $freelancerProfile->increment('total_earnings', $this->freelancer_amount);
            $freelancerProfile->decrement('ongoing_projects');
        }
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->project->update(['status' => 'cancelled']);
    }

    public function createMilestones(array $milestonesData): void
    {
        foreach ($milestonesData as $index => $data) {
            $this->milestones()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'deliverables' => $data['deliverables'] ?? null,
                'amount' => $data['amount'],
                'currency' => $this->currency,
                'order' => $index,
                'due_date' => $data['due_date'] ?? null,
                'status' => 'pending',
            ]);
        }
    }
}
