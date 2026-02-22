<?php

namespace App\Services\AI;

use App\Models\CompanyInterviewData;
use App\Models\InterviewQuestion;
use App\Models\InterviewerProfile;
use App\Models\AIInterviewCalculation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class InterviewQuestionGenerator
{
    protected string $model = 'gpt-5.1'; // Azure OpenAI GPT-5.1

    /**
     * Generate questions for a specific company and role
     */
    public function generateForCompanyRole(
        CompanyInterviewData $company,
        string $role,
        int $count = 20,
        ?string $interviewStage = null
    ): array {
        $cacheKey = "interview_questions_{$company->id}_{$role}_{$interviewStage}_{$count}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($company, $role, $count, $interviewStage) {
            try {
                $prompt = $this->buildQuestionGenerationPrompt($company, $role, $count, $interviewStage);

                $startTime = microtime(true);
                $response = OpenAI::chat()->create([
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert interview preparation coach who generates realistic, company-specific interview questions based on deep industry knowledge and company research.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 3000,
                ]);

                $processingTime = (microtime(true) - $startTime) * 1000;
                $questions = $this->parseQuestionResponse($response->choices[0]->message->content);

                // Track AI usage
                AIInterviewCalculation::create([
                    'user_id' => auth()->id(),
                    'calculation_type' => 'question_generation',
                    'input_data' => ['company' => $company->company_name, 'role' => $role, 'count' => $count],
                    'output_data' => ['questions_generated' => count($questions)],
                    'tokens_used' => $response->usage->totalTokens,
                    'cost' => $this->calculateCost($response->usage->totalTokens),
                    'processing_time_ms' => $processingTime,
                    'model_version' => $this->model,
                ]);

                return $questions;

            } catch (\Exception $e) {
                Log::error('Failed to generate interview questions', [
                    'company' => $company->company_name,
                    'role' => $role,
                    'error' => $e->getMessage(),
                ]);

                // Return fallback questions
                return $this->getFallbackQuestions($role, $count);
            }
        });
    }

    /**
     * Generate behavioral questions (STAR methodology)
     */
    public function generateBehavioral(string $role, string $focusArea, int $count = 10): array
    {
        $cacheKey = "behavioral_questions_{$role}_{$focusArea}_{$count}";

        return Cache::remember($cacheKey, now()->addDays(14), function () use ($role, $focusArea, $count) {
            try {
                $prompt = <<<EOT
Generate {$count} behavioral interview questions for a {$role} position focusing on {$focusArea}.

Requirements:
1. All questions must be STAR-applicable (Situation, Task, Action, Result)
2. Questions should start with phrases like "Tell me about a time when..." or "Describe a situation where..."
3. Focus specifically on {$focusArea}
4. Make questions realistic and commonly asked
5. Include a mix of difficulty levels

Return ONLY a JSON array of objects with this structure:
[
    {
        "question": "Tell me about a time when...",
        "category": "{$focusArea}",
        "difficulty": "easy|medium|hard",
        "key_points": ["point1", "point2", "point3"],
        "follow_ups": ["follow-up question 1", "follow-up question 2"]
    }
]
EOT;

                $response = OpenAI::chat()->create([
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert at creating STAR methodology behavioral interview questions.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ]);

                return json_decode($response->choices[0]->message->content, true) ?? [];

            } catch (\Exception $e) {
                Log::error('Failed to generate behavioral questions', ['error' => $e->getMessage()]);
                return $this->getDefaultBehavioralQuestions($focusArea, $count);
            }
        });
    }

    /**
     * Generate technical questions for specific role
     */
    public function generateTechnical(string $role, array $techStack = [], int $count = 15): array
    {
        $cacheKey = "technical_questions_" . md5($role . implode(',', $techStack) . $count);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($role, $techStack, $count) {
            try {
                $techStackStr = !empty($techStack) ? implode(', ', $techStack) : 'general software development';

                $prompt = <<<EOT
Generate {$count} technical interview questions for a {$role} position with focus on: {$techStackStr}

Requirements:
1. Mix of theoretical concepts and practical application
2. Include coding problems, system design, and conceptual questions
3. Range from easy to hard difficulty
4. Include specific evaluation criteria
5. Provide sample answer outlines

Return ONLY a JSON array of objects with this structure:
[
    {
        "question": "Technical question here",
        "type": "coding|system_design|conceptual",
        "difficulty": "easy|medium|hard",
        "topic": "specific technology/concept",
        "evaluation_criteria": ["criteria1", "criteria2"],
        "key_concepts": ["concept1", "concept2"]
    }
]
EOT;

                $response = OpenAI::chat()->create([
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert technical interviewer with deep knowledge across various technology stacks.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.8,
                    'max_tokens' => 3000,
                ]);

                return json_decode($response->choices[0]->message->content, true) ?? [];

            } catch (\Exception $e) {
                Log::error('Failed to generate technical questions', ['error' => $e->getMessage()]);
                return $this->getDefaultTechnicalQuestions($role, $count);
            }
        });
    }

    /**
     * Predict most likely questions for company/role based on historical data
     */
    public function predictLikelyQuestions(CompanyInterviewData $company, string $role, int $count = 10): array
    {
        // Get historical questions for this company
        $historicalQuestions = InterviewQuestion::query()
            ->where('company_interview_data_id', $company->id)
            ->where('is_template', true)
            ->orderByDesc('frequency_score')
            ->limit($count)
            ->get();

        if ($historicalQuestions->count() >= $count) {
            return $historicalQuestions->toArray();
        }

        // Generate additional questions to meet count
        $needed = $count - $historicalQuestions->count();
        $generated = $this->generateForCompanyRole($company, $role, $needed);

        return array_merge($historicalQuestions->toArray(), $generated);
    }

    /**
     * Generate context-aware follow-up questions
     */
    public function generateFollowUp(InterviewQuestion $question, string $userAnswer): array
    {
        try {
            $prompt = <<<EOT
Original Question: {$question->question_text}
User's Answer: {$userAnswer}

Generate 3 relevant follow-up questions that:
1. Dig deeper into specific aspects of their answer
2. Challenge assumptions or probe for more detail
3. Are natural conversation progressions

Return ONLY a JSON array of follow-up question strings.
EOT;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini', // Use faster model for follow-ups
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an experienced interviewer who asks insightful follow-up questions.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]);

            return json_decode($response->choices[0]->message->content, true) ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to generate follow-up questions', ['error' => $e->getMessage()]);
            return $this->getGenericFollowUps();
        }
    }

    /**
     * Build comprehensive prompt for question generation
     */
    protected function buildQuestionGenerationPrompt(
        CompanyInterviewData $company,
        string $role,
        int $count,
        ?string $interviewStage
    ): string {
        $companyInfo = [
            'name' => $company->company_name,
            'industry' => $company->industry,
            'size' => $company->company_size,
            'culture' => $company->company_culture ?? 'Not specified',
            'difficulty' => $company->average_difficulty ?? 5,
        ];

        $stage = $interviewStage ?? 'general interview';

        return <<<EOT
Generate {$count} realistic interview questions for a {$role} position at {$companyInfo['name']} (Interview Stage: {$stage}).

Company Context:
- Industry: {$companyInfo['industry']}
- Company Size: {$companyInfo['size']}
- Culture: {$companyInfo['culture']}
- Typical Difficulty: {$companyInfo['difficulty']}/10

Requirements:
1. Questions must be realistic and commonly asked at similar companies
2. Mix of question types: behavioral (40%), technical (35%), situational (15%), cultural fit (10%)
3. All behavioral questions should be STAR-applicable
4. Include difficulty levels (easy, medium, hard)
5. Provide key points the answer should cover
6. Include 2-3 potential follow-up questions for each
7. Add specific evaluation criteria

Return ONLY a JSON array of objects with this exact structure:
[
    {
        "question": "Question text here",
        "type": "behavioral|technical|case_study|situational|cultural_fit",
        "category": "specific category like 'leadership' or 'problem_solving'",
        "difficulty": "easy|medium|hard",
        "key_points": ["point1", "point2", "point3"],
        "evaluation_criteria": ["criteria1", "criteria2"],
        "follow_ups": ["follow-up 1", "follow-up 2"],
        "typical_time_minutes": 3
    }
]
EOT;
    }

    /**
     * Parse GPT response into structured question array
     */
    protected function parseQuestionResponse(string $content): array
    {
        try {
            // Try to extract JSON from response
            if (preg_match('/\[.*\]/s', $content, $matches)) {
                $questions = json_decode($matches[0], true);
                if (is_array($questions)) {
                    return $questions;
                }
            }

            // Fallback: try direct JSON decode
            $questions = json_decode($content, true);
            return is_array($questions) ? $questions : [];

        } catch (\Exception $e) {
            Log::error('Failed to parse question response', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get fallback questions when AI generation fails
     */
    protected function getFallbackQuestions(string $role, int $count): array
    {
        $fallbacks = [
            [
                'question' => 'Tell me about yourself and your background.',
                'type' => 'behavioral',
                'category' => 'introduction',
                'difficulty' => 'easy',
                'key_points' => ['Professional background', 'Key achievements', 'Career goals'],
            ],
            [
                'question' => 'Why are you interested in this position?',
                'type' => 'cultural_fit',
                'category' => 'motivation',
                'difficulty' => 'easy',
                'key_points' => ['Company research', 'Role alignment', 'Career growth'],
            ],
            [
                'question' => 'Describe a challenging project you worked on and how you overcame obstacles.',
                'type' => 'behavioral',
                'category' => 'problem_solving',
                'difficulty' => 'medium',
                'key_points' => ['Situation', 'Challenge', 'Actions taken', 'Results'],
            ],
            [
                'question' => 'How do you handle conflicts with team members?',
                'type' => 'behavioral',
                'category' => 'teamwork',
                'difficulty' => 'medium',
                'key_points' => ['Communication', 'Empathy', 'Resolution strategy'],
            ],
            [
                'question' => 'What are your greatest strengths and weaknesses?',
                'type' => 'behavioral',
                'category' => 'self_awareness',
                'difficulty' => 'medium',
                'key_points' => ['Honest assessment', 'Examples', 'Improvement plans'],
            ],
        ];

        return array_slice($fallbacks, 0, min($count, count($fallbacks)));
    }

    /**
     * Get default behavioral questions
     */
    protected function getDefaultBehavioralQuestions(string $focusArea, int $count): array
    {
        $defaults = [
            'leadership' => [
                'Tell me about a time when you had to lead a difficult team.',
                'Describe a situation where you had to influence others without authority.',
                'Give an example of how you motivated an underperforming team member.',
            ],
            'problem_solving' => [
                'Describe a complex problem you solved at work.',
                'Tell me about a time when you had to think outside the box.',
                'Give an example of a situation where you identified a process improvement.',
            ],
            'teamwork' => [
                'Tell me about a successful team project you contributed to.',
                'Describe a time when you had to work with a difficult colleague.',
                'Give an example of how you helped resolve a team conflict.',
            ],
        ];

        $questions = $defaults[$focusArea] ?? $defaults['problem_solving'];
        return array_slice($questions, 0, $count);
    }

    /**
     * Get default technical questions
     */
    protected function getDefaultTechnicalQuestions(string $role, int $count): array
    {
        $defaults = [
            'Explain the difference between var, let, and const in JavaScript.',
            'What is the difference between SQL and NoSQL databases?',
            'Describe how you would optimize a slow-performing query.',
            'What are the principles of object-oriented programming?',
            'How does garbage collection work in your preferred language?',
        ];

        return array_slice($defaults, 0, $count);
    }

    /**
     * Get generic follow-up questions
     */
    protected function getGenericFollowUps(): array
    {
        return [
            'Can you elaborate on that?',
            'What was the outcome?',
            'How did others respond to your approach?',
        ];
    }

    /**
     * Calculate API cost based on tokens
     */
    protected function calculateCost(int $tokens): float
    {
        // GPT-4 Turbo pricing: $0.01 per 1K input tokens, $0.03 per 1K output tokens
        // Simplified average: $0.02 per 1K tokens
        return ($tokens / 1000) * 0.02;
    }
}
