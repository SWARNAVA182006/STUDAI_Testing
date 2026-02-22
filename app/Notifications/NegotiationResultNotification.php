<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\NegotiationSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NegotiationResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected NegotiationSession $session,
        protected string $outcome,
        protected ?float $finalAmount = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $outcomeLabel = ucfirst(str_replace('_', ' ', $this->outcome));
        $amountLine = $this->finalAmount
            ? "Final Amount: \${$this->finalAmount}"
            : '';

        return (new MailMessage())
            ->subject('Negotiation Session Summary')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your negotiation session has concluded with outcome: {$outcomeLabel}.")
            ->lineIf(!empty($amountLine), $amountLine)
            ->action('View Negotiation Details', url('/negotiations'))
            ->line('Review your negotiation strategy for future reference.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'negotiation_result',
            'session_id' => $this->session->id,
            'outcome' => $this->outcome,
            'final_amount' => $this->finalAmount,
            'message' => "Negotiation completed: {$this->outcome}",
        ];
    }
}
