<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'retry_attempts',
        'timeout_seconds',
        'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'retry_attempts' => 'integer',
        'timeout_seconds' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Available webhook events
     */
    const EVENTS = [
        'application.received',
        'application.status_changed',
        'application.reviewed',
        'interview.scheduled',
        'interview.completed',
        'interview.cancelled',
        'job.published',
        'job.closed',
        'job.expired',
        'candidate.hired',
        'referral.created',
        'referral.hired',
    ];

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get deliveries
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if webhook subscribes to event
     */
    public function subscribesToEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Generate signature for payload
     */
    public function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->secret);
    }

    /**
     * Update last triggered timestamp
     */
    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Scope for active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}
