<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendApplicationConfirmation extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $application;
    
    /**
     * Create a new notification instance.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }
    
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
        $job = $this->application->job;
        $company = $job->company;
        
        return (new MailMessage)
            ->subject('Application Submitted - ' . $job->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your application has been successfully submitted.')
            ->line('Position: ' . $job->title)
            ->line('Company: ' . $company->name)
            ->line('Application Number: ' . $this->application->application_number)
            ->line('Match Score: ' . $this->application->match_score . '%')
            ->action('View Application', url('/applications/' . $this->application->id))
            ->line('We will notify you when the employer reviews your application.')
            ->line('Good luck!');
    }
    
    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'job_id' => $this->application->job_id,
            'job_title' => $this->application->job->title,
            'company_name' => $this->application->job->company->name,
            'match_score' => $this->application->match_score,
        ];
    }
}
