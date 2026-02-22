<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateUserDataExport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public int $tries = 2;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->onQueue('low'); // Non-critical, can wait
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        // Gather user data
        $data = [
            'user' => $this->user->toArray(),
            'profile' => $this->user->profile?->toArray(),
            'applications' => $this->user->applications()->with('job')->get()->toArray(),
            'saved_jobs' => $this->user->savedJobs()->get()->toArray(),
            'interviews' => $this->user->interviews()->get()->toArray(),
            'assessments' => $this->user->assessments()->get()->toArray(),
            'subscription' => $this->user->subscription?->toArray(),
        ];

        // Generate JSON file
        $filename = "user-data-export-{$this->user->id}-" . now()->format('Y-m-d') . ".json";
        Storage::put("exports/{$filename}", json_encode($data, JSON_PRETTY_PRINT));

        // Notify user
        $this->user->notify(new \App\Notifications\DataExportReady($filename));

        \Log::info("Generated data export for user {$this->user->id}: {$filename}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error("Failed to generate data export for user {$this->user->id}: {$exception->getMessage()}");
        
        // Notify user of failure
        $this->user->notify(new \App\Notifications\DataExportFailed());
    }
}
