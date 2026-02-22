<?php

namespace App\Jobs;

use App\Models\Job;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateJobEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Job $job;
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        try {
            // Generate content for embedding
            $content = $this->job->title . ' ' . 
                      $this->job->description . ' ' . 
                      implode(' ', $this->job->required_skills ?? []);

            // Generate embeddings
            $embeddings = $aiService->generateEmbeddings($content);

            // Store embeddings
            $this->job->update([
                'embeddings' => $embeddings,
            ]);

            Log::info("Generated embeddings for job {$this->job->id}");

        } catch (\Exception $e) {
            Log::error("Failed to generate embeddings for job {$this->job->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job embedding generation permanently failed for job {$this->job->id}: {$exception->getMessage()}");
    }
}
