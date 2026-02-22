<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'transaction_id',
        'order_id',
        'payment_gateway',
        'amount',
        'currency',
        'gateway_fee',
        'tax_amount',
        'status',
        'payment_method',
        'gateway_response',
        'error_message',
        'retry_count',
        'refund_amount',
        'refund_id',
        'refunded_at',
        'paid_at',
        'notes',
        'metadata',
        'initiated_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'gateway_response' => 'encrypted:array',
        'metadata' => 'encrypted:array',
        'paid_at' => 'datetime',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
    
    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }

    /**
     * Scopes
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    /**
     * Accessors
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function getIsRefundedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->amount - ($this->gateway_fee ?? 0) - ($this->refund_amount ?? 0);
    }

    /**
     * Helper methods
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    public function markAsSuccess(array $gatewayResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'gateway_response' => $gatewayResponse,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $errorMessage = null, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'gateway_response' => $gatewayResponse,
            'failed_at' => now(),
        ]);
    }

    public function canBeRetried(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->retry_count < 3;
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }
}

