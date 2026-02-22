<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_letter_id',
        'initiated_by',
        'round_number',
        'requested_salary',
        'requested_signing_bonus',
        'requested_start_date',
        'requested_equity_shares',
        'requested_benefits',
        'other_requests',
        'justification',
        'status',
        'counter_salary',
        'counter_signing_bonus',
        'counter_start_date',
        'counter_equity_shares',
        'counter_benefits',
        'employer_response',
        'responded_by',
        'responded_at',
    ];

    protected $casts = [
        'requested_salary' => 'decimal:2',
        'requested_signing_bonus' => 'decimal:2',
        'requested_start_date' => 'date',
        'requested_equity_shares' => 'integer',
        'counter_salary' => 'decimal:2',
        'counter_signing_bonus' => 'decimal:2',
        'counter_start_date' => 'date',
        'counter_equity_shares' => 'integer',
        'responded_at' => 'datetime',
    ];

    public function offerLetter(): BelongsTo
    {
        return $this->belongsTo(OfferLetter::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->whereIn('status', ['accepted', 'partially_accepted']);
    }

    // Accessors
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsAcceptedAttribute(): bool
    {
        return in_array($this->status, ['accepted', 'partially_accepted']);
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    public function getSalaryDifferenceAttribute(): ?float
    {
        if ($this->requested_salary && $this->offerLetter) {
            return (float) $this->requested_salary - (float) $this->offerLetter->base_salary;
        }
        return null;
    }

    public function getSalaryDifferencePercentAttribute(): ?float
    {
        if ($this->requested_salary && $this->offerLetter && $this->offerLetter->base_salary > 0) {
            $diff = $this->salary_difference;
            return ($diff / (float) $this->offerLetter->base_salary) * 100;
        }
        return null;
    }

    // Actions
    public function accept(?string $response = null, ?int $responderId = null): void
    {
        $this->update([
            'status' => 'accepted',
            'employer_response' => $response,
            'responded_by' => $responderId ?? auth()->id(),
            'responded_at' => now(),
        ]);

        // Update the offer letter with the accepted terms
        $this->offerLetter->update([
            'base_salary' => $this->requested_salary ?? $this->offerLetter->base_salary,
            'signing_bonus' => $this->requested_signing_bonus ?? $this->offerLetter->signing_bonus,
            'start_date' => $this->requested_start_date ?? $this->offerLetter->start_date,
            'equity_shares' => $this->requested_equity_shares ?? $this->offerLetter->equity_shares,
            'status' => 'sent',
        ]);

        $this->offerLetter->logActivity('counter_offer_accepted', 'Counter offer accepted', [
            'round' => $this->round_number,
        ]);
    }

    public function partiallyAccept(array $acceptedTerms, ?string $response = null, ?int $responderId = null): void
    {
        $this->update([
            'status' => 'partially_accepted',
            'counter_salary' => $acceptedTerms['salary'] ?? null,
            'counter_signing_bonus' => $acceptedTerms['signing_bonus'] ?? null,
            'counter_start_date' => $acceptedTerms['start_date'] ?? null,
            'counter_equity_shares' => $acceptedTerms['equity_shares'] ?? null,
            'counter_benefits' => $acceptedTerms['benefits'] ?? null,
            'employer_response' => $response,
            'responded_by' => $responderId ?? auth()->id(),
            'responded_at' => now(),
        ]);

        $this->offerLetter->logActivity('counter_offer_partially_accepted', 'Counter offer partially accepted', [
            'round' => $this->round_number,
            'accepted_terms' => $acceptedTerms,
        ]);
    }

    public function reject(?string $response = null, ?int $responderId = null): void
    {
        $this->update([
            'status' => 'rejected',
            'employer_response' => $response,
            'responded_by' => $responderId ?? auth()->id(),
            'responded_at' => now(),
        ]);

        $this->offerLetter->logActivity('counter_offer_rejected', 'Counter offer rejected', [
            'round' => $this->round_number,
        ]);
    }

    public function getHasChangesAttribute(): bool
    {
        return $this->requested_salary !== null
            || $this->requested_signing_bonus !== null
            || $this->requested_start_date !== null
            || $this->requested_equity_shares !== null
            || $this->requested_benefits !== null
            || $this->other_requests !== null;
    }

    public function getChangeSummaryAttribute(): array
    {
        $changes = [];
        $offer = $this->offerLetter;

        if ($this->requested_salary !== null) {
            $changes['salary'] = [
                'original' => (float) $offer->base_salary,
                'requested' => (float) $this->requested_salary,
                'difference' => (float) $this->requested_salary - (float) $offer->base_salary,
            ];
        }

        if ($this->requested_signing_bonus !== null) {
            $changes['signing_bonus'] = [
                'original' => (float) ($offer->signing_bonus ?? 0),
                'requested' => (float) $this->requested_signing_bonus,
            ];
        }

        if ($this->requested_start_date !== null) {
            $changes['start_date'] = [
                'original' => $offer->start_date?->format('Y-m-d'),
                'requested' => $this->requested_start_date->format('Y-m-d'),
            ];
        }

        if ($this->requested_equity_shares !== null) {
            $changes['equity_shares'] = [
                'original' => $offer->equity_shares ?? 0,
                'requested' => $this->requested_equity_shares,
            ];
        }

        return $changes;
    }
}
