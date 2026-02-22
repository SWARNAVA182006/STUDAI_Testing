<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'status_code',
        'response_body',
        'attempt_number',
        'response_time_ms',
        'status',
        'error_message',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_code' => 'integer',
        'attempt_number' => 'integer',
        'response_time_ms' => 'integer',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the webhook
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Mark as success
     */
    public function markAsSuccess(int $statusCode, string $responseBody, int $responseTime): void
    {
        $this->update([
            'status' => 'success',
            'status_code' => $statusCode,
            'response_body' => $responseBody,
            'response_time_ms' => $responseTime,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error, ?int $statusCode = null): void
    {
        $this->update([
            'status' => 'failed',
            'status_code' => $statusCode,
            'error_message' => $error,
        ]);
    }

    /**
     * Scope for pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
