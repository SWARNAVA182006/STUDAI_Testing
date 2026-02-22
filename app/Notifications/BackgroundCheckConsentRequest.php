<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BackgroundCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackgroundCheckConsentRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected BackgroundCheck $backgroundCheck;

    /**
     * Create a new notification instance.
     */
    public function __construct(BackgroundCheck $backgroundCheck)
    {
        $this->backgroundCheck = $backgroundCheck;
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
        $company = $this->backgroundCheck->company;
        $job = $this->backgroundCheck->job;
        $consentUrl = route('background-check-consent.show', $this->backgroundCheck);

        return (new MailMessage)
            ->subject('Background Check Consent Required - ' . ($company->name ?? 'StudAI Path'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('As part of the hiring process for the position of **' . ($job->title ?? 'the applied role') . '**, a background check is required.')
            ->line('**Company:** ' . ($company->name ?? 'N/A'))
            ->line('We need your consent to proceed with this check. Your privacy is important to us, and all information will be handled in accordance with applicable laws.')
            ->line('**What will be checked:**')
            ->line($this->getCheckTypesDescription())
            ->action('Review & Provide Consent', $consentUrl)
            ->line('This consent request will expire in ' . config('background-check.consent_expiry_days', 7) . ' days.')
            ->line('If you have any questions, please contact the hiring team.')
            ->salutation('Best regards, StudAI Path Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'background_check_consent',
            'background_check_id' => $this->backgroundCheck->id,
            'company_name' => $this->backgroundCheck->company->name ?? 'N/A',
            'job_title' => $this->backgroundCheck->job->title ?? 'N/A',
            'message' => 'Background check consent requested for ' . ($this->backgroundCheck->job->title ?? 'your application'),
            'action_url' => route('background-check-consent.show', $this->backgroundCheck),
        ];
    }

    /**
     * Get human-readable description of check types
     */
    protected function getCheckTypesDescription(): string
    {
        $items = $this->backgroundCheck->items ?? collect();
        
        if ($items->isEmpty()) {
            return '• Standard background verification';
        }

        $descriptions = $items->map(function ($item) {
            return match ($item->check_type) {
                'identity' => '• Identity Verification',
                'criminal' => '• Criminal History Check',
                'education' => '• Education Verification',
                'employment' => '• Employment History Verification',
                'credit' => '• Credit Check',
                'drug_test' => '• Drug Screening',
                'reference' => '• Reference Check',
                'driving' => '• Driving Record Check',
                'professional_license' => '• Professional License Verification',
                default => '• ' . ucfirst(str_replace('_', ' ', $item->check_type)),
            };
        });

        return $descriptions->implode("\n");
    }
}
