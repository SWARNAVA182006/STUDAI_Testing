<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
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
        'enabled',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Available notification channels.
     */
    public const CHANNELS = [
        'push' => 'Push Notifications',
        'email' => 'Email',
        'sms' => 'SMS',
    ];

    /**
     * Available notification types.
     */
    public const TYPES = [
        'job_alert' => 'Job Alerts',
        'application_status' => 'Application Status Updates',
        'interview_reminder' => 'Interview Reminders',
        'message_received' => 'New Messages',
        'profile_view' => 'Profile Views',
        'saved_job_update' => 'Saved Job Updates',
        'recommendation' => 'Job Recommendations',
        'assessment_invitation' => 'Assessment Invitations',
        'marketing' => 'Marketing Updates',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if notification type is enabled for user.
     */
    public static function isEnabled(int $userId, string $channel, string $type): bool
    {
        $preference = static::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('notification_type', $type)
            ->first();

        // Default to enabled if no preference set
        return $preference ? $preference->enabled : true;
    }

    /**
     * Enable notification type for user.
     */
    public static function enable(int $userId, string $channel, string $type, array $settings = []): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'channel' => $channel,
                'notification_type' => $type,
            ],
            [
                'enabled' => true,
                'settings' => $settings,
            ]
        );
    }

    /**
     * Disable notification type for user.
     */
    public static function disable(int $userId, string $channel, string $type): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'channel' => $channel,
                'notification_type' => $type,
            ],
            ['enabled' => false]
        );
    }

    /**
     * Get all preferences for user.
     */
    public static function getUserPreferences(int $userId): array
    {
        $preferences = static::where('user_id', $userId)->get();

        $result = [];
        foreach (static::CHANNELS as $channelKey => $channelName) {
            foreach (static::TYPES as $typeKey => $typeName) {
                $preference = $preferences->where('channel', $channelKey)
                    ->where('notification_type', $typeKey)
                    ->first();

                $result[$channelKey][$typeKey] = [
                    'enabled' => $preference ? $preference->enabled : true,
                    'settings' => $preference ? $preference->settings : [],
                ];
            }
        }

        return $result;
    }

    /**
     * Scope to get enabled preferences.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
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
}
