<?php

namespace App\Notifications\Agent;

use App\Models\AgentConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Limit Reached Notification
 * 
 * Notifies user when daily or monthly application limit is reached.
 */
class LimitReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AgentConfiguration $config,
        public string $limitType, // 'daily' or 'monthly'
        public int $limit
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $limitText = $this->limitType === 'daily' ? 'daily' : 'monthly';
        $resetText = $this->limitType === 'daily' 
            ? 'tomorrow' 
            : 'next month';

        return (new MailMessage)
            ->subject('Agent Application Limit Reached')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your autonomous agent has reached its **' . $limitText . ' application limit** of ' . $this->limit . ' applications.')
            ->line('The agent will resume submitting applications ' . $resetText . '.')
            ->line('**What you can do:**')
            ->line('• Upgrade your subscription plan for higher limits')
            ->line('• Adjust your job search criteria to be more selective')
            ->line('• Wait for the limit to reset ' . $resetText)
            ->action('View Dashboard', route('agent.dashboard'))
            ->line('Thank you for using StudAI Career!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'limit_reached',
            'config_id' => $this->config->id,
            'limit_type' => $this->limitType,
            'limit' => $this->limit,
            'message' => ucfirst($this->limitType) . " application limit of {$this->limit} reached",
        ];
    }
}
