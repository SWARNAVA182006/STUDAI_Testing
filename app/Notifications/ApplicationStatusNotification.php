<?php

namespace App\Notifications;

use App\Models\SentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ApplicationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $application,
        public $oldStatus,
        public $newStatus
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [];

        if ($this->isPushEnabled($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable): WebPushMessage
    {
        $title = 'Application Update';
        $body = sprintf(
            'Your application for %s at %s has been updated to: %s',
            $this->application->job->title,
            $this->application->job->company_name,
            $this->getStatusLabel($this->newStatus)
        );

        // Log notification
        SentNotification::create([
            'user_id' => $notifiable->id,
            'channel' => 'push',
            'notification_type' => 'application_status',
            'title' => $title,
            'body' => $body,
            'data' => [
                'application_id' => $this->application->id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
            ],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return (new WebPushMessage)
            ->title($title)
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/badge-72x72.png')
            ->body($body)
            ->action('View Application', 'view_application')
            ->data([
                'url' => route('applications.show', $this->application->id),
                'application_id' => $this->application->id,
            ])
            ->tag('application-' . $this->application->id)
            ->requireInteraction(true);
    }

    /**
     * Check if push notifications are enabled.
     */
    private function isPushEnabled($notifiable): bool
    {
        return \App\Models\NotificationPreference::isEnabled(
            $notifiable->id,
            'push',
            'application_status'
        );
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'Pending Review',
            'reviewed' => 'Reviewed',
            'shortlisted' => 'Shortlisted',
            'interview' => 'Interview Scheduled',
            'offered' => 'Offer Extended',
            'accepted' => 'Offer Accepted',
            'rejected' => 'Not Selected',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($status),
        };
    }
}
