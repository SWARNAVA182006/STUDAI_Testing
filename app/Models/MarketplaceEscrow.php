<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceEscrow extends Model
{
    use HasFactory;

    protected $table = 'marketplace_escrow';

    protected $fillable = [
        'escrow_id',
        'contract_id',
        'milestone_id',
        'payer_id',
        'payee_id',
        'amount',
        'platform_fee',
        'net_amount',
        'currency',
        'status',
        'payment_gateway',
        'payment_transaction_id',
        'payout_transaction_id',
        'funded_at',
        'held_at',
        'released_at',
        'refunded_at',
        'release_note',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'funded_at' => 'datetime',
        'held_at' => 'datetime',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($escrow) {
            if (empty($escrow->escrow_id)) {
                $escrow->escrow_id = 'ESC-' . strtoupper(uniqid());
            }

            // Calculate net amount if not set
            if (empty($escrow->net_amount)) {
                $escrow->net_amount = $escrow->amount - $escrow->platform_fee;
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

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
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

    public function scopeHeld($query)
    {
        return $query->where('status', 'held');
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    // Helpers
    public function getAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->amount));
    }

    public function getNetAmountDisplayAttribute(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->net_amount));
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFunded(): bool
    {
        return $this->status === 'funded';
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    public function fund(string $gateway, string $transactionId): void
    {
        $this->update([
            'status' => 'funded',
            'payment_gateway' => $gateway,
            'payment_transaction_id' => $transactionId,
            'funded_at' => now(),
        ]);

        // Fund the associated milestone
        if ($this->milestone) {
            $this->milestone->fund();
        }
    }

    public function hold(): void
    {
        $this->update([
            'status' => 'held',
            'held_at' => now(),
        ]);
    }

    public function release(string $note = null): void
    {
        $this->update([
            'status' => 'released',
            'released_at' => now(),
            'release_note' => $note,
        ]);

        // Update freelancer earnings
        $freelancerProfile = FreelancerProfile::where('user_id', $this->payee_id)->first();
        if ($freelancerProfile) {
            $freelancerProfile->increment('total_earnings', $this->net_amount);
        }

        // TODO: Trigger actual payout via payment gateway
    }

    public function refund(string $reason): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_reason' => $reason,
        ]);

        // TODO: Trigger actual refund via payment gateway
    }

    public function dispute(): void
    {
        $this->update(['status' => 'disputed']);
    }

    public static function createForMilestone(
        MarketplaceMilestone $milestone,
        float $platformFeePercent = 10
    ): self {
        $contract = $milestone->contract;
        $platformFee = $milestone->amount * ($platformFeePercent / 100);
        $netAmount = $milestone->amount - $platformFee;

        return self::create([
            'contract_id' => $contract->id,
            'milestone_id' => $milestone->id,
            'payer_id' => $contract->employer_id,
            'payee_id' => $contract->freelancer_id,
            'amount' => $milestone->amount,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
            'currency' => $milestone->currency,
            'status' => 'pending',
        ]);
    }
}
