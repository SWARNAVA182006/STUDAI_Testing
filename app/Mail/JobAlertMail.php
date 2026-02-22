<?php

namespace App\Mail;

use App\Models\JobAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class JobAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $jobs;
    public $alert;
    public $totalMatches;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $jobs, JobAlert $alert, int $totalMatches)
    {
        $this->jobs = $jobs;
        $this->alert = $alert;
        $this->totalMatches = $totalMatches;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->totalMatches === 1
            ? "1 new job match for your alert: {$this->alert->name}"
            : "{$this->totalMatches} new job matches for your alert: {$this->alert->name}";
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.job-alert',
            with: [
                'jobs' => $this->jobs,
                'alert' => $this->alert,
                'totalMatches' => $this->totalMatches,
                'showingTop' => min(10, $this->totalMatches),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
