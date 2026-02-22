<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBulkEmail implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $recipients;
    public string $subject;
    public string $content;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(array $recipients, string $subject, string $content)
    {
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->content = $content;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        foreach ($this->recipients as $recipient) {
            try {
                Mail::to($recipient['email'])
                    ->send(new \App\Mail\BulkEmail(
                        $this->subject,
                        $this->content,
                        $recipient
                    ));
            } catch (\Exception $e) {
                \Log::error("Failed to send email to {$recipient['email']}: {$e->getMessage()}");
            }
        }
    }
}
