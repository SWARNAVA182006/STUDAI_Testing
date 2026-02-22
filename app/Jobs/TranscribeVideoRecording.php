<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\VideoInterviewRecording;
use App\Services\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranscribeVideoRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public int $backoff = 120;

    public function __construct(
        public VideoInterviewRecording $recording
    ) {}

    public function handle(TranscriptionService $transcriptionService): void
    {
        Log::info('Starting transcription job', ['recording_id' => $this->recording->id]);

        try {
            // Wait for processing to complete
            if ($this->recording->status !== VideoInterviewRecording::STATUS_PROCESSED) {
                Log::info('Recording not yet processed, releasing job', [
                    'recording_id' => $this->recording->id,
                    'status' => $this->recording->status,
                ]);
                $this->release(60);
                return;
            }

            $transcription = $transcriptionService->transcribe($this->recording);

            Log::info('Transcription job completed', [
                'recording_id' => $this->recording->id,
                'word_count' => str_word_count($transcription),
            ]);

            // Dispatch analysis job
            AnalyzeVideoInterview::dispatch($this->recording)->delay(now()->addSeconds(10));

        } catch (\Exception $e) {
            Log::error('Transcription job failed', [
                'recording_id' => $this->recording->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TranscribeVideoRecording job failed permanently', [
            'recording_id' => $this->recording->id,
            'error' => $exception->getMessage(),
        ]);

        $this->recording->update([
            'transcription_status' => 'failed',
            'transcription_error' => $exception->getMessage(),
        ]);
    }
}
