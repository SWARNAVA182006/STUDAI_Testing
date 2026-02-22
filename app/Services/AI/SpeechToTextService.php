<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AIInterviewCalculation;

class SpeechToTextService
{
    protected $apiKey;
    protected $apiUrl;
    protected $maxFileSize = 25 * 1024 * 1024; // 25MB max for Whisper API
    protected $supportedFormats = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
    protected $chunkDuration = 30; // seconds per chunk for large files

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->apiUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Transcribe audio file to text using OpenAI Whisper
     *
     * @param string $filePath Path to audio file (storage path or absolute path)
     * @param array $options Additional options (language, prompt, temperature, timestamps)
     * @return array Transcription result with text, language, duration, segments
     */
    public function transcribe(string $filePath, array $options = []): array
    {
        try {
            // Validate file
            $this->validateAudioFile($filePath);

            // Check if file needs chunking
            $fileSize = Storage::size($filePath);
            if ($fileSize > $this->maxFileSize) {
                Log::info("SpeechToText: File exceeds max size, using chunked transcription", [
                    'file' => $filePath,
                    'size' => $fileSize
                ]);
                return $this->transcribeChunked($filePath, $options);
            }

            // Prepare request
            $model = $options['model'] ?? 'whisper-1';
            $language = $options['language'] ?? null; // Auto-detect if not provided
            $prompt = $options['prompt'] ?? null; // Context for better accuracy
            $temperature = $options['temperature'] ?? 0.0; // Lower = more deterministic
            $responseFormat = $options['response_format'] ?? 'verbose_json'; // Include timestamps
            $timestampGranularities = $options['timestamp_granularities'] ?? ['word', 'segment'];

            // Get file content
            $fileContent = Storage::get($filePath);
            $fileName = basename($filePath);

            // Make API call
            $startTime = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->attach(
                'file',
                $fileContent,
                $fileName
            )->post($this->apiUrl . '/audio/transcriptions', array_filter([
                'model' => $model,
                'language' => $language,
                'prompt' => $prompt,
                'temperature' => $temperature,
                'response_format' => $responseFormat,
                'timestamp_granularities' => $timestampGranularities,
            ]));

            $duration = microtime(true) - $startTime;

            if (!$response->successful()) {
                throw new \Exception("Whisper API error: " . $response->body());
            }

            $result = $response->json();

            // Track usage
            $this->trackUsage([
                'audio_duration' => $result['duration'] ?? 0,
                'api_duration' => $duration,
                'file_size' => $fileSize,
                'model' => $model,
                'language' => $result['language'] ?? $language,
            ]);

            // Parse and return structured result
            return $this->parseTranscriptionResult($result, $options);

        } catch (\Exception $e) {
            Log::error("SpeechToText: Transcription failed", [
                'error' => $e->getMessage(),
                'file' => $filePath
            ]);

            // Fallback to Google Speech-to-Text if available
            if (config('services.google.speech_to_text.enabled')) {
                return $this->fallbackToGoogleSpeech($filePath, $options);
            }

            throw $e;
        }
    }

    /**
     * Transcribe large audio files in chunks
     *
     * @param string $filePath Path to audio file
     * @param array $options Transcription options
     * @return array Combined transcription result
     */
    public function transcribeChunked(string $filePath, array $options = []): array
    {
        try {
            // Split audio into chunks (using ffmpeg)
            $chunks = $this->splitAudioIntoChunks($filePath);

            $allSegments = [];
            $fullText = '';
            $totalDuration = 0;
            $detectedLanguage = null;

            foreach ($chunks as $index => $chunkPath) {
                Log::info("SpeechToText: Transcribing chunk", [
                    'chunk' => $index + 1,
                    'total' => count($chunks)
                ]);

                // Transcribe chunk
                $chunkResult = $this->transcribe($chunkPath, $options);

                // Append results
                $fullText .= ' ' . $chunkResult['text'];
                
                if (isset($chunkResult['segments'])) {
                    // Adjust timestamps for chunk offset
                    $offset = $index * $this->chunkDuration;
                    foreach ($chunkResult['segments'] as $segment) {
                        $segment['start'] += $offset;
                        $segment['end'] += $offset;
                        $allSegments[] = $segment;
                    }
                }

                $totalDuration += $chunkResult['duration'] ?? 0;
                $detectedLanguage = $detectedLanguage ?? $chunkResult['language'];

                // Clean up chunk file
                Storage::delete($chunkPath);
            }

            return [
                'text' => trim($fullText),
                'language' => $detectedLanguage,
                'duration' => $totalDuration,
                'segments' => $allSegments,
                'words' => $this->extractWordsFromSegments($allSegments),
                'chunked' => true,
                'chunk_count' => count($chunks),
            ];

        } catch (\Exception $e) {
            Log::error("SpeechToText: Chunked transcription failed", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Detect language of audio file
     *
     * @param string $filePath Path to audio file
     * @return string Detected language code (e.g., 'en', 'es', 'fr')
     */
    public function detectLanguage(string $filePath): string
    {
        $cacheKey = 'speech_language_' . md5($filePath);

        return Cache::remember($cacheKey, 86400, function () use ($filePath) {
            // Transcribe first 30 seconds to detect language
            $result = $this->transcribe($filePath, [
                'language' => null, // Auto-detect
                'response_format' => 'verbose_json'
            ]);

            return $result['language'] ?? 'en';
        });
    }

    /**
     * Add word-level timestamps to transcription
     *
     * @param array $transcription Transcription result
     * @return array Transcription with word timestamps
     */
    public function addWordTimestamps(array $transcription): array
    {
        if (!isset($transcription['words']) || empty($transcription['words'])) {
            // Extract words from segments
            $transcription['words'] = $this->extractWordsFromSegments(
                $transcription['segments'] ?? []
            );
        }

        return $transcription;
    }

    /**
     * Calculate speech pace (words per minute)
     *
     * @param array $transcription Transcription result
     * @return float Words per minute
     */
    public function calculateSpeechPace(array $transcription): float
    {
        $wordCount = str_word_count($transcription['text'] ?? '');
        $duration = $transcription['duration'] ?? 1;
        
        return ($wordCount / $duration) * 60; // Convert to WPM
    }

    /**
     * Detect pauses and hesitations in speech
     *
     * @param array $transcription Transcription with timestamps
     * @return array Pauses with start time, duration, context
     */
    public function detectPauses(array $transcription): array
    {
        $pauses = [];
        $segments = $transcription['segments'] ?? [];

        for ($i = 0; $i < count($segments) - 1; $i++) {
            $currentEnd = $segments[$i]['end'] ?? 0;
            $nextStart = $segments[$i + 1]['start'] ?? 0;
            $gap = $nextStart - $currentEnd;

            // Significant pause (>1 second)
            if ($gap > 1.0) {
                $pauses[] = [
                    'start' => $currentEnd,
                    'duration' => $gap,
                    'before' => $segments[$i]['text'] ?? '',
                    'after' => $segments[$i + 1]['text'] ?? '',
                    'severity' => $gap > 3 ? 'high' : ($gap > 2 ? 'medium' : 'low')
                ];
            }
        }

        return $pauses;
    }

    /**
     * Extract key phrases and keywords from transcription
     *
     * @param array $transcription Transcription result
     * @return array Keywords with frequency and importance
     */
    public function extractKeywords(array $transcription): array
    {
        $text = $transcription['text'] ?? '';
        
        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'up', 'about', 'into', 'through', 'during', 'is', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they'];
        
        // Extract words
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 3;
        });

        // Count frequencies
        $frequencies = array_count_values($words);
        arsort($frequencies);

        // Return top keywords
        return array_slice($frequencies, 0, 20, true);
    }

    /**
     * Validate audio file
     *
     * @param string $filePath Path to audio file
     * @throws \Exception If file is invalid
     */
    protected function validateAudioFile(string $filePath): void
    {
        if (!Storage::exists($filePath)) {
            throw new \Exception("Audio file not found: {$filePath}");
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), $this->supportedFormats)) {
            throw new \Exception("Unsupported audio format: {$extension}. Supported: " . implode(', ', $this->supportedFormats));
        }

        $fileSize = Storage::size($filePath);
        if ($fileSize === 0) {
            throw new \Exception("Audio file is empty");
        }
    }

    /**
     * Split audio into chunks using ffmpeg
     *
     * @param string $filePath Path to audio file
     * @return array Array of chunk file paths
     */
    protected function splitAudioIntoChunks(string $filePath): array
    {
        // This is a placeholder - actual implementation would use ffmpeg
        // For now, return single chunk
        Log::warning("SpeechToText: Audio chunking not fully implemented, processing as single file");
        
        // In production, use something like:
        // exec("ffmpeg -i {$filePath} -f segment -segment_time {$this->chunkDuration} output%03d.mp3");
        
        return [$filePath];
    }

    /**
     * Parse Whisper API response into structured format
     *
     * @param array $result API response
     * @param array $options Original options
     * @return array Structured transcription result
     */
    protected function parseTranscriptionResult(array $result, array $options): array
    {
        return [
            'text' => $result['text'] ?? '',
            'language' => $result['language'] ?? null,
            'duration' => $result['duration'] ?? 0,
            'segments' => $result['segments'] ?? [],
            'words' => $result['words'] ?? $this->extractWordsFromSegments($result['segments'] ?? []),
            'task' => $result['task'] ?? 'transcribe',
            'model' => $options['model'] ?? 'whisper-1',
            'timestamp_generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Extract words from segments
     *
     * @param array $segments Transcription segments
     * @return array Words with timestamps
     */
    protected function extractWordsFromSegments(array $segments): array
    {
        $words = [];

        foreach ($segments as $segment) {
            $text = $segment['text'] ?? '';
            $start = $segment['start'] ?? 0;
            $end = $segment['end'] ?? 0;
            $duration = $end - $start;

            // Split segment into words
            $segmentWords = explode(' ', trim($text));
            $wordCount = count($segmentWords);
            
            if ($wordCount > 0) {
                $timePerWord = $duration / $wordCount;

                foreach ($segmentWords as $index => $word) {
                    if (empty(trim($word))) continue;

                    $words[] = [
                        'word' => $word,
                        'start' => $start + ($index * $timePerWord),
                        'end' => $start + (($index + 1) * $timePerWord),
                        'confidence' => $segment['confidence'] ?? null,
                    ];
                }
            }
        }

        return $words;
    }

    /**
     * Fallback to Google Speech-to-Text
     *
     * @param string $filePath Path to audio file
     * @param array $options Transcription options
     * @return array Transcription result
     */
    protected function fallbackToGoogleSpeech(string $filePath, array $options): array
    {
        Log::warning("SpeechToText: Falling back to Google Speech-to-Text");

        // Placeholder for Google Speech-to-Text integration
        // In production, implement Google Cloud Speech-to-Text API
        
        return [
            'text' => '[Transcription failed - please try again or type your answer]',
            'language' => 'en',
            'duration' => 0,
            'segments' => [],
            'words' => [],
            'error' => 'Whisper API unavailable, Google fallback not configured',
        ];
    }

    /**
     * Track API usage and costs
     *
     * @param array $data Usage data
     */
    protected function trackUsage(array $data): void
    {
        try {
            $user = auth()->user();
            if (!$user) return;

            // Whisper pricing: $0.006 per minute
            $minutes = ceil($data['audio_duration'] / 60);
            $cost = $minutes * 0.006;

            AIInterviewCalculation::create([
                'user_id' => $user->id,
                'operation_type' => 'speech_to_text',
                'model_used' => $data['model'] ?? 'whisper-1',
                'audio_duration_seconds' => $data['audio_duration'] ?? 0,
                'api_response_time' => $data['api_duration'] ?? 0,
                'file_size_bytes' => $data['file_size'] ?? 0,
                'detected_language' => $data['language'] ?? null,
                'estimated_cost' => $cost,
                'metadata' => json_encode($data),
            ]);

            // Update user subscription AI credits if applicable
            if ($user->subscription) {
                $user->subscription->increment('ai_credits_used_this_month', $minutes);
            }

        } catch (\Exception $e) {
            Log::error("SpeechToText: Failed to track usage", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get transcription cost estimate
     *
     * @param float $durationSeconds Audio duration in seconds
     * @return float Estimated cost in USD
     */
    public function estimateCost(float $durationSeconds): float
    {
        $minutes = ceil($durationSeconds / 60);
        return $minutes * 0.006; // $0.006 per minute
    }

    /**
     * Check if user has sufficient AI credits for transcription
     *
     * @param float $durationSeconds Audio duration in seconds
     * @return bool True if user has sufficient credits
     */
    public function hasCreditsForTranscription(float $durationSeconds): bool
    {
        $user = auth()->user();
        if (!$user || !$user->subscription) {
            return false; // Free users don't get transcription
        }

        $requiredCredits = ceil($durationSeconds / 60);
        $remainingCredits = $user->getRemainingAICredits();

        return $remainingCredits >= $requiredCredits;
    }

    /**
     * Get supported audio formats
     *
     * @return array List of supported file extensions
     */
    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }

    /**
     * Get maximum file size
     *
     * @return int Max file size in bytes
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }
}
