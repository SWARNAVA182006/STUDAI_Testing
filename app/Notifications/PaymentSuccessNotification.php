<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;
use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PaymentTransaction $transaction,
        public UserSubscription $subscription
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $plan = $this->transaction->subscriptionPlan;
        $amount = number_format($this->transaction->amount, 2);
        $currency = $this->transaction->currency;
        $nextBillingDate = $this->subscription->current_period_end->format('F d, Y');
        
        $message = (new MailMessage)
            ->subject('Payment Successful - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment of ' . $currency . ' ' . $amount . ' has been successfully processed.')
            ->line('**Plan:** ' . $plan->name)
            ->line('**Transaction ID:** ' . $this->transaction->transaction_id)
            ->line('**Payment Method:** ' . ucfirst($this->transaction->payment_method ?? 'N/A'))
            ->line('**Next Billing Date:** ' . $nextBillingDate);

        // Add plan features
        if ($plan->applications_limit) {
            $message->line('**Applications per month:** ' . ($plan->applications_limit === -1 ? 'Unlimited' : $plan->applications_limit));
        }
        
        if ($plan->ai_credits) {
            $message->line('**AI Credits per month:** ' . ($plan->ai_credits === -1 ? 'Unlimited' : $plan->ai_credits));
        }

        $message->action('View Dashboard', url('/dashboard'))
            ->line('You can view your transaction history and subscription details in your dashboard.')
            ->line('If you have any questions, feel free to contact our support team.')
            ->line('Thank you for choosing ' . config('app.name') . '!');

        return $message;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $plan = $this->transaction->subscriptionPlan;
        
        return [
            'type' => 'payment_success',
            'title' => 'Payment Successful',
            'message' => 'Your payment of ' . $this->transaction->currency . ' ' . number_format($this->transaction->amount, 2) . ' for ' . $plan->name . ' plan has been processed successfully.',
            'transaction_id' => $this->transaction->id,
            'subscription_id' => $this->subscription->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'payment_method' => $this->transaction->payment_method,
            'next_billing_date' => $this->subscription->current_period_end->toDateString(),
            'action_url' => url('/dashboard'),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
