<?php

namespace App\Notifications\Agent;

use App\Models\AgentConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Agent Paused Notification
 * 
 * Notifies user when the agent is automatically paused due to errors or issues.
 */
class AgentPausedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AgentConfiguration $config,
        public string $reason,
        public ?string $errorMessage = null
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
        $message = (new MailMessage)
            ->subject('Agent Paused - Action Required')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your autonomous agent has been automatically paused.')
            ->line('**Reason:** ' . $this->reason);

        if ($this->errorMessage) {
            $message->line('**Details:** ' . $this->errorMessage);
        }

        $message->line('**What to do next:**');
        
        // Provide specific guidance based on reason
        if (str_contains(strtolower($this->reason), 'resume') || str_contains(strtolower($this->reason), 'profile')) {
            $message->line('• Update your profile with complete information');
            $message->line('• Upload an up-to-date resume');
            $message->line('• Ensure all required fields are filled');
        } elseif (str_contains(strtolower($this->reason), 'error') || str_contains(strtolower($this->reason), 'failure')) {
            $message->line('• Check your agent configuration');
            $message->line('• Review error logs in your dashboard');
            $message->line('• Contact support if the issue persists');
        } elseif (str_contains(strtolower($this->reason), 'subscription')) {
            $message->line('• Upgrade your subscription plan');
            $message->line('• Renew your subscription if expired');
        }

        $message->action('Resume Agent', route('agent.dashboard'))
            ->line('Once the issue is resolved, you can resume your agent from the dashboard.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'agent_paused',
            'config_id' => $this->config->id,
            'reason' => $this->reason,
            'error_message' => $this->errorMessage,
            'message' => "Agent paused: {$this->reason}",
        ];
    }
}
