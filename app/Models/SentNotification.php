<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'title',
        'body',
        'data',
        'status',
        'sent_at',
        'clicked_at',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as clicked.
     */
    public function markAsClicked(): void
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Scope to get recent notifications.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by channel.
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to filter by notification type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Get click-through rate for notification type.
     */
    public static function getClickThroughRate(string $type, int $days = 30): float
    {
        $sent = static::type($type)
            ->where('sent_at', '>=', now()->subDays($days))
            ->count();

        if ($sent === 0) {
            return 0.0;
        }

        $clicked = static::type($type)
            ->where('sent_at', '>=', now()->subDays($days))
            ->where('status', 'clicked')
            ->count();

        return round(($clicked / $sent) * 100, 2);
    }

    /**
     * Get delivery rate for channel.
     */
    public static function getDeliveryRate(string $channel, int $days = 30): float
    {
        $sent = static::channel($channel)
            ->where('sent_at', '>=', now()->subDays($days))
            ->count();

        if ($sent === 0) {
            return 0.0;
        }

        $delivered = static::channel($channel)
            ->where('sent_at', '>=', now()->subDays($days))
            ->whereIn('status', ['sent', 'clicked', 'dismissed'])
            ->count();

        return round(($delivered / $sent) * 100, 2);
    }
}
