<?php

namespace App\Services\AI;

use App\Models\InterviewResponse;
use App\Models\InterviewQuestion;
use App\Models\InterviewLiveFeedback;
use App\Models\AIInterviewCalculation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AnswerEvaluationService
{
    protected string $model = 'gpt-5.1'; // Azure OpenAI GPT-5.1

    // Filler words to detect
    protected array $fillerWords = [
        'um', 'uh', 'like', 'you know', 'i mean', 'basically', 'actually',
        'literally', 'sort of', 'kind of', 'right', 'okay', 'so', 'well',
        'er', 'ah', 'hmm', 'yeah', 'you see', 'you know what i mean'
    ];

    /**
     * Comprehensive evaluation of an interview answer
     */
    public function evaluateAnswer(InterviewResponse $response): array
    {
        $question = $response->interviewQuestion;

        try {
            $startTime = microtime(true);

            // Perform all evaluations in parallel-like structure
            $evaluations = [
                'content' => $this->evaluateContent($response->response_text, $question),
                'star' => $this->analyzeSTAR($response->response_text),
                'clarity' => $this->evaluateClarity($response->response_text),
                'confidence' => $this->evaluateConfidence($response->response_text),
                'filler_words' => $this->detectFillerWords($response->response_text),
            ];

            // Generate comprehensive feedback
            $feedback = $this->generateComprehensiveFeedback(
                $response->response_text,
                $question,
                $evaluations
            );

            $processingTime = (microtime(true) - $startTime) * 1000;

            // Calculate overall score
            $scores = [
                'content' => $evaluations['content']['score'],
                'structure' => $evaluations['star']['score'],
                'clarity' => $evaluations['clarity']['score'],
                'confidence' => $evaluations['confidence']['score'],
            ];

            $overallScore = $this->calculateOverallScore($scores);

            // Update response with all scores and analysis
            $response->update([
                'content_score' => $scores['content'],
                'structure_score' => $scores['structure'],
                'clarity_score' => $scores['clarity'],
                'confidence_score' => $scores['confidence'],
                'overall_score' => $overallScore,
                'star_analysis' => $evaluations['star']['analysis'],
                'filler_words_detected' => $evaluations['filler_words']['words'],
                'filler_word_count' => count($evaluations['filler_words']['words']),
                'key_points_covered' => $evaluations['content']['points_covered'],
                'missing_elements' => $evaluations['content']['missing_elements'],
                'strengths' => $feedback['strengths'],
                'weaknesses' => $feedback['weaknesses'],
                'ai_feedback' => $feedback['overall'],
                'improvement_suggestions' => $feedback['improvements'],
                'word_count' => str_word_count($response->response_text),
            ]);

            // Track AI usage
            AIInterviewCalculation::create([
                'user_id' => $response->user_id,
                'interview_session_id' => $response->interview_session_id,
                'calculation_type' => 'answer_analysis',
                'input_data' => ['response_length' => strlen($response->response_text)],
                'output_data' => ['overall_score' => $overallScore],
                'tokens_used' => $this->estimateTokens($response->response_text),
                'cost' => 0, // Will be calculated after OpenAI call
                'processing_time_ms' => $processingTime,
                'model_version' => $this->model,
            ]);

            return [
                'scores' => array_merge($scores, ['overall' => $overallScore]),
                'evaluations' => $evaluations,
                'feedback' => $feedback,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to evaluate answer', [
                'response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackEvaluation();
        }
    }

    /**
     * Evaluate content quality and relevance
     */
    protected function evaluateContent(string $answerText, InterviewQuestion $question): array
    {
        try {
            $keyPoints = $question->key_points_to_cover ?? [];
            $keyPointsStr = !empty($keyPoints) ? implode(', ', $keyPoints) : 'general relevance and completeness';

            $prompt = <<<EOT
Evaluate this interview answer for content quality:

Question: {$question->question_text}
Answer: {$answerText}

Key points to assess: {$keyPointsStr}

Evaluate:
1. How well does the answer address the question?
2. Which key points are covered?
3. What important elements are missing?
4. Is the answer complete and substantive?

Return ONLY a JSON object:
{
    "score": 0-100,
    "points_covered": ["point1", "point2"],
    "missing_elements": ["element1", "element2"],
    "relevance": "high|medium|low",
    "depth": "deep|moderate|shallow",
    "reasoning": "Brief explanation"
}
EOT;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert interview evaluator focused on content quality.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);
            return $result ?? $this->getDefaultContentEvaluation();

        } catch (\Exception $e) {
            Log::error('Content evaluation failed', ['error' => $e->getMessage()]);
            return $this->getDefaultContentEvaluation();
        }
    }

    /**
     * Analyze STAR methodology components
     */
    public function analyzeSTAR(string $answerText): array
    {
        try {
            $prompt = <<<EOT
Analyze this interview answer for STAR methodology (Situation, Task, Action, Result):

Answer: {$answerText}

For each component:
1. Identify if it's present
2. Extract the relevant text
3. Rate its quality (poor, fair, good, excellent)

Return ONLY a JSON object:
{
    "situation": {
        "present": true/false,
        "text": "extracted text or null",
        "quality": "poor|fair|good|excellent"
    },
    "task": {
        "present": true/false,
        "text": "extracted text or null",
        "quality": "poor|fair|good|excellent"
    },
    "action": {
        "present": true/false,
        "text": "extracted text or null",
        "quality": "poor|fair|good|excellent"
    },
    "result": {
        "present": true/false,
        "text": "extracted text or null",
        "quality": "poor|fair|good|excellent"
    },
    "score": 0-100,
    "completeness": "complete|partial|missing"
}
EOT;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert at analyzing STAR methodology in interview answers.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 600,
            ]);

            $analysis = json_decode($response->choices[0]->message->content, true);

            return [
                'analysis' => $analysis ?? $this->getDefaultSTARAnalysis(),
                'score' => $analysis['score'] ?? 50,
            ];

        } catch (\Exception $e) {
            Log::error('STAR analysis failed', ['error' => $e->getMessage()]);
            return [
                'analysis' => $this->getDefaultSTARAnalysis(),
                'score' => 50,
            ];
        }
    }

    /**
     * Evaluate clarity of communication
     */
    protected function evaluateClarity(string $answerText): array
    {
        try {
            $prompt = <<<EOT
Evaluate the clarity and communication quality of this interview answer:

Answer: {$answerText}

Assess:
1. Grammar and sentence structure
2. Logical flow and organization
3. Use of specific examples
4. Conciseness vs verbosity
5. Professional language

Return ONLY a JSON object:
{
    "score": 0-100,
    "grammar_quality": "excellent|good|fair|poor",
    "organization": "well_structured|somewhat_structured|unstructured",
    "specificity": "very_specific|somewhat_specific|vague",
    "conciseness": "concise|balanced|verbose",
    "issues": ["issue1", "issue2"]
}
EOT;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert in evaluating communication clarity.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 400,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);
            return $result ?? ['score' => 70];

        } catch (\Exception $e) {
            Log::error('Clarity evaluation failed', ['error' => $e->getMessage()]);
            return ['score' => 70];
        }
    }

    /**
     * Evaluate confidence level from text patterns
     */
    protected function evaluateConfidence(string $answerText): array
    {
        try {
            // Analyze language patterns for confidence indicators
            $text = strtolower($answerText);

            $confidenceIndicators = [
                'positive' => ['definitely', 'clearly', 'successfully', 'achieved', 'led', 'implemented', 'ensured'],
                'negative' => ['maybe', 'probably', 'i think', 'i guess', 'sort of', 'kind of', 'might'],
            ];

            $positiveCount = 0;
            $negativeCount = 0;

            foreach ($confidenceIndicators['positive'] as $indicator) {
                $positiveCount += substr_count($text, $indicator);
            }

            foreach ($confidenceIndicators['negative'] as $indicator) {
                $negativeCount += substr_count($text, $indicator);
            }

            // Calculate base score
            $baseScore = 70;
            $score = $baseScore + ($positiveCount * 3) - ($negativeCount * 5);
            $score = max(0, min(100, $score));

            // Use AI for deeper analysis
            $prompt = <<<EOT
Analyze the confidence level conveyed in this interview answer:

Answer: {$answerText}

Assess:
1. Use of definitive vs tentative language
2. Ownership of accomplishments
3. Certainty in statements
4. Professional assertiveness

Return ONLY a JSON object:
{
    "score": 0-100,
    "level": "very_confident|confident|moderate|hesitant|uncertain",
    "indicators": ["indicator1", "indicator2"]
}
EOT;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert at detecting confidence levels in communication.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 300,
            ]);

            $aiResult = json_decode($response->choices[0]->message->content, true);

            // Blend rule-based and AI scores
            $finalScore = $aiResult['score'] ?? $score;

            return [
                'score' => $finalScore,
                'level' => $aiResult['level'] ?? 'moderate',
                'indicators' => $aiResult['indicators'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Confidence evaluation failed', ['error' => $e->getMessage()]);
            return ['score' => 70, 'level' => 'moderate'];
        }
    }

    /**
     * Detect filler words in answer
     */
    public function detectFillerWords(string $answerText): array
    {
        $text = strtolower($answerText);
        $detectedWords = [];
        $wordPositions = [];

        foreach ($this->fillerWords as $fillerWord) {
            // Use word boundaries to avoid partial matches
            $pattern = '/\b' . preg_quote($fillerWord, '/') . '\b/i';
            $count = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

            if ($count > 0) {
                foreach ($matches[0] as $match) {
                    $detectedWords[] = $fillerWord;
                    $wordPositions[] = [
                        'word' => $fillerWord,
                        'position' => $match[1],
                    ];
                }
            }
        }

        // Calculate severity
        $totalWords = str_word_count($answerText);
        $fillerPercentage = $totalWords > 0 ? (count($detectedWords) / $totalWords) * 100 : 0;

        $severity = 'low';
        if ($fillerPercentage > 10) {
            $severity = 'high';
        } elseif ($fillerPercentage > 5) {
            $severity = 'medium';
        }

        return [
            'words' => $detectedWords,
            'positions' => $wordPositions,
            'count' => count($detectedWords),
            'percentage' => round($fillerPercentage, 2),
            'severity' => $severity,
        ];
    }

    /**
     * Generate comprehensive feedback
     */
    protected function generateComprehensiveFeedback(
        string $answerText,
        InterviewQuestion $question,
        array $evaluations
    ): array {
        try {
            $prompt = <<<EOT
Generate comprehensive, actionable feedback for this interview answer:

Question: {$question->question_text}
Answer: {$answerText}

Analysis Results:
- Content Score: {$evaluations['content']['score']}/100
- STAR Score: {$evaluations['star']['score']}/100
- Clarity Score: {$evaluations['clarity']['score']}/100
- Confidence Score: {$evaluations['confidence']['score']}/100
- Filler Words: {$evaluations['filler_words']['count']}

Provide:
1. Overall assessment (2-3 sentences)
2. Top 3 strengths
3. Top 3 areas for improvement
4. 3-5 specific, actionable suggestions

Return ONLY a JSON object:
{
    "overall": "Overall assessment text",
    "strengths": ["strength1", "strength2", "strength3"],
    "weaknesses": ["weakness1", "weakness2", "weakness3"],
    "improvements": ["improvement1", "improvement2", "improvement3", "improvement4", "improvement5"]
}
EOT;

            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a supportive interview coach providing constructive feedback.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            $feedback = json_decode($response->choices[0]->message->content, true);
            return $feedback ?? $this->getDefaultFeedback();

        } catch (\Exception $e) {
            Log::error('Feedback generation failed', ['error' => $e->getMessage()]);
            return $this->getDefaultFeedback();
        }
    }

    /**
     * Calculate weighted overall score
     */
    protected function calculateOverallScore(array $scores): float
    {
        $weights = [
            'content' => 0.40,
            'structure' => 0.25,
            'clarity' => 0.20,
            'confidence' => 0.15,
        ];

        $totalScore = 0;
        foreach ($weights as $component => $weight) {
            $totalScore += ($scores[$component] ?? 50) * $weight;
        }

        return round($totalScore, 2);
    }

    /**
     * Estimate token count for cost calculation
     */
    protected function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Default evaluations when AI fails
     */
    protected function getDefaultContentEvaluation(): array
    {
        return [
            'score' => 70,
            'points_covered' => [],
            'missing_elements' => [],
            'relevance' => 'medium',
            'depth' => 'moderate',
            'reasoning' => 'Unable to perform detailed analysis',
        ];
    }

    protected function getDefaultSTARAnalysis(): array
    {
        return [
            'situation' => ['present' => false, 'text' => null, 'quality' => 'poor'],
            'task' => ['present' => false, 'text' => null, 'quality' => 'poor'],
            'action' => ['present' => false, 'text' => null, 'quality' => 'poor'],
            'result' => ['present' => false, 'text' => null, 'quality' => 'poor'],
            'score' => 50,
            'completeness' => 'missing',
        ];
    }

    protected function getDefaultFeedback(): array
    {
        return [
            'overall' => 'Thank you for your answer. Practice more to improve your interview skills.',
            'strengths' => ['You provided an answer', 'You stayed on topic'],
            'weaknesses' => ['More detail needed', 'Consider using STAR methodology'],
            'improvements' => [
                'Provide specific examples',
                'Structure your answer with STAR method',
                'Be more concise',
                'Reduce filler words',
            ],
        ];
    }

    protected function getFallbackEvaluation(): array
    {
        return [
            'scores' => [
                'content' => 70,
                'structure' => 70,
                'clarity' => 70,
                'confidence' => 70,
                'overall' => 70,
            ],
            'evaluations' => [
                'content' => $this->getDefaultContentEvaluation(),
                'star' => ['analysis' => $this->getDefaultSTARAnalysis(), 'score' => 50],
                'clarity' => ['score' => 70],
                'confidence' => ['score' => 70],
                'filler_words' => ['words' => [], 'count' => 0, 'severity' => 'low'],
            ],
            'feedback' => $this->getDefaultFeedback(),
        ];
    }
}
