<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VideoInterviewRecording;
use App\Models\VideoInterviewAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for transcribing video/audio recordings
 */
class TranscriptionService
{
    protected string $provider;
    protected string $azureEndpoint;
    protected string $azureKey;
    protected string $openAiKey;

    public function __construct()
    {
        $this->provider = config('video-interview.transcription_provider', 'azure');
        $this->azureEndpoint = config('services.azure.speech_endpoint', '');
        $this->azureKey = config('services.azure.speech_key', '');
        $this->openAiKey = config('services.openai.api_key', '');
    }

    /**
     * Transcribe a video recording
     */
    public function transcribe(VideoInterviewRecording $recording): string
    {
        Log::info('Starting transcription', [
            'recording_id' => $recording->id,
            'provider' => $this->provider,
        ]);

        try {
            $transcription = match ($this->provider) {
                'azure' => $this->transcribeWithAzure($recording),
                'openai', 'whisper' => $this->transcribeWithWhisper($recording),
                default => $this->transcribeWithWhisper($recording),
            };

            $recording->update([
                'transcription_text' => $transcription,
                'transcription_status' => 'completed',
                'transcribed_at' => now(),
            ]);

            Log::info('Transcription completed', [
                'recording_id' => $recording->id,
                'word_count' => str_word_count($transcription),
            ]);

            return $transcription;
        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            $recording->update([
                'transcription_status' => 'failed',
                'transcription_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Transcribe using Azure Speech Services
     */
    protected function transcribeWithAzure(VideoInterviewRecording $recording): string
    {
        $audioPath = $this->extractAudio($recording);

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->azureKey,
            'Content-Type' => 'audio/wav',
        ])->attach(
            'audio',
            Storage::disk($recording->storage_disk)->get($audioPath),
            'audio.wav'
        )->post("{$this->azureEndpoint}/speechtotext/v3.1/transcriptions:recognize", [
            'locales' => ['en-US'],
            'profanityFilterMode' => 'Masked',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Azure transcription failed: ' . $response->body());
        }

        $result = $response->json();
        return $result['combinedRecognizedPhrases'][0]['display'] ?? '';
    }

    /**
     * Transcribe using OpenAI Whisper
     */
    protected function transcribeWithWhisper(VideoInterviewRecording $recording): string
    {
        $audioPath = $this->extractAudio($recording);
        $audioContent = Storage::disk($recording->storage_disk)->get($audioPath);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openAiKey,
        ])->attach(
            'file',
            $audioContent,
            'audio.mp3'
        )->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'en',
            'response_format' => 'json',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Whisper transcription failed: ' . $response->body());
        }

        return $response->json('text') ?? '';
    }

    /**
     * Extract audio from video file
     */
    protected function extractAudio(VideoInterviewRecording $recording): string
    {
        // Check if audio already extracted
        if ($recording->audio_path) {
            return $recording->audio_path;
        }

        $videoPath = $recording->file_path;
        $audioPath = str_replace(['.webm', '.mp4'], '.mp3', $videoPath);

        // In production, use FFmpeg to extract audio
        // For now, we'll try to use the video file directly if it's audio
        $mimeType = $recording->mime_type ?? 'video/webm';
        if (str_starts_with($mimeType, 'audio/')) {
            return $videoPath;
        }

        // Use FFmpeg via shell or a PHP FFmpeg library
        $this->extractAudioWithFFmpeg($recording, $audioPath);

        $recording->update(['audio_path' => $audioPath]);

        return $audioPath;
    }

    /**
     * Extract audio using FFmpeg
     */
    protected function extractAudioWithFFmpeg(VideoInterviewRecording $recording, string $outputPath): void
    {
        $disk = Storage::disk($recording->storage_disk);
        
        // Download video to temp location
        $tempVideo = tempnam(sys_get_temp_dir(), 'video_') . '.webm';
        $tempAudio = tempnam(sys_get_temp_dir(), 'audio_') . '.mp3';
        
        file_put_contents($tempVideo, $disk->get($recording->file_path));

        // Run FFmpeg
        $command = sprintf(
            'ffmpeg -i %s -vn -acodec libmp3lame -ar 16000 -ac 1 -ab 128k %s 2>&1',
            escapeshellarg($tempVideo),
            escapeshellarg($tempAudio)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            @unlink($tempVideo);
            @unlink($tempAudio);
            throw new \Exception('FFmpeg audio extraction failed: ' . implode("\n", $output));
        }

        // Upload extracted audio
        $disk->put($outputPath, file_get_contents($tempAudio));

        // Cleanup
        @unlink($tempVideo);
        @unlink($tempAudio);
    }

    /**
     * Get transcription status for a recording
     */
    public function getStatus(VideoInterviewRecording $recording): array
    {
        return [
            'recording_id' => $recording->id,
            'status' => $recording->transcription_status ?? 'pending',
            'transcribed_at' => $recording->transcribed_at,
            'has_transcription' => !empty($recording->transcription_text),
            'word_count' => $recording->transcription_text 
                ? str_word_count($recording->transcription_text) 
                : 0,
        ];
    }

    /**
     * Retry failed transcription
     */
    public function retry(VideoInterviewRecording $recording): string
    {
        $recording->update([
            'transcription_status' => 'pending',
            'transcription_error' => null,
        ]);

        return $this->transcribe($recording);
    }
}
