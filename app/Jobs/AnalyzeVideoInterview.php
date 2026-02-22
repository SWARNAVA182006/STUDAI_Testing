<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\VideoInterviewRecording;
use App\Services\VideoAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeVideoInterview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 60;

    public function __construct(
        public VideoInterviewRecording $recording
    ) {}

    public function handle(VideoAnalysisService $analysisService): void
    {
        Log::info('Starting video analysis job', ['recording_id' => $this->recording->id]);

        try {
            // Ensure transcription is available
            if (empty($this->recording->transcription_text)) {
                Log::info('Transcription not ready, releasing job', [
                    'recording_id' => $this->recording->id,
                ]);
                $this->release(120);
                return;
            }

            $analysis = $analysisService->analyzeRecording($this->recording);

            Log::info('Video analysis job completed', [
                'recording_id' => $this->recording->id,
                'analysis_id' => $analysis->id,
                'overall_score' => $analysis->overall_score,
            ]);

        } catch (\Exception $e) {
            Log::error('Video analysis job failed', [
                'recording_id' => $this->recording->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeVideoInterview job failed permanently', [
            'recording_id' => $this->recording->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
