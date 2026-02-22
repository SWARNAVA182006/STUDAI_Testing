<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Payment Failed Notification
 *
 * Notifies users about payment failures and subscription status changes.
 * Handles multiple scenarios: initial failure, retry failures, final warning, expiration.
 */
class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The subscription instance.
     */
    protected UserSubscription $subscription;

    /**
     * The notification type.
     */
    protected string $type;

    /**
     * Error or message details.
     */
    protected string $message;

    /**
     * Current retry attempt.
     */
    protected int $attempt;

    /**
     * Maximum retry attempts.
     */
    protected int $maxAttempts;

    /**
     * Notification types.
     */
    public const TYPE_INITIAL_FAILURE = 'initial_failure';
    public const TYPE_RETRY_FAILED = 'retry_failed';
    public const TYPE_FINAL_WARNING = 'final_warning';
    public const TYPE_EXPIRED = 'expired';
    public const TYPE_RECOVERED = 'recovered';

    /**
     * Create a new notification instance.
     */
    public function __construct(
        UserSubscription $subscription,
        string $type = self::TYPE_INITIAL_FAILURE,
        string $message = '',
        int $attempt = 1,
        int $maxAttempts = 3
    ) {
        $this->subscription = $subscription;
        $this->type = $type;
        $this->message = $message;
        $this->attempt = $attempt;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->type) {
            self::TYPE_INITIAL_FAILURE => $this->initialFailureMail($notifiable),
            self::TYPE_RETRY_FAILED => $this->retryFailedMail($notifiable),
            self::TYPE_FINAL_WARNING => $this->finalWarningMail($notifiable),
            self::TYPE_EXPIRED => $this->expiredMail($notifiable),
            self::TYPE_RECOVERED => $this->recoveredMail($notifiable),
            default => $this->initialFailureMail($notifiable),
        };
    }

    /**
     * Initial payment failure email.
     */
    protected function initialFailureMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? 'your subscription';
        $gracePeriodEnds = $this->subscription->grace_period_ends_at?->format('F j, Y');

        return (new MailMessage)
            ->subject('Action Required: Payment Failed for Your Subscription')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("We were unable to process your payment for {$planName}.")
            ->line('')
            ->line('Your subscription is now in a grace period and you still have access to all features.')
            ->line('')
            ->when($gracePeriodEnds, function ($mail) use ($gracePeriodEnds) {
                return $mail->line("**Grace period ends:** {$gracePeriodEnds}");
            })
            ->line('')
            ->line('To avoid any interruption to your service, please update your payment method.')
            ->action('Update Payment Method', url('/settings/billing'))
            ->line('')
            ->line("If you have any questions, please don't hesitate to contact our support team.");
    }

    /**
     * Retry failed email.
     */
    protected function retryFailedMail(object $notifiable): MailMessage
    {
        $remainingAttempts = $this->maxAttempts - $this->attempt;
        $gracePeriodEnds = $this->subscription->grace_period_ends_at?->format('F j, Y');

        return (new MailMessage)
            ->subject('Payment Retry Failed - Action Required')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("We attempted to process your subscription payment but it was unsuccessful.")
            ->line('')
            ->line("**Attempt:** {$this->attempt} of {$this->maxAttempts}")
            ->when($remainingAttempts > 0, function ($mail) use ($remainingAttempts) {
                return $mail->line("We will automatically retry {$remainingAttempts} more time(s).");
            })
            ->line('')
            ->when($gracePeriodEnds, function ($mail) use ($gracePeriodEnds) {
                return $mail->line("**Service continues until:** {$gracePeriodEnds}");
            })
            ->line('')
            ->line('Please update your payment method to ensure uninterrupted service.')
            ->action('Update Payment Method', url('/settings/billing'))
            ->line('')
            ->line('Common reasons for payment failure:')
            ->line('• Insufficient funds')
            ->line('• Expired card')
            ->line('• Card declined by your bank');
    }

    /**
     * Final warning email.
     */
    protected function finalWarningMail(object $notifiable): MailMessage
    {
        $gracePeriodEnds = $this->subscription->grace_period_ends_at?->format('F j, Y');
        $planName = $this->subscription->plan?->name ?? 'your subscription';

        return (new MailMessage)
            ->subject('URGENT: Final Notice - Subscription Will Be Suspended')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("⚠️ **This is your final notice** regarding your {$planName} subscription.")
            ->line('')
            ->line("Despite multiple attempts, we've been unable to process your payment. Your subscription will be suspended soon.")
            ->line('')
            ->when($gracePeriodEnds, function ($mail) use ($gracePeriodEnds) {
                return $mail->line("**Your service will end:** {$gracePeriodEnds}");
            })
            ->line('')
            ->line('**What happens when your subscription expires:**')
            ->line('• You will lose access to premium features')
            ->line('• Your agent will stop running')
            ->line('• Application history will be preserved')
            ->line('')
            ->line('**Take action now** to avoid losing your subscription:')
            ->action('Update Payment Method Now', url('/settings/billing'))
            ->line('')
            ->line('If you intended to cancel your subscription, you can ignore this email.');
    }

    /**
     * Subscription expired email.
     */
    protected function expiredMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? 'your subscription';

        return (new MailMessage)
            ->subject('Your Subscription Has Expired')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("Your {$planName} subscription has expired due to payment failure.")
            ->line('')
            ->line('**What this means:**')
            ->line('• Access to premium features has been suspended')
            ->line('• Your autonomous agent has been paused')
            ->line('• Your account data is safe and preserved')
            ->line('')
            ->line('**Good news:** You can reactivate your subscription at any time!')
            ->line('')
            ->action('Reactivate Subscription', url('/pricing'))
            ->line('')
            ->line("We'd love to have you back. If you have any questions or need assistance, please reach out to our support team.");
    }

    /**
     * Payment recovered email.
     */
    protected function recoveredMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? 'your subscription';

        return (new MailMessage)
            ->subject('Payment Successful - Subscription Restored')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("Great news! Your payment for {$planName} has been successfully processed.")
            ->line('')
            ->line('✅ Your subscription is now active')
            ->line('✅ All premium features have been restored')
            ->line('✅ Your agent is running again')
            ->line('')
            ->action('View Dashboard', url('/dashboard'))
            ->line('')
            ->line('Thank you for being a valued subscriber!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_failed',
            'notification_type' => $this->type,
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan?->name,
            'message' => $this->message,
            'attempt' => $this->attempt,
            'max_attempts' => $this->maxAttempts,
            'grace_period_ends_at' => $this->subscription->grace_period_ends_at?->toIso8601String(),
            'action_url' => url('/settings/billing'),
        ];
    }
}
