<?php

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewResponse;
use App\Models\InterviewFeedback;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

class AnswerAnalysisService
{
    // Common filler words to detect
    protected array $fillerWords = [
        'um', 'uh', 'like', 'you know', 'actually', 'basically', 'literally',
        'kind of', 'sort of', 'i mean', 'right', 'okay', 'so', 'well',
        'just', 'really', 'very', 'totally', 'absolutely',
    ];

    public function __construct(
        protected OpenAIService $openAIService
    ) {}

    /**
     * Analyze a user's answer in real-time
     */
    public function analyzeAnswer(
        InterviewQuestion $question,
        string $answerText,
        int $responseTimeSeconds,
        int $userId
    ): InterviewResponse {
        // Create initial response record
        $response = InterviewResponse::create([
            'interview_question_id' => $question->id,
            'user_id' => $userId,
            'response_type' => 'text',
            'response_text' => $answerText,
            'response_time_seconds' => $responseTimeSeconds,
            'answered_at' => now(),
        ]);

        // Perform comprehensive analysis
        $this->analyzeContent($response, $question);
        $this->analyzeConfidence($response);
        $this->analyzeClarity($response);
        $this->analyzeStructure($response, $question);
        $this->detectFillerWords($response);
        $this->analyzeSTAR($response, $question);
        $this->analyzeKeywords($response, $question);

        // Calculate overall score
        $response->calculateOverallScore();

        // Generate real-time feedback
        $this->generateRealTimeFeedback($response, $question);

        return $response->fresh(['feedback']);
    }

    /**
     * Analyze answer content quality using AI
     */
    protected function analyzeContent(InterviewResponse $response, InterviewQuestion $question): void
    {
        $prompt = $this->buildContentAnalysisPrompt($response, $question);

        try {
            $aiResponse = $this->openAIService->generateCompletion($prompt, [
                'max_tokens' => 300,
                'temperature' => 0.3,
            ]);

            $analysis = json_decode($aiResponse, true);

            if (isset($analysis['score'])) {
                $response->update([
                    'content_score' => min(100, max(0, $analysis['score'])),
                    'keywords_used' => $analysis['keywords_found'] ?? [],
                    'missing_elements' => $analysis['missing_elements'] ?? [],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Content analysis failed', [
                'error' => $e->getMessage(),
                'response_id' => $response->id,
            ]);

            // Fallback scoring
            $response->update(['content_score' => 70]);
        }
    }

    /**
     * Analyze confidence level in the answer
     */
    protected function analyzeConfidence(InterviewResponse $response): void
    {
        $text = $response->response_text;
        $confidenceScore = 75; // Base score

        // Positive indicators
        $confidenceIndicators = [
            'definitely', 'certainly', 'absolutely', 'clearly', 'successfully',
            'achieved', 'accomplished', 'demonstrated', 'proven', 'led',
        ];

        // Negative indicators
        $uncertainIndicators = [
            'maybe', 'perhaps', 'might', 'possibly', 'i think', 'i guess',
            'probably', 'not sure', 'kind of', 'sort of', 'i suppose',
        ];

        $textLower = strtolower($text);

        // Count indicators
        foreach ($confidenceIndicators as $indicator) {
            if (str_contains($textLower, $indicator)) {
                $confidenceScore += 3;
            }
        }

        foreach ($uncertainIndicators as $indicator) {
            if (str_contains($textLower, $indicator)) {
                $confidenceScore -= 5;
            }
        }

        // Check for question marks (indicates uncertainty)
        $questionMarks = substr_count($text, '?');
        $confidenceScore -= ($questionMarks * 2);

        // Check for hedging phrases
        if (preg_match('/\b(I\'m not|I don\'t know|uncertain)\b/i', $text)) {
            $confidenceScore -= 10;
        }

        // Ensure score is within bounds
        $confidenceScore = min(100, max(0, $confidenceScore));

        $response->update(['confidence_score' => $confidenceScore]);
    }

    /**
     * Analyze clarity and coherence
     */
    protected function analyzeClarity(InterviewResponse $response): void
    {
        $text = $response->response_text;
        $wordCount = $response->calculateWordCount();
        $clarityScore = 75; // Base score

        // Sentence structure analysis
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        if ($sentenceCount > 0) {
            $avgWordsPerSentence = $wordCount / $sentenceCount;

            // Optimal range: 15-25 words per sentence
            if ($avgWordsPerSentence >= 15 && $avgWordsPerSentence <= 25) {
                $clarityScore += 10;
            } elseif ($avgWordsPerSentence > 35) {
                $clarityScore -= 10; // Too long/complex
            } elseif ($avgWordsPerSentence < 8) {
                $clarityScore -= 5; // Too choppy
            }
        }

        // Check for transition words (indicates logical flow)
        $transitionWords = [
            'first', 'second', 'then', 'next', 'finally', 'additionally',
            'furthermore', 'however', 'therefore', 'consequently', 'as a result',
        ];

        $transitionCount = 0;
        $textLower = strtolower($text);
        
        foreach ($transitionWords as $word) {
            if (str_contains($textLower, $word)) {
                $transitionCount++;
            }
        }

        if ($transitionCount >= 2) {
            $clarityScore += 10;
        }

        // Check for excessive repetition
        $words = str_word_count($text, 1);
        $uniqueWords = array_unique($words);
        $repetitionRatio = count($uniqueWords) / max(1, count($words));

        if ($repetitionRatio < 0.6) {
            $clarityScore -= 10; // Too repetitive
        }

        $clarityScore = min(100, max(0, $clarityScore));

        $response->update(['clarity_score' => $clarityScore]);
    }

    /**
     * Analyze answer structure
     */
    protected function analyzeStructure(InterviewResponse $response, InterviewQuestion $question): void
    {
        $text = $response->response_text;
        $structureScore = 70; // Base score

        // Check for proper introduction
        $hasIntro = preg_match('/^(In my experience|At my previous|When I was|During my)/i', trim($text));
        if ($hasIntro) {
            $structureScore += 10;
        }

        // Check for conclusion
        $hasConclusion = preg_match('/(In conclusion|As a result|Ultimately|This taught me|This led to)/i', $text);
        if ($hasConclusion) {
            $structureScore += 10;
        }

        // Check for logical progression markers
        $progressionMarkers = preg_match_all('/(First|Second|Then|Next|After that|Finally)/i', $text);
        if ($progressionMarkers >= 2) {
            $structureScore += 10;
        }

        // For behavioral questions, check STAR structure
        if ($question->requiresSTARFormat()) {
            $starComponents = $this->detectSTARComponents($text);
            $starCount = count(array_filter($starComponents));
            
            $structureScore += ($starCount * 5); // 5 points per STAR component
        }

        $structureScore = min(100, max(0, $structureScore));

        $response->update(['structure_score' => $structureScore]);
    }

    /**
     * Detect and count filler words
     */
    protected function detectFillerWords(InterviewResponse $response): void
    {
        $text = strtolower($response->response_text);
        $fillerCounts = [];

        foreach ($this->fillerWords as $filler) {
            $count = substr_count($text, $filler);
            if ($count > 0) {
                $fillerCounts[$filler] = $count;
            }
        }

        $response->update(['filler_words' => $fillerCounts]);
    }

    /**
     * Analyze STAR methodology compliance
     */
    protected function analyzeSTAR(InterviewResponse $response, InterviewQuestion $question): void
    {
        if (!$question->requiresSTARFormat()) {
            return;
        }

        $text = $response->response_text;
        $starComponents = $this->detectSTARComponents($text);

        $response->update(['star_analysis' => $starComponents]);
    }

    /**
     * Detect STAR components in answer
     */
    protected function detectSTARComponents(string $text): array
    {
        $textLower = strtolower($text);
        
        return [
            'situation' => $this->detectSituation($textLower),
            'task' => $this->detectTask($textLower),
            'action' => $this->detectAction($textLower),
            'result' => $this->detectResult($textLower),
        ];
    }

    protected function detectSituation(string $text): ?string
    {
        $situationPatterns = [
            '/(?:at|in|during|when)\s+(?:my|the)\s+(?:previous|last|recent)?\s*(?:role|position|job|company|project)/i',
            '/(?:the|a)\s+(?:situation|scenario|challenge|problem)\s+(?:was|involved|arose)/i',
        ];

        foreach ($situationPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return 'Situation context detected';
            }
        }

        return null;
    }

    protected function detectTask(string $text): ?string
    {
        $taskPatterns = [
            '/(?:I|we)\s+(?:needed|had)\s+to\s+(?:develop|create|build|solve|address|fix)/i',
            '/(?:my|the)\s+(?:responsibility|task|objective|goal)\s+was\s+to/i',
        ];

        foreach ($taskPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return 'Task/objective identified';
            }
        }

        return null;
    }

    protected function detectAction(string $text): ?string
    {
        $actionPatterns = [
            '/(?:I|we)\s+(?:implemented|developed|created|designed|built|organized|led)/i',
            '/(?:I|we)\s+(?:decided|chose|selected)\s+to/i',
            '/(?:my|the)\s+approach\s+was\s+to/i',
        ];

        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return 'Actions taken described';
            }
        }

        return null;
    }

    protected function detectResult(string $text): ?string
    {
        $resultPatterns = [
            '/(?:as\s+a\s+result|this\s+led\s+to|ultimately|in\s+the\s+end|consequently)/i',
            '/(?:achieved|accomplished|delivered|resulted\s+in|increased|decreased|improved)/i',
            '/(?:\d+%|\$[\d,]+)\s+(?:increase|decrease|improvement|reduction)/i',
        ];

        foreach ($resultPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return 'Results and outcomes mentioned';
            }
        }

        return null;
    }

    /**
     * Analyze keyword usage
     */
    protected function analyzeKeywords(InterviewResponse $response, InterviewQuestion $question): void
    {
        $expectedElements = $question->expected_elements ?? [];
        
        if (empty($expectedElements)) {
            return;
        }

        $text = strtolower($response->response_text);
        $keywordsUsed = [];
        $missingElements = [];

        foreach ($expectedElements as $element) {
            $elementLower = strtolower($element);
            
            if (str_contains($text, $elementLower)) {
                $keywordsUsed[] = $element;
            } else {
                $missingElements[] = $element;
            }
        }

        $currentKeywords = $response->keywords_used ?? [];
        $currentMissing = $response->missing_elements ?? [];

        $response->update([
            'keywords_used' => array_merge($currentKeywords, $keywordsUsed),
            'missing_elements' => array_merge($currentMissing, $missingElements),
        ]);
    }

    /**
     * Generate real-time feedback
     */
    protected function generateRealTimeFeedback(InterviewResponse $response, InterviewQuestion $question): void
    {
        // Content feedback
        if ($response->content_score < 70) {
            InterviewFeedback::create([
                'interview_response_id' => $response->id,
                'feedback_type' => 'real_time',
                'feedback_text' => 'Your answer could be more comprehensive. Consider adding more specific details and examples.',
                'is_positive' => false,
                'focus_area' => 'content',
                'priority' => 8,
                'suggestions' => [
                    'Include specific examples from your experience',
                    'Provide measurable outcomes or results',
                    'Address all parts of the question',
                ],
            ]);
        }

        // Filler words feedback
        $fillerPercentage = $response->getFillerWordPercentage();
        if ($fillerPercentage > 5) {
            InterviewFeedback::create([
                'interview_response_id' => $response->id,
                'feedback_type' => 'real_time',
                'feedback_text' => "You used filler words in {$fillerPercentage}% of your answer. Try to reduce these for more confident delivery.",
                'is_positive' => false,
                'focus_area' => 'delivery',
                'priority' => 6,
                'suggestions' => [
                    'Pause briefly instead of using filler words',
                    'Practice speaking more deliberately',
                    'Take a breath when you need time to think',
                ],
            ]);
        }

        // STAR feedback for behavioral questions
        if ($question->requiresSTARFormat() && !$response->hasSTARComponents()) {
            $missingComponents = $response->getMissingSTARComponents();
            
            InterviewFeedback::create([
                'interview_response_id' => $response->id,
                'feedback_type' => 'real_time',
                'feedback_text' => 'Your answer is missing STAR components: ' . implode(', ', $missingComponents),
                'is_positive' => false,
                'focus_area' => 'structure',
                'priority' => 9,
                'suggestions' => array_map(function($component) {
                    return "Add the {$component} component to strengthen your answer";
                }, $missingComponents),
            ]);
        }

        // Positive feedback
        if ($response->overall_score >= 85) {
            InterviewFeedback::create([
                'interview_response_id' => $response->id,
                'feedback_type' => 'real_time',
                'feedback_text' => 'Excellent answer! You demonstrated strong understanding and communication.',
                'is_positive' => true,
                'focus_area' => 'overall',
                'priority' => 5,
                'strengths' => [
                    'Clear and well-structured response',
                    'Specific examples provided',
                    'Confident delivery',
                ],
            ]);
        }
    }

    /**
     * Build prompt for content analysis
     */
    protected function buildContentAnalysisPrompt(InterviewResponse $response, InterviewQuestion $question): string
    {
        $prompt = "Analyze this interview answer for content quality.\n\n";
        $prompt .= "Question: {$question->question_text}\n\n";
        $prompt .= "Answer: {$response->response_text}\n\n";
        
        if ($expectedElements = $question->expected_elements) {
            $prompt .= "Expected elements:\n";
            foreach ($expectedElements as $element) {
                $prompt .= "- {$element}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Return a JSON object with:\n";
        $prompt .= "- score: Number from 0-100 rating content quality\n";
        $prompt .= "- keywords_found: Array of expected elements that were mentioned\n";
        $prompt .= "- missing_elements: Array of expected elements that were not mentioned\n";
        $prompt .= "- strengths: Array of what was done well\n";
        $prompt .= "- improvements: Array of what could be improved\n";

        return $prompt;
    }
}
