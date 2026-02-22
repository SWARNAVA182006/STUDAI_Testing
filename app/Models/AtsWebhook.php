<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ATS Webhook - Webhook endpoints for receiving real-time updates from ATS.
 */
class AtsWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'ats_connection_id',
        'event_type',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'last_triggered_at',
        'trigger_count',
        'failure_count',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_count' => 'integer',
        'failure_count' => 'integer',
        'metadata' => 'array',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * Get the ATS connection for this webhook.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(AtsConnection::class, 'ats_connection_id');
    }

    /**
     * Scope to get active webhooks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get webhooks by event type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Generate a new webhook secret.
     */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Verify a webhook signature.
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Record a successful webhook trigger.
     */
    public function recordSuccess(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1,
            'last_error' => null,
        ]);
    }

    /**
     * Record a failed webhook trigger.
     */
    public function recordFailure(string $error): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'failure_count' => $this->failure_count + 1,
            'last_error' => $error,
        ]);
    }

    /**
     * Disable the webhook after too many failures.
     */
    public function disableIfTooManyFailures(int $threshold = 10): bool
    {
        if ($this->failure_count >= $threshold) {
            $this->update(['is_active' => false]);
            return true;
        }
        return false;
    }

    /**
     * Get the supported event types.
     */
    public static function eventTypes(): array
    {
        return [
            'candidate.created',
            'candidate.updated',
            'candidate.deleted',
            'application.created',
            'application.updated',
            'application.status_changed',
            'job.created',
            'job.updated',
            'job.closed',
            'interview.scheduled',
            'interview.completed',
            'offer.created',
            'offer.accepted',
            'offer.rejected',
        ];
    }
}
