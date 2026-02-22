<?php

namespace App\Notifications;

use App\Models\SentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestPushNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable): WebPushMessage
    {
        $title = 'Test Notification';
        $body = 'Your push notifications are working! 🎉';

        // Log notification
        SentNotification::create([
            'user_id' => $notifiable->id,
            'channel' => 'push',
            'notification_type' => 'test',
            'title' => $title,
            'body' => $body,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return (new WebPushMessage)
            ->title($title)
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/badge-72x72.png')
            ->body($body)
            ->action('Open App', 'open_app')
            ->data(['url' => route('dashboard')])
            ->tag('test-notification');
    }
}
