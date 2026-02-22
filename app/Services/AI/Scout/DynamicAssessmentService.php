<?php

namespace App\Services\AI\Scout;

use App\Models\Job;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class DynamicAssessmentService
{
    /**
     * Difficulty levels with point values
     */
    protected const DIFFICULTY_LEVELS = [
        'easy' => ['points' => 25, 'weight' => 1.0],
        'medium' => ['points' => 50, 'weight' => 1.5],
        'hard' => ['points' => 75, 'weight' => 2.0],
        'expert' => ['points' => 100, 'weight' => 2.5]
    ];

    /**
     * Adaptive difficulty thresholds
     */
    protected const ADAPTIVE_THRESHOLDS = [
        'increase_difficulty' => 0.80, // 80% correct answers
        'decrease_difficulty' => 0.40, // 40% or below
        'maintain_difficulty' => [0.41, 0.79] // 41-79%
    ];

    /**
     * Generate personalized assessment for candidate.
     *
     * @param int $applicationId
     * @param int $jobId
     * @param array $options
     * @return array
     */
    public function generateAssessment(int $applicationId, int $jobId, array $options = []): array
    {
        try {
            Log::info('Generating dynamic assessment', [
                'application_id' => $applicationId,
                'job_id' => $jobId
            ]);

            $application = Application::with(['user.profile'])->findOrFail($applicationId);
            $job = Job::with(['company.dnaProfile'])->findOrFail($jobId);

            // Extract candidate context
            $candidateContext = $this->buildCandidateContext($application);
            $roleContext = $this->buildRoleContext($job);

            // Generate initial question set (5 questions to start)
            $initialDifficulty = $options['initial_difficulty'] ?? 'medium';
            $questionCount = $options['initial_question_count'] ?? 5;
            $assessmentType = $options['type'] ?? 'comprehensive'; // comprehensive, technical, behavioral, case_study

            $questions = $this->generateQuestions(
                $candidateContext,
                $roleContext,
                $initialDifficulty,
                $questionCount,
                $assessmentType
            );

            if (!$questions['success']) {
                throw new Exception($questions['message'] ?? 'Failed to generate questions');
            }

            // Create assessment record
            $assessment = Assessment::create([
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'type' => $assessmentType,
                'status' => 'pending',
                'total_questions' => $questionCount,
                'questions_answered' => 0,
                'current_difficulty' => $initialDifficulty,
                'adaptive_enabled' => true,
                'time_limit_minutes' => $options['time_limit'] ?? 60,
                'started_at' => null,
                'completed_at' => null,
                'metadata' => [
                    'candidate_context' => $candidateContext,
                    'role_context' => $roleContext,
                    'adaptive_thresholds' => self::ADAPTIVE_THRESHOLDS
                ]
            ]);

            // Store questions
            foreach ($questions['data']['questions'] as $index => $questionData) {
                AssessmentQuestion::create([
                    'assessment_id' => $assessment->id,
                    'question_number' => $index + 1,
                    'question_text' => $questionData['question'],
                    'question_type' => $questionData['type'], // multiple_choice, coding, essay, case_study
                    'difficulty' => $questionData['difficulty'],
                    'category' => $questionData['category'],
                    'expected_answer' => $questionData['expected_answer'] ?? null,
                    'evaluation_criteria' => $questionData['evaluation_criteria'],
                    'time_limit_seconds' => $questionData['time_limit'] ?? 300,
                    'points' => self::DIFFICULTY_LEVELS[$questionData['difficulty']]['points'],
                    'options' => $questionData['options'] ?? null, // For multiple choice
                    'code_template' => $questionData['code_template'] ?? null, // For coding challenges
                    'context' => $questionData['context'] ?? null
                ]);
            }

            Log::info('Assessment generated successfully', [
                'assessment_id' => $assessment->id,
                'question_count' => count($questions['data']['questions'])
            ]);

            return [
                'success' => true,
                'data' => [
                    'assessment_id' => $assessment->id,
                    'type' => $assessmentType,
                    'total_questions' => $questionCount,
                    'time_limit_minutes' => $assessment->time_limit_minutes,
                    'initial_difficulty' => $initialDifficulty,
                    'adaptive_enabled' => true,
                    'instructions' => $this->generateInstructions($assessmentType, $questionCount)
                ]
            ];

        } catch (Exception $e) {
            Log::error('Assessment generation failed', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate assessment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Submit answer and get next question (adaptive logic).
     *
     * @param int $assessmentId
     * @param int $questionId
     * @param array $answer
     * @return array
     */
    public function submitAnswer(int $assessmentId, int $questionId, array $answer): array
    {
        try {
            $assessment = Assessment::with(['questions', 'responses'])->findOrFail($assessmentId);
            $question = AssessmentQuestion::findOrFail($questionId);

            if ($assessment->status === 'completed') {
                return [
                    'success' => false,
                    'message' => 'Assessment already completed'
                ];
            }

            // Mark assessment as in_progress on first answer
            if ($assessment->status === 'pending') {
                $assessment->update([
                    'status' => 'in_progress',
                    'started_at' => now()
                ]);
            }

            // Evaluate the answer
            $evaluation = $this->evaluateAnswer($question, $answer);

            // Store response
            $response = AssessmentResponse::create([
                'assessment_id' => $assessmentId,
                'question_id' => $questionId,
                'answer' => $answer['answer'] ?? null,
                'code_submission' => $answer['code'] ?? null,
                'is_correct' => $evaluation['is_correct'],
                'score' => $evaluation['score'],
                'max_score' => $question->points,
                'time_taken_seconds' => $answer['time_taken'] ?? null,
                'confidence_level' => $answer['confidence'] ?? null, // 1-5 scale
                'evaluation_feedback' => $evaluation['feedback'],
                'evaluation_details' => $evaluation['details'],
                'submitted_at' => now()
            ]);

            // Update assessment progress
            $assessment->increment('questions_answered');
            
            // Calculate performance metrics
            $performanceMetrics = $this->calculatePerformanceMetrics($assessment->fresh());

            // Determine if assessment is complete
            $isComplete = $assessment->questions_answered >= $assessment->total_questions;

            if ($isComplete) {
                $assessment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'final_score' => $performanceMetrics['weighted_score'],
                    'performance_summary' => $performanceMetrics
                ]);

                return [
                    'success' => true,
                    'assessment_complete' => true,
                    'final_results' => $this->generateFinalResults($assessment),
                    'evaluation' => $evaluation
                ];
            }

            // Adaptive difficulty adjustment
            $nextDifficulty = $this->determineNextDifficulty(
                $assessment->current_difficulty,
                $performanceMetrics
            );

            if ($nextDifficulty !== $assessment->current_difficulty) {
                Log::info('Adaptive difficulty adjustment', [
                    'assessment_id' => $assessmentId,
                    'from' => $assessment->current_difficulty,
                    'to' => $nextDifficulty,
                    'accuracy' => $performanceMetrics['accuracy']
                ]);
            }

            // Generate next question
            $application = Application::with(['user.profile'])->find($assessment->application_id);
            $job = Job::with(['company.dnaProfile'])->find($assessment->job_id);

            $candidateContext = $this->buildCandidateContext($application);
            $roleContext = $this->buildRoleContext($job);

            $nextQuestionData = $this->generateQuestions(
                $candidateContext,
                $roleContext,
                $nextDifficulty,
                1,
                $assessment->type,
                $performanceMetrics // Use performance data to guide question generation
            );

            if ($nextQuestionData['success']) {
                $nextQuestion = AssessmentQuestion::create([
                    'assessment_id' => $assessment->id,
                    'question_number' => $assessment->questions_answered + 1,
                    'question_text' => $nextQuestionData['data']['questions'][0]['question'],
                    'question_type' => $nextQuestionData['data']['questions'][0]['type'],
                    'difficulty' => $nextDifficulty,
                    'category' => $nextQuestionData['data']['questions'][0]['category'],
                    'expected_answer' => $nextQuestionData['data']['questions'][0]['expected_answer'] ?? null,
                    'evaluation_criteria' => $nextQuestionData['data']['questions'][0]['evaluation_criteria'],
                    'time_limit_seconds' => $nextQuestionData['data']['questions'][0]['time_limit'] ?? 300,
                    'points' => self::DIFFICULTY_LEVELS[$nextDifficulty]['points'],
                    'options' => $nextQuestionData['data']['questions'][0]['options'] ?? null,
                    'code_template' => $nextQuestionData['data']['questions'][0]['code_template'] ?? null,
                    'context' => $nextQuestionData['data']['questions'][0]['context'] ?? null
                ]);

                $assessment->update(['current_difficulty' => $nextDifficulty]);
            }

            return [
                'success' => true,
                'assessment_complete' => false,
                'evaluation' => $evaluation,
                'performance_metrics' => $performanceMetrics,
                'next_question' => $nextQuestion ? [
                    'id' => $nextQuestion->id,
                    'question_number' => $nextQuestion->question_number,
                    'question_text' => $nextQuestion->question_text,
                    'question_type' => $nextQuestion->question_type,
                    'difficulty' => $nextQuestion->difficulty,
                    'category' => $nextQuestion->category,
                    'time_limit_seconds' => $nextQuestion->time_limit_seconds,
                    'points' => $nextQuestion->points,
                    'options' => $nextQuestion->options,
                    'code_template' => $nextQuestion->code_template
                ] : null,
                'difficulty_adjusted' => $nextDifficulty !== $assessment->current_difficulty
            ];

        } catch (Exception $e) {
            Log::error('Answer submission failed', [
                'assessment_id' => $assessmentId,
                'question_id' => $questionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to submit answer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate personalized questions using GPT-4.
     *
     * @param array $candidateContext
     * @param array $roleContext
     * @param string $difficulty
     * @param int $count
     * @param string $type
     * @param array|null $performanceData
     * @return array
     */
    protected function generateQuestions(
        array $candidateContext,
        array $roleContext,
        string $difficulty,
        int $count,
        string $type,
        ?array $performanceData = null
    ): array {
        $prompt = $this->buildQuestionGenerationPrompt(
            $candidateContext,
            $roleContext,
            $difficulty,
            $count,
            $type,
            $performanceData
        );

        $cacheKey = 'assessment_questions_' . md5($prompt);

        try {
            // No caching for assessments - each must be unique
            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are an expert technical interviewer and assessment designer. You create unique, challenging, and fair assessment questions tailored to individual candidates and roles. Your questions are designed to accurately gauge competency across multiple dimensions while preventing memorization or unfair advantages."
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.9, // Higher temperature for variety
                'max_tokens' => 3000,
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            $questionData = json_decode($content, true);

            return [
                'success' => true,
                'data' => $questionData
            ];

        } catch (Exception $e) {
            Log::error('Question generation failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate questions'
            ];
        }
    }

    /**
     * Build GPT-4 prompt for question generation.
     *
     * @param array $candidateContext
     * @param array $roleContext
     * @param string $difficulty
     * @param int $count
     * @param string $type
     * @param array|null $performanceData
     * @return string
     */
    protected function buildQuestionGenerationPrompt(
        array $candidateContext,
        array $roleContext,
        string $difficulty,
        int $count,
        string $type,
        ?array $performanceData = null
    ): string {
        $prompt = "Generate {$count} unique assessment question(s) with the following specifications:\n\n";

        $prompt .= "**Candidate Context:**\n";
        $prompt .= "- Name: {$candidateContext['name']}\n";
        $prompt .= "- Experience: {$candidateContext['years_experience']} years\n";
        $prompt .= "- Skills: " . implode(', ', $candidateContext['skills']) . "\n";
        $prompt .= "- Background: {$candidateContext['background']}\n\n";

        $prompt .= "**Role Context:**\n";
        $prompt .= "- Position: {$roleContext['title']}\n";
        $prompt .= "- Department: {$roleContext['department']}\n";
        $prompt .= "- Required Skills: " . implode(', ', $roleContext['required_skills']) . "\n";
        $prompt .= "- Seniority: {$roleContext['seniority_level']}\n\n";

        $prompt .= "**Assessment Parameters:**\n";
        $prompt .= "- Type: {$type}\n";
        $prompt .= "- Difficulty Level: {$difficulty}\n";
        $prompt .= "- Question Count: {$count}\n\n";

        if ($performanceData) {
            $prompt .= "**Performance Data (for adaptive difficulty):**\n";
            $prompt .= "- Current Accuracy: {$performanceData['accuracy']}%\n";
            $prompt .= "- Average Time per Question: {$performanceData['avg_time_per_question']}s\n";
            $prompt .= "- Strengths: " . implode(', ', $performanceData['strong_categories'] ?? []) . "\n";
            $prompt .= "- Weaknesses: " . implode(', ', $performanceData['weak_categories'] ?? []) . "\n\n";
        }

        $prompt .= "**Instructions:**\n";
        $prompt .= "1. Create {$count} UNIQUE question(s) that no candidate could have seen before\n";
        $prompt .= "2. Tailor questions to candidate's specific experience and resume\n";
        $prompt .= "3. Difficulty '{$difficulty}' means:\n";
        $prompt .= $this->getDifficultyGuidelines($difficulty);
        $prompt .= "4. Assessment type '{$type}' means:\n";
        $prompt .= $this->getTypeGuidelines($type);
        $prompt .= "5. Questions should test REAL skills needed for this role\n";
        $prompt .= "6. Include scenario-based challenges from actual work situations\n";
        $prompt .= "7. Avoid generic textbook questions - make them specific and practical\n\n";

        if ($performanceData) {
            $prompt .= "8. Focus more on weak categories to identify true skill level\n";
            $prompt .= "9. Increase complexity if accuracy is high, decrease if struggling\n\n";
        }

        $prompt .= "**Required JSON Response Format:**\n";
        $prompt .= "{\n";
        $prompt .= "  \"questions\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"question\": \"Question text here\",\n";
        $prompt .= "      \"type\": \"multiple_choice|coding|essay|case_study\",\n";
        $prompt .= "      \"difficulty\": \"{$difficulty}\",\n";
        $prompt .= "      \"category\": \"technical|behavioral|problem_solving|system_design|leadership\",\n";
        $prompt .= "      \"expected_answer\": \"For evaluation purposes\",\n";
        $prompt .= "      \"evaluation_criteria\": [\"criterion1\", \"criterion2\"],\n";
        $prompt .= "      \"time_limit\": 300,\n";
        $prompt .= "      \"options\": [\"A\", \"B\", \"C\", \"D\"],  // Only for multiple_choice\n";
        $prompt .= "      \"code_template\": \"function solve() {\\n  // Your code here\\n}\",  // Only for coding\n";
        $prompt .= "      \"context\": \"Background scenario for the question\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}";

        return $prompt;
    }

    /**
     * Evaluate candidate's answer.
     *
     * @param AssessmentQuestion $question
     * @param array $answer
     * @return array
     */
    protected function evaluateAnswer(AssessmentQuestion $question, array $answer): array
    {
        // For multiple choice, simple comparison
        if ($question->question_type === 'multiple_choice') {
            $isCorrect = strtolower($answer['answer']) === strtolower($question->expected_answer);
            $score = $isCorrect ? $question->points : 0;

            return [
                'is_correct' => $isCorrect,
                'score' => $score,
                'feedback' => $isCorrect ? 
                    'Correct! Well done.' : 
                    "Incorrect. The correct answer was: {$question->expected_answer}",
                'details' => [
                    'submitted' => $answer['answer'],
                    'expected' => $question->expected_answer
                ]
            ];
        }

        // For coding, essay, case_study - use GPT-4 for evaluation
        return $this->evaluateWithAI($question, $answer);
    }

    /**
     * Use GPT-4 to evaluate complex answers.
     *
     * @param AssessmentQuestion $question
     * @param array $answer
     * @return array
     */
    protected function evaluateWithAI(AssessmentQuestion $question, array $answer): array
    {
        try {
            $evaluationPrompt = $this->buildEvaluationPrompt($question, $answer);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are an expert technical evaluator. Assess candidate responses fairly and objectively based on the provided criteria. Provide constructive feedback."
                    ],
                    [
                        'role' => 'user',
                        'content' => $evaluationPrompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            $evaluation = json_decode($content, true);

            return [
                'is_correct' => $evaluation['is_correct'] ?? ($evaluation['score'] >= ($question->points * 0.7)),
                'score' => $evaluation['score'],
                'feedback' => $evaluation['feedback'],
                'details' => $evaluation['details'] ?? []
            ];

        } catch (Exception $e) {
            Log::error('AI evaluation failed', ['error' => $e->getMessage()]);

            // Fallback to partial credit
            return [
                'is_correct' => false,
                'score' => $question->points * 0.5,
                'feedback' => 'Your answer has been recorded and will be reviewed.',
                'details' => ['evaluation_error' => true]
            ];
        }
    }

    /**
     * Build evaluation prompt for GPT-4.
     *
     * @param AssessmentQuestion $question
     * @param array $answer
     * @return string
     */
    protected function buildEvaluationPrompt(AssessmentQuestion $question, array $answer): string
    {
        $prompt = "Evaluate the following candidate response:\n\n";
        $prompt .= "**Question:**\n{$question->question_text}\n\n";
        $prompt .= "**Question Type:** {$question->question_type}\n";
        $prompt .= "**Difficulty:** {$question->difficulty}\n";
        $prompt .= "**Max Points:** {$question->points}\n\n";

        if ($question->question_type === 'coding') {
            $prompt .= "**Code Submission:**\n```\n{$answer['code']}\n```\n\n";
        } else {
            $prompt .= "**Candidate Answer:**\n{$answer['answer']}\n\n";
        }

        $prompt .= "**Expected Answer/Solution:**\n{$question->expected_answer}\n\n";
        $prompt .= "**Evaluation Criteria:**\n";
        foreach ($question->evaluation_criteria as $criterion) {
            $prompt .= "- {$criterion}\n";
        }

        $prompt .= "\n**Required JSON Response:**\n";
        $prompt .= "{\n";
        $prompt .= "  \"score\": 0-{$question->points},\n";
        $prompt .= "  \"is_correct\": true/false,\n";
        $prompt .= "  \"feedback\": \"Constructive feedback explaining the score\",\n";
        $prompt .= "  \"details\": {\n";
        $prompt .= "    \"strengths\": [\"strength1\", \"strength2\"],\n";
        $prompt .= "    \"improvements\": [\"area1\", \"area2\"],\n";
        $prompt .= "    \"correctness\": \"percentage or description\"\n";
        $prompt .= "  }\n";
        $prompt .= "}";

        return $prompt;
    }

    /**
     * Calculate performance metrics for adaptive difficulty.
     *
     * @param Assessment $assessment
     * @return array
     */
    protected function calculatePerformanceMetrics(Assessment $assessment): array
    {
        $responses = $assessment->responses;

        if ($responses->isEmpty()) {
            return [
                'accuracy' => 0,
                'avg_time_per_question' => 0,
                'weighted_score' => 0,
                'total_points_earned' => 0,
                'total_points_possible' => 0,
                'difficulty_breakdown' => [],
                'category_breakdown' => [],
                'strong_categories' => [],
                'weak_categories' => []
            ];
        }

        $totalCorrect = $responses->where('is_correct', true)->count();
        $totalQuestions = $responses->count();
        $accuracy = ($totalCorrect / $totalQuestions) * 100;

        $avgTime = $responses->avg('time_taken_seconds');

        $totalPointsEarned = $responses->sum('score');
        $totalPointsPossible = $responses->sum('max_score');

        // Weighted score considers difficulty
        $weightedScore = 0;
        $totalWeight = 0;
        foreach ($responses as $response) {
            $question = $response->question;
            $weight = self::DIFFICULTY_LEVELS[$question->difficulty]['weight'];
            $weightedScore += $response->score * $weight;
            $totalWeight += $question->points * $weight;
        }
        $weightedScorePercent = $totalWeight > 0 ? ($weightedScore / $totalWeight) * 100 : 0;

        // Breakdown by difficulty
        $difficultyBreakdown = [];
        foreach (array_keys(self::DIFFICULTY_LEVELS) as $diff) {
            $diffResponses = $responses->filter(fn($r) => $r->question->difficulty === $diff);
            if ($diffResponses->isNotEmpty()) {
                $difficultyBreakdown[$diff] = [
                    'attempted' => $diffResponses->count(),
                    'correct' => $diffResponses->where('is_correct', true)->count(),
                    'accuracy' => ($diffResponses->where('is_correct', true)->count() / $diffResponses->count()) * 100
                ];
            }
        }

        // Breakdown by category
        $categoryBreakdown = [];
        $categories = $responses->pluck('question.category')->unique();
        foreach ($categories as $category) {
            $catResponses = $responses->filter(fn($r) => $r->question->category === $category);
            $categoryBreakdown[$category] = [
                'attempted' => $catResponses->count(),
                'correct' => $catResponses->where('is_correct', true)->count(),
                'accuracy' => ($catResponses->where('is_correct', true)->count() / $catResponses->count()) * 100
            ];
        }

        // Identify strengths and weaknesses
        $strongCategories = collect($categoryBreakdown)
            ->filter(fn($data) => $data['accuracy'] >= 75)
            ->keys()
            ->toArray();

        $weakCategories = collect($categoryBreakdown)
            ->filter(fn($data) => $data['accuracy'] < 50)
            ->keys()
            ->toArray();

        return [
            'accuracy' => round($accuracy, 2),
            'avg_time_per_question' => round($avgTime, 2),
            'weighted_score' => round($weightedScorePercent, 2),
            'total_points_earned' => $totalPointsEarned,
            'total_points_possible' => $totalPointsPossible,
            'difficulty_breakdown' => $difficultyBreakdown,
            'category_breakdown' => $categoryBreakdown,
            'strong_categories' => $strongCategories,
            'weak_categories' => $weakCategories
        ];
    }

    /**
     * Determine next difficulty level based on performance.
     *
     * @param string $currentDifficulty
     * @param array $metrics
     * @return string
     */
    protected function determineNextDifficulty(string $currentDifficulty, array $metrics): string
    {
        $accuracy = $metrics['accuracy'] / 100; // Convert to 0-1 scale

        // If accuracy is high, increase difficulty
        if ($accuracy >= self::ADAPTIVE_THRESHOLDS['increase_difficulty']) {
            return $this->getNextHigherDifficulty($currentDifficulty);
        }

        // If accuracy is low, decrease difficulty
        if ($accuracy <= self::ADAPTIVE_THRESHOLDS['decrease_difficulty']) {
            return $this->getNextLowerDifficulty($currentDifficulty);
        }

        // Maintain current difficulty
        return $currentDifficulty;
    }

    /**
     * Get next higher difficulty level.
     *
     * @param string $current
     * @return string
     */
    protected function getNextHigherDifficulty(string $current): string
    {
        $levels = array_keys(self::DIFFICULTY_LEVELS);
        $currentIndex = array_search($current, $levels);
        $nextIndex = min($currentIndex + 1, count($levels) - 1);
        return $levels[$nextIndex];
    }

    /**
     * Get next lower difficulty level.
     *
     * @param string $current
     * @return string
     */
    protected function getNextLowerDifficulty(string $current): string
    {
        $levels = array_keys(self::DIFFICULTY_LEVELS);
        $currentIndex = array_search($current, $levels);
        $nextIndex = max($currentIndex - 1, 0);
        return $levels[$nextIndex];
    }

    /**
     * Generate final assessment results.
     *
     * @param Assessment $assessment
     * @return array
     */
    protected function generateFinalResults(Assessment $assessment): array
    {
        $metrics = $assessment->performance_summary;

        // Determine proficiency level
        $proficiency = $this->determineProficiencyLevel($metrics['weighted_score']);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($metrics);

        return [
            'assessment_id' => $assessment->id,
            'status' => 'completed',
            'final_score' => $assessment->final_score,
            'proficiency_level' => $proficiency,
            'performance_metrics' => $metrics,
            'recommendations' => $recommendations,
            'completed_at' => $assessment->completed_at->toIso8601String(),
            'time_taken_minutes' => $assessment->started_at->diffInMinutes($assessment->completed_at),
            'summary' => [
                'total_questions' => $assessment->total_questions,
                'questions_answered' => $assessment->questions_answered,
                'accuracy' => $metrics['accuracy'] . '%',
                'strong_areas' => $metrics['strong_categories'],
                'areas_for_improvement' => $metrics['weak_categories']
            ]
        ];
    }

    /**
     * Determine proficiency level from score.
     *
     * @param float $score
     * @return string
     */
    protected function determineProficiencyLevel(float $score): string
    {
        if ($score >= 90) return 'Expert';
        if ($score >= 75) return 'Advanced';
        if ($score >= 60) return 'Intermediate';
        if ($score >= 45) return 'Basic';
        return 'Beginner';
    }

    /**
     * Generate hiring recommendations based on performance.
     *
     * @param array $metrics
     * @return array
     */
    protected function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        if ($metrics['weighted_score'] >= 85) {
            $recommendations[] = "STRONG HIRE - Candidate demonstrated exceptional competency";
        } elseif ($metrics['weighted_score'] >= 70) {
            $recommendations[] = "RECOMMEND - Candidate shows solid skills with room for growth";
        } elseif ($metrics['weighted_score'] >= 55) {
            $recommendations[] = "CONSIDER - Candidate meets basic requirements but may need support";
        } else {
            $recommendations[] = "NOT RECOMMENDED - Candidate did not meet minimum competency threshold";
        }

        if (!empty($metrics['strong_categories'])) {
            $recommendations[] = "Strengths in: " . implode(', ', $metrics['strong_categories']);
        }

        if (!empty($metrics['weak_categories'])) {
            $recommendations[] = "Development needed in: " . implode(', ', $metrics['weak_categories']);
        }

        return $recommendations;
    }

    // Helper methods for context building

    protected function buildCandidateContext(Application $application): array
    {
        $profile = $application->user->profile;

        return [
            'name' => $application->user->name,
            'years_experience' => $profile->years_of_experience ?? 0,
            'skills' => $profile->skills ?? [],
            'background' => $profile->summary ?? 'No background provided'
        ];
    }

    protected function buildRoleContext(Job $job): array
    {
        return [
            'title' => $job->title,
            'department' => $job->department ?? 'General',
            'required_skills' => $job->required_skills ?? [],
            'seniority_level' => $job->experience_level ?? 'Mid-level'
        ];
    }

    protected function getDifficultyGuidelines(string $difficulty): string
    {
        $guidelines = [
            'easy' => "   - Basic concepts, textbook knowledge\n   - Can be answered with foundational understanding\n   - 80%+ candidates at this level should pass\n",
            'medium' => "   - Practical application of skills\n   - Requires real-world problem solving\n   - 50-60% of qualified candidates should pass\n",
            'hard' => "   - Complex scenarios, edge cases\n   - Requires deep understanding and experience\n   - 20-30% of candidates should pass\n",
            'expert' => "   - Cutting-edge knowledge, novel problems\n   - Requires expertise and advanced thinking\n   - Only top 10% should pass\n"
        ];

        return $guidelines[$difficulty] ?? '';
    }

    protected function getTypeGuidelines(string $type): string
    {
        $guidelines = [
            'comprehensive' => "   - Mix of technical, behavioral, and problem-solving questions\n",
            'technical' => "   - Focus on coding, system design, technical knowledge\n",
            'behavioral' => "   - Situational questions, leadership scenarios, teamwork\n",
            'case_study' => "   - Real-world business problems requiring comprehensive analysis\n"
        ];

        return $guidelines[$type] ?? '';
    }

    protected function generateInstructions(string $type, int $count): string
    {
        return "This is a dynamic adaptive assessment with {$count} questions. " .
               "The difficulty will adjust based on your performance. " .
               "Answer each question to the best of your ability. " .
               "There is no penalty for incorrect answers, but accuracy and speed are both measured. " .
               "Good luck!";
    }
}
