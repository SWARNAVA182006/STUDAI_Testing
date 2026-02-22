<?php

namespace App\Notifications;

use App\Models\EmployeeReferral;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected EmployeeReferral $referral;
    protected string $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(EmployeeReferral $referral, string $action)
    {
        $this->referral = $referral;
        $this->action = $action;
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
        $mail = (new MailMessage)
            ->subject('Referral Status Updated');

        if ($this->action === 'approved') {
            $mail->line('Your referral for ' . $this->referral->candidate->name . ' has been approved!')
                ->line('The candidate has been added to the application pool for the position: ' . $this->referral->job->title)
                ->line('Bonus Amount: ₹' . number_format($this->referral->bonus_amount))
                ->action('View Referral', route('employer.referrals.index'));
        } elseif ($this->action === 'rejected') {
            $mail->line('Your referral for ' . $this->referral->candidate->name . ' was not approved.')
                ->line('Reason: ' . ($this->referral->rejection_reason ?? 'Not specified'))
                ->action('View Details', route('employer.referrals.index'));
        } elseif ($this->action === 'hired') {
            $mail->line('Great news! Your referral ' . $this->referral->candidate->name . ' has been hired!')
                ->line('Position: ' . $this->referral->job->title)
                ->line('Bonus Amount: ₹' . number_format($this->referral->bonus_amount))
                ->line('Your bonus will be processed after the probation period.')
                ->action('View Referral', route('employer.referrals.index'));
        } elseif ($this->action === 'bonus_paid') {
            $mail->line('Your referral bonus has been paid!')
                ->line('Candidate: ' . $this->referral->candidate->name)
                ->line('Amount: ₹' . number_format($this->referral->bonus_amount))
                ->line('Congratulations on a successful referral!')
                ->action('View Details', route('employer.referrals.index'));
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'referral_id' => $this->referral->id,
            'candidate_name' => $this->referral->candidate->name,
            'job_title' => $this->referral->job->title,
            'action' => $this->action,
            'bonus_amount' => $this->referral->bonus_amount,
        ];
    }
}
