<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\VideoInterviewRecording;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVideoRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 60;

    public function __construct(
        public VideoInterviewRecording $recording
    ) {}

    public function handle(): void
    {
        Log::info('Processing video recording', ['recording_id' => $this->recording->id]);

        try {
            $this->recording->update(['status' => VideoInterviewRecording::STATUS_PROCESSING]);

            // Verify file exists
            $disk = Storage::disk($this->recording->storage_disk);
            if (!$disk->exists($this->recording->file_path)) {
                throw new \Exception('Video file not found');
            }

            // Get file size if not set
            if (!$this->recording->file_size) {
                $this->recording->update([
                    'file_size' => $disk->size($this->recording->file_path),
                ]);
            }

            // Generate thumbnail
            $this->generateThumbnail();

            // Calculate duration
            $this->calculateDuration();

            $this->recording->markProcessed();

            Log::info('Video recording processed successfully', [
                'recording_id' => $this->recording->id,
                'duration' => $this->recording->duration_seconds,
            ]);

        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'recording_id' => $this->recording->id,
                'error' => $e->getMessage(),
            ]);

            $this->recording->markFailed($e->getMessage());
            throw $e;
        }
    }

    protected function generateThumbnail(): void
    {
        $disk = Storage::disk($this->recording->storage_disk);
        $thumbnailPath = str_replace(
            ['.webm', '.mp4'],
            '_thumb.jpg',
            $this->recording->file_path
        );

        // Download to temp location
        $tempVideo = tempnam(sys_get_temp_dir(), 'video_') . '.webm';
        $tempThumb = tempnam(sys_get_temp_dir(), 'thumb_') . '.jpg';

        file_put_contents($tempVideo, $disk->get($this->recording->file_path));

        // Use FFmpeg to extract thumbnail at 2 seconds
        $command = sprintf(
            'ffmpeg -i %s -ss 00:00:02 -vframes 1 -vf scale=480:-1 %s 2>&1',
            escapeshellarg($tempVideo),
            escapeshellarg($tempThumb)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($tempThumb)) {
            $disk->put($thumbnailPath, file_get_contents($tempThumb));
            $this->recording->update(['thumbnail_path' => $thumbnailPath]);
        }

        @unlink($tempVideo);
        @unlink($tempThumb);
    }

    protected function calculateDuration(): void
    {
        $disk = Storage::disk($this->recording->storage_disk);
        
        $tempVideo = tempnam(sys_get_temp_dir(), 'video_') . '.webm';
        file_put_contents($tempVideo, $disk->get($this->recording->file_path));

        // Use FFprobe to get duration
        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($tempVideo)
        );

        $output = [];
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && !empty($output[0])) {
            $duration = (int) round((float) $output[0]);
            $this->recording->update(['duration_seconds' => $duration]);
        }

        @unlink($tempVideo);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessVideoRecording job failed permanently', [
            'recording_id' => $this->recording->id,
            'error' => $exception->getMessage(),
        ]);

        $this->recording->markFailed($exception->getMessage());
    }
}
