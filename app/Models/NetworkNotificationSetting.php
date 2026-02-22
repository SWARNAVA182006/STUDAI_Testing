<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkNotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'network_notification_settings';

    protected $fillable = [
        'user_id',
        'connection_requests',
        'connection_accepted',
        'messages',
        'mentions',
        'post_likes',
        'post_comments',
        'group_invites',
        'event_invites',
        'event_reminders',
        'mentorship_requests',
        'weekly_digest',
    ];

    protected $casts = [
        'connection_requests' => 'boolean',
        'connection_accepted' => 'boolean',
        'messages' => 'boolean',
        'mentions' => 'boolean',
        'post_likes' => 'boolean',
        'post_comments' => 'boolean',
        'group_invites' => 'boolean',
        'event_invites' => 'boolean',
        'event_reminders' => 'boolean',
        'mentorship_requests' => 'boolean',
        'weekly_digest' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'connection_requests' => true,
                'connection_accepted' => true,
                'messages' => true,
                'mentions' => true,
                'post_likes' => true,
                'post_comments' => true,
                'group_invites' => true,
                'event_invites' => true,
                'event_reminders' => true,
                'mentorship_requests' => true,
                'weekly_digest' => true,
            ]
        );
    }

    public function shouldNotify(string $type): bool
    {
        return match ($type) {
            'connection_request' => $this->connection_requests,
            'connection_accepted' => $this->connection_accepted,
            'message' => $this->messages,
            'mention' => $this->mentions,
            'post_like' => $this->post_likes,
            'post_comment' => $this->post_comments,
            'group_invite' => $this->group_invites,
            'event_invite' => $this->event_invites,
            'event_reminder' => $this->event_reminders,
            'mentorship_request' => $this->mentorship_requests,
            'weekly_digest' => $this->weekly_digest,
            default => true,
        };
    }

    public function enableAll(): void
    {
        $this->update([
            'connection_requests' => true,
            'connection_accepted' => true,
            'messages' => true,
            'mentions' => true,
            'post_likes' => true,
            'post_comments' => true,
            'group_invites' => true,
            'event_invites' => true,
            'event_reminders' => true,
            'mentorship_requests' => true,
            'weekly_digest' => true,
        ]);
    }

    public function disableAll(): void
    {
        $this->update([
            'connection_requests' => false,
            'connection_accepted' => false,
            'messages' => false,
            'mentions' => false,
            'post_likes' => false,
            'post_comments' => false,
            'group_invites' => false,
            'event_invites' => false,
            'event_reminders' => false,
            'mentorship_requests' => false,
            'weekly_digest' => false,
        ]);
    }
}
