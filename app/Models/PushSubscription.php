<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
        'device_type',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update last used timestamp.
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get subscription data for web push.
     */
    public function getSubscriptionData(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->public_key,
                'auth' => $this->auth_token,
            ],
            'contentEncoding' => $this->content_encoding,
        ];
    }

    /**
     * Scope to get active subscriptions (used in last 30 days).
     */
    public function scopeActive($query)
    {
        return $query->where('last_used_at', '>=', now()->subDays(30));
    }

    /**
     * Scope to get stale subscriptions (not used in 60 days).
     */
    public function scopeStale($query)
    {
        return $query->where('last_used_at', '<=', now()->subDays(60));
    }

    /**
     * Scope to filter by device type.
     */
    public function scopeDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }
}
