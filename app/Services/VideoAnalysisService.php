<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VideoInterviewRecording;
use App\Models\VideoInterviewAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for AI-powered video interview analysis
 * 
 * Analyzes video recordings for:
 * - Body language (posture, gestures, eye contact)
 * - Speech patterns (pace, filler words, clarity)
 * - Content quality (relevance, structure, keywords)
 * - Emotional indicators (confidence, enthusiasm)
 */
class VideoAnalysisService
{
    protected AIService $aiService;
    protected TranscriptionService $transcriptionService;

    public function __construct(AIService $aiService, TranscriptionService $transcriptionService)
    {
        $this->aiService = $aiService;
        $this->transcriptionService = $transcriptionService;
    }

    /**
     * Perform full analysis on a video recording
     */
    public function analyzeRecording(VideoInterviewRecording $recording): VideoInterviewAnalysis
    {
        Log::info('Starting video analysis', ['recording_id' => $recording->id]);

        // Get or create transcription
        $transcription = $recording->transcription_text;
        if (!$transcription) {
            $transcription = $this->transcriptionService->transcribe($recording);
            $recording->update(['transcription_text' => $transcription]);
        }

        // Perform content analysis using AI
        $contentAnalysis = $this->analyzeContent($recording, $transcription);

        // Analyze speech patterns
        $speechAnalysis = $this->analyzeSpeech($transcription);

        // Get video metrics (would be from client-side TensorFlow.js or video processing)
        $visualAnalysis = $this->getVisualAnalysisMetrics($recording);

        // Calculate composite scores
        $scores = $this->calculateScores($contentAnalysis, $speechAnalysis, $visualAnalysis);

        // Create or update analysis record
        $analysis = VideoInterviewAnalysis::updateOrCreate(
            ['video_interview_recording_id' => $recording->id],
            [
                'status' => VideoInterviewAnalysis::STATUS_COMPLETED,
                'analyzed_at' => now(),
                
                // Overall scores
                'overall_score' => $scores['overall'],
                'content_score' => $scores['content'],
                'confidence_score' => $scores['confidence'],
                'clarity_score' => $scores['clarity'],
                
                // Body language
                'eye_contact_score' => $visualAnalysis['eye_contact'] ?? null,
                'posture_score' => $visualAnalysis['posture'] ?? null,
                'gesture_score' => $visualAnalysis['gestures'] ?? null,
                
                // Speech analysis
                'speech_pace_wpm' => $speechAnalysis['words_per_minute'],
                'filler_word_count' => $speechAnalysis['filler_count'],
                'filler_words_detected' => $speechAnalysis['filler_words'],
                'pause_analysis' => $speechAnalysis['pauses'],
                
                // Content analysis
                'keywords_matched' => $contentAnalysis['keywords_matched'] ?? [],
                'structure_score' => $contentAnalysis['structure_score'] ?? null,
                'relevance_score' => $contentAnalysis['relevance_score'] ?? null,
                
                // AI feedback
                'ai_feedback' => $contentAnalysis['feedback'],
                'strengths' => $contentAnalysis['strengths'] ?? [],
                'improvements' => $contentAnalysis['improvements'] ?? [],
                'detailed_analysis' => [
                    'content' => $contentAnalysis,
                    'speech' => $speechAnalysis,
                    'visual' => $visualAnalysis,
                ],
            ]
        );

        $recording->markAnalyzed();

        Log::info('Video analysis completed', [
            'recording_id' => $recording->id,
            'analysis_id' => $analysis->id,
            'overall_score' => $scores['overall'],
        ]);

        return $analysis;
    }

    /**
     * Analyze content using AI
     */
    protected function analyzeContent(VideoInterviewRecording $recording, string $transcription): array
    {
        $question = $recording->question;
        $questionText = $question?->question_text ?? 'General interview response';
        $expectedElements = $question?->expected_elements ?? [];
        $keywordsToLookFor = $question?->keywords_to_look_for ?? [];

        $prompt = $this->buildContentAnalysisPrompt(
            transcription: $transcription,
            question: $questionText,
            expectedElements: $expectedElements,
            keywords: $keywordsToLookFor
        );

        try {
            $response = $this->aiService->chat([
                ['role' => 'system', 'content' => $this->getContentAnalysisSystemPrompt()],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3,
            ]);

            $analysis = json_decode($response, true);

            return [
                'relevance_score' => $analysis['relevance_score'] ?? 70,
                'structure_score' => $analysis['structure_score'] ?? 70,
                'depth_score' => $analysis['depth_score'] ?? 70,
                'keywords_matched' => $analysis['keywords_matched'] ?? [],
                'feedback' => $analysis['feedback'] ?? '',
                'strengths' => $analysis['strengths'] ?? [],
                'improvements' => $analysis['improvements'] ?? [],
                'key_points' => $analysis['key_points'] ?? [],
                'missing_elements' => $analysis['missing_elements'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Content analysis failed', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getDefaultContentAnalysis();
        }
    }

    /**
     * Analyze speech patterns from transcription
     */
    protected function analyzeSpeech(string $transcription): array
    {
        // Common filler words to detect
        $fillerWords = [
            'um', 'uh', 'like', 'you know', 'basically', 'actually',
            'literally', 'honestly', 'right', 'so', 'well', 'i mean',
            'kind of', 'sort of', 'stuff like that', 'and stuff',
        ];

        $lowercaseTranscription = strtolower($transcription);
        $wordCount = str_word_count($transcription);
        
        // Count filler words
        $detectedFillers = [];
        $totalFillerCount = 0;

        foreach ($fillerWords as $filler) {
            $count = substr_count($lowercaseTranscription, $filler);
            if ($count > 0) {
                $detectedFillers[$filler] = $count;
                $totalFillerCount += $count;
            }
        }

        // Estimate speech rate (assuming average response time)
        // In real implementation, this would come from audio analysis
        $estimatedDurationSeconds = max(1, $wordCount / 2.5); // ~150 wpm average
        $wordsPerMinute = (int) round(($wordCount / $estimatedDurationSeconds) * 60);

        // Analyze pauses (would come from audio analysis in production)
        $pauses = $this->estimatePauses($transcription);

        // Calculate clarity score based on filler word ratio
        $fillerRatio = $wordCount > 0 ? $totalFillerCount / $wordCount : 0;
        $clarityScore = max(0, min(100, 100 - ($fillerRatio * 500)));

        // Evaluate speech pace
        $paceEvaluation = match (true) {
            $wordsPerMinute < 100 => 'too_slow',
            $wordsPerMinute > 180 => 'too_fast',
            $wordsPerMinute >= 130 && $wordsPerMinute <= 160 => 'optimal',
            default => 'acceptable',
        };

        return [
            'word_count' => $wordCount,
            'words_per_minute' => $wordsPerMinute,
            'pace_evaluation' => $paceEvaluation,
            'filler_count' => $totalFillerCount,
            'filler_words' => $detectedFillers,
            'filler_ratio' => round($fillerRatio * 100, 2),
            'clarity_score' => round($clarityScore, 1),
            'pauses' => $pauses,
            'sentence_count' => preg_match_all('/[.!?]+/', $transcription),
        ];
    }

    /**
     * Get visual analysis metrics
     * In production, these would come from:
     * - Client-side TensorFlow.js analysis
     * - Server-side video processing
     * - Real-time WebRTC analysis
     */
    protected function getVisualAnalysisMetrics(VideoInterviewRecording $recording): array
    {
        // Check if client-side analysis was provided
        $clientAnalysis = $recording->client_side_analysis;
        if ($clientAnalysis) {
            return [
                'eye_contact' => $clientAnalysis['eye_contact_score'] ?? null,
                'posture' => $clientAnalysis['posture_score'] ?? null,
                'gestures' => $clientAnalysis['gesture_score'] ?? null,
                'facial_expressions' => $clientAnalysis['expression_data'] ?? [],
                'head_movement' => $clientAnalysis['head_movement'] ?? [],
                'source' => 'client_analysis',
            ];
        }

        // For now, return placeholder values
        // In production, we would process the video file
        return [
            'eye_contact' => null,
            'posture' => null,
            'gestures' => null,
            'facial_expressions' => [],
            'source' => 'pending',
            'note' => 'Visual analysis pending - client-side analysis not provided',
        ];
    }

    /**
     * Calculate composite scores
     */
    protected function calculateScores(array $content, array $speech, array $visual): array
    {
        // Content score (40% weight)
        $contentScore = $this->weightedAverage([
            'relevance' => ['value' => $content['relevance_score'] ?? 70, 'weight' => 0.4],
            'structure' => ['value' => $content['structure_score'] ?? 70, 'weight' => 0.3],
            'depth' => ['value' => $content['depth_score'] ?? 70, 'weight' => 0.3],
        ]);

        // Delivery score (30% weight)
        $deliveryScore = $this->calculateDeliveryScore($speech, $visual);

        // Clarity score (15% weight)
        $clarityScore = $speech['clarity_score'] ?? 70;

        // Confidence score (15% weight) - based on speech and visual cues
        $confidenceScore = $this->calculateConfidenceScore($speech, $visual);

        // Overall score
        $overallScore = $this->weightedAverage([
            'content' => ['value' => $contentScore, 'weight' => 0.40],
            'delivery' => ['value' => $deliveryScore, 'weight' => 0.30],
            'clarity' => ['value' => $clarityScore, 'weight' => 0.15],
            'confidence' => ['value' => $confidenceScore, 'weight' => 0.15],
        ]);

        return [
            'overall' => round($overallScore, 1),
            'content' => round($contentScore, 1),
            'delivery' => round($deliveryScore, 1),
            'clarity' => round($clarityScore, 1),
            'confidence' => round($confidenceScore, 1),
        ];
    }

    /**
     * Calculate delivery score
     */
    protected function calculateDeliveryScore(array $speech, array $visual): float
    {
        $scores = [];
        
        // Speech pace score
        $pace = $speech['words_per_minute'] ?? 140;
        $scores['pace'] = match (true) {
            $pace < 100 || $pace > 200 => 50,
            $pace < 120 || $pace > 180 => 70,
            $pace >= 130 && $pace <= 160 => 100,
            default => 85,
        };

        // Filler word penalty
        $fillerRatio = $speech['filler_ratio'] ?? 0;
        $scores['fluency'] = max(0, 100 - ($fillerRatio * 3));

        // Visual scores if available
        if (isset($visual['eye_contact']) && $visual['eye_contact'] !== null) {
            $scores['eye_contact'] = $visual['eye_contact'];
        }
        if (isset($visual['posture']) && $visual['posture'] !== null) {
            $scores['posture'] = $visual['posture'];
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 70;
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidenceScore(array $speech, array $visual): float
    {
        $indicators = [];

        // Steady speech pace indicates confidence
        $paceVariability = $speech['pace_variability'] ?? 15;
        $indicators[] = max(0, 100 - $paceVariability);

        // Fewer filler words indicate confidence
        $fillerRatio = $speech['filler_ratio'] ?? 0;
        $indicators[] = max(0, 100 - ($fillerRatio * 5));

        // Eye contact indicates confidence
        if (isset($visual['eye_contact']) && $visual['eye_contact'] !== null) {
            $indicators[] = $visual['eye_contact'];
        }

        // Good posture indicates confidence
        if (isset($visual['posture']) && $visual['posture'] !== null) {
            $indicators[] = $visual['posture'];
        }

        return count($indicators) > 0 ? array_sum($indicators) / count($indicators) : 70;
    }

    /**
     * Calculate weighted average
     */
    protected function weightedAverage(array $items): float
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($items as $item) {
            $weightedSum += $item['value'] * $item['weight'];
            $totalWeight += $item['weight'];
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Estimate pauses from transcription
     */
    protected function estimatePauses(string $transcription): array
    {
        // Count sentence boundaries as potential pauses
        preg_match_all('/[.!?]+/', $transcription, $matches);
        $sentenceCount = count($matches[0]);

        // Estimate based on typical speech patterns
        return [
            'estimated_count' => $sentenceCount,
            'type' => 'estimated',
            'note' => 'Pause analysis based on sentence structure',
        ];
    }

    /**
     * Get content analysis system prompt
     */
    protected function getContentAnalysisSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert interview coach analyzing a candidate's video interview response.
Evaluate the response objectively and provide constructive feedback.

Return a JSON object with:
{
    "relevance_score": (0-100) How well the response addresses the question,
    "structure_score": (0-100) How well-organized the response is,
    "depth_score": (0-100) The depth and quality of the content,
    "keywords_matched": ["list", "of", "key", "terms", "used"],
    "feedback": "2-3 sentence overall assessment",
    "strengths": ["strength 1", "strength 2", "strength 3"],
    "improvements": ["improvement 1", "improvement 2"],
    "key_points": ["main point 1", "main point 2"],
    "missing_elements": ["element that could have been included"]
}

Be encouraging but honest. Focus on actionable improvements.
PROMPT;
    }

    /**
     * Build content analysis prompt
     */
    protected function buildContentAnalysisPrompt(
        string $transcription,
        string $question,
        array $expectedElements,
        array $keywords
    ): string {
        $prompt = "Interview Question: {$question}\n\n";
        $prompt .= "Candidate's Response (Transcription):\n\"{$transcription}\"\n\n";

        if (!empty($expectedElements)) {
            $prompt .= "Expected elements in a strong answer:\n";
            foreach ($expectedElements as $element) {
                $prompt .= "- {$element}\n";
            }
            $prompt .= "\n";
        }

        if (!empty($keywords)) {
            $prompt .= "Keywords to look for: " . implode(', ', $keywords) . "\n\n";
        }

        $prompt .= "Analyze this response and provide your assessment.";

        return $prompt;
    }

    /**
     * Get default content analysis when AI fails
     */
    protected function getDefaultContentAnalysis(): array
    {
        return [
            'relevance_score' => 70,
            'structure_score' => 70,
            'depth_score' => 70,
            'keywords_matched' => [],
            'feedback' => 'Analysis could not be completed. Please try again later.',
            'strengths' => ['Response recorded successfully'],
            'improvements' => ['Ensure clear audio and video quality'],
            'key_points' => [],
            'missing_elements' => [],
        ];
    }

    /**
     * Get analysis tips based on scores
     */
    public function getImprovementTips(VideoInterviewAnalysis $analysis): array
    {
        $tips = [];

        // Speech pace tips
        if ($analysis->speech_pace_wpm) {
            if ($analysis->speech_pace_wpm < 120) {
                $tips[] = [
                    'category' => 'Speech Pace',
                    'tip' => 'Try to speak a bit faster. Aim for 130-160 words per minute for optimal engagement.',
                    'priority' => 'medium',
                ];
            } elseif ($analysis->speech_pace_wpm > 180) {
                $tips[] = [
                    'category' => 'Speech Pace',
                    'tip' => 'Consider slowing down slightly. Speaking too fast can make it hard to follow.',
                    'priority' => 'medium',
                ];
            }
        }

        // Filler words tips
        if ($analysis->filler_word_count && $analysis->filler_word_count > 5) {
            $mostUsed = collect($analysis->filler_words_detected)->sortDesc()->take(2)->keys()->toArray();
            $tips[] = [
                'category' => 'Filler Words',
                'tip' => sprintf(
                    'You used filler words %d times. Try to reduce usage of "%s". Practice pausing instead of using fillers.',
                    $analysis->filler_word_count,
                    implode('" and "', $mostUsed)
                ),
                'priority' => 'high',
            ];
        }

        // Eye contact tips
        if ($analysis->eye_contact_score && $analysis->eye_contact_score < 70) {
            $tips[] = [
                'category' => 'Eye Contact',
                'tip' => 'Try to look at the camera more consistently. This simulates eye contact with the interviewer.',
                'priority' => 'high',
            ];
        }

        // Content tips
        if ($analysis->content_score && $analysis->content_score < 75) {
            $tips[] = [
                'category' => 'Content',
                'tip' => 'Structure your answers using the STAR method (Situation, Task, Action, Result) for behavioral questions.',
                'priority' => 'high',
            ];
        }

        // Confidence tips
        if ($analysis->confidence_score && $analysis->confidence_score < 70) {
            $tips[] = [
                'category' => 'Confidence',
                'tip' => 'Practice your responses beforehand. Speaking with conviction comes from knowing your material well.',
                'priority' => 'medium',
            ];
        }

        return $tips;
    }

    /**
     * Compare analysis across multiple recordings
     */
    public function comparePerformance(array $analysisIds): array
    {
        $analyses = VideoInterviewAnalysis::whereIn('id', $analysisIds)
            ->with('recording')
            ->orderBy('created_at')
            ->get();

        if ($analyses->count() < 2) {
            return ['error' => 'Need at least 2 analyses to compare'];
        }

        $first = $analyses->first();
        $last = $analyses->last();

        return [
            'sessions_compared' => $analyses->count(),
            'date_range' => [
                'first' => $first->analyzed_at?->toDateString(),
                'last' => $last->analyzed_at?->toDateString(),
            ],
            'overall_trend' => [
                'first_score' => $first->overall_score,
                'last_score' => $last->overall_score,
                'change' => round(($last->overall_score ?? 0) - ($first->overall_score ?? 0), 1),
                'direction' => ($last->overall_score ?? 0) > ($first->overall_score ?? 0) ? 'improving' : 'declining',
            ],
            'averages' => [
                'overall' => round($analyses->avg('overall_score') ?? 0, 1),
                'content' => round($analyses->avg('content_score') ?? 0, 1),
                'confidence' => round($analyses->avg('confidence_score') ?? 0, 1),
                'clarity' => round($analyses->avg('clarity_score') ?? 0, 1),
            ],
            'best_performance' => [
                'score' => $analyses->max('overall_score'),
                'date' => $analyses->where('overall_score', $analyses->max('overall_score'))->first()?->analyzed_at?->toDateString(),
            ],
        ];
    }
}
