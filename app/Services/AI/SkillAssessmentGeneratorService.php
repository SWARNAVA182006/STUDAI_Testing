<?php

namespace App\Services\AI;

use App\Models\UserSkill;
use App\Models\SkillAssessment;
use App\Models\User;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SkillAssessmentGeneratorService
{
    use InteractsWithAI;
    private const CACHE_TTL_ASSESSMENT = 86400; // 24 hours
    
    /**
     * Generate AI-powered skill assessment test
     */
    public function generateAssessment(
        UserSkill $userSkill,
        string $assessmentType = 'multiple_choice',
        string $difficulty = 'intermediate',
        int $questionCount = 20
    ): SkillAssessment {
        try {
            // Generate questions using AI
            $questions = $this->generateQuestions($userSkill->skill_name, $assessmentType, $difficulty, $questionCount);
            
            // Create assessment record
            $assessment = SkillAssessment::create([
                'user_id' => $userSkill->user_id,
                'user_skill_id' => $userSkill->id,
                'skill_name' => $userSkill->skill_name,
                'assessment_type' => $assessmentType,
                'difficulty' => $difficulty,
                'total_questions' => count($questions),
                'passing_score' => $this->calculatePassingScore($difficulty),
                'time_limit_minutes' => $this->calculateTimeLimit($questionCount, $assessmentType),
                'questions' => $questions,
                'status' => 'draft',
            ]);
            
            return $assessment;
            
        } catch (\Exception $e) {
            Log::error('Assessment generation failed', [
                'skill_id' => $userSkill->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate assessment questions using AI
     */
    private function generateQuestions(string $skillName, string $type, string $difficulty, int $count): array
    {
        $cacheKey = "assessment_questions_{$skillName}_{$type}_{$difficulty}_{$count}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_ASSESSMENT, function() use ($skillName, $type, $difficulty, $count) {
            try {
                $prompt = $this->buildQuestionGenerationPrompt($skillName, $type, $difficulty, $count);
                
                $content = $this->ai(
                    $prompt,
                    'You are an expert assessment designer and technical interviewer. Create challenging, fair, and accurate skill assessment questions.',
                    ['temperature' => 0.7]
                );
                
                return $this->parseQuestions($content, $type);
                
            } catch (\Exception $e) {
                Log::error('Question generation failed', [
                    'skill' => $skillName,
                    'error' => $e->getMessage()
                ]);
                
                return $this->generateFallbackQuestions($skillName, $type, $count);
            }
        });
    }

    /**
     * Build AI prompt for question generation
     */
    private function buildQuestionGenerationPrompt(string $skillName, string $type, string $difficulty, int $count): string
    {
        $typeInstructions = $this->getTypeSpecificInstructions($type);
        
        return <<<PROMPT
Generate {$count} high-quality {$type} assessment questions for "{$skillName}" skill at {$difficulty} level.

{$typeInstructions}

Requirements:
- Questions should test practical application, not just theory
- Cover breadth of the skill (concepts, syntax, best practices, troubleshooting)
- Difficulty appropriate for {$difficulty} level
- Clear, unambiguous questions
- No trick questions, but do test edge cases and common pitfalls
- Include realistic scenarios from real-world usage

For each question provide:
1. **Question text** (clear, specific scenario or problem)
2. **Correct answer** (or expected code/solution)
3. **Explanation** (why this is correct, what concept it tests)
4. **Difficulty** (easy, moderate, hard within {$difficulty} level)
5. **Concepts tested** (array of concepts this question evaluates)

Format as JSON:
{{
  "questions": [
    {{
      "question_number": 1,
      "question_text": "What is the output of this Python code?\\n```python\\nprint([i**2 for i in range(5)])\\n```",
      "options": ["[0, 1, 4, 9, 16]", "[1, 4, 9, 16, 25]", "Error", "[0, 1, 2, 3, 4]"],
      "correct_answer": "[0, 1, 4, 9, 16]",
      "explanation": "List comprehension creates list with squares of 0-4. Range(5) generates 0,1,2,3,4.",
      "difficulty": "easy",
      "concepts_tested": ["list comprehension", "range function", "exponentiation"],
      "points": 5
    }}
  ]
}}

Be thorough and ensure questions accurately test {$skillName} proficiency.
PROMPT;
    }

    /**
     * Get type-specific instructions for question generation
     */
    private function getTypeSpecificInstructions(string $type): string
    {
        return match($type) {
            'multiple_choice' => <<<INST
**Multiple Choice Format:**
- Provide question with 4 options (A, B, C, D)
- Only one correct answer
- Distractors (wrong options) should be plausible but clearly incorrect
- Avoid "all of the above" or "none of the above" unless necessary
INST,
            'coding' => <<<INST
**Coding Challenge Format:**
- Provide a programming task with clear requirements
- Include example input/output
- Specify time/space complexity expectations if relevant
- Test cases should cover normal cases, edge cases, and error handling
- Solution should be achievable in 5-15 minutes per question
INST,
            'scenario_based' => <<<INST
**Scenario-Based Format:**
- Present a realistic work scenario or problem
- Ask how to solve it using the skill
- Multiple valid approaches may exist - note all acceptable solutions
- Focus on decision-making, trade-offs, and best practices
INST,
            'project' => <<<INST
**Project Format:**
- Describe a mini-project that demonstrates skill mastery
- Include requirements, constraints, and success criteria
- Should take 1-3 hours to complete
- Evaluates architecture, implementation, and code quality
INST,
            default => 'Generate assessment questions appropriate for this skill.',
        };
    }

    /**
     * Parse AI-generated questions
     */
    private function parseQuestions(string $response, string $type): array
    {
        try {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['questions'])) {
                    return $data['questions'];
                }
            }
            
            throw new \Exception('Invalid JSON in questions');
            
        } catch (\Exception $e) {
            Log::error('Failed to parse questions', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500)
            ]);
            
            return [];
        }
    }

    /**
     * Grade assessment answers using AI
     */
    public function gradeAssessment(SkillAssessment $assessment, array $userAnswers): array
    {
        try {
            $questions = $assessment->questions;
            $results = [];
            $totalPoints = 0;
            $earnedPoints = 0;
            
            foreach ($questions as $index => $question) {
                $userAnswer = $userAnswers[$index] ?? null;
                $questionPoints = $question['points'] ?? 5;
                $totalPoints += $questionPoints;
                
                if ($assessment->assessment_type === 'multiple_choice') {
                    // Simple exact match for multiple choice
                    $isCorrect = $this->compareAnswers($userAnswer, $question['correct_answer']);
                    $pointsEarned = $isCorrect ? $questionPoints : 0;
                    $earnedPoints += $pointsEarned;
                    
                    $results[] = [
                        'question_number' => $index + 1,
                        'user_answer' => $userAnswer,
                        'correct_answer' => $question['correct_answer'],
                        'is_correct' => $isCorrect,
                        'points_earned' => $pointsEarned,
                        'points_possible' => $questionPoints,
                        'feedback' => $isCorrect ? 'Correct!' : $question['explanation'],
                    ];
                    
                } elseif ($assessment->assessment_type === 'coding') {
                    // Use AI to grade coding solutions
                    $gradingResult = $this->gradeCodeAnswer($question, $userAnswer);
                    $pointsEarned = ($gradingResult['score_percentage'] / 100) * $questionPoints;
                    $earnedPoints += $pointsEarned;
                    
                    $results[] = [
                        'question_number' => $index + 1,
                        'user_answer' => $userAnswer,
                        'points_earned' => round($pointsEarned, 2),
                        'points_possible' => $questionPoints,
                        'score_percentage' => $gradingResult['score_percentage'],
                        'feedback' => $gradingResult['feedback'],
                        'strengths' => $gradingResult['strengths'] ?? [],
                        'improvements' => $gradingResult['improvements'] ?? [],
                    ];
                    
                } else {
                    // Scenario-based or project: AI-assisted grading
                    $gradingResult = $this->gradeOpenEndedAnswer($question, $userAnswer);
                    $pointsEarned = ($gradingResult['score_percentage'] / 100) * $questionPoints;
                    $earnedPoints += $pointsEarned;
                    
                    $results[] = [
                        'question_number' => $index + 1,
                        'user_answer' => $userAnswer,
                        'points_earned' => round($pointsEarned, 2),
                        'points_possible' => $questionPoints,
                        'score_percentage' => $gradingResult['score_percentage'],
                        'feedback' => $gradingResult['feedback'],
                    ];
                }
            }
            
            $finalScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
            
            return [
                'total_score' => $finalScore,
                'points_earned' => round($earnedPoints, 2),
                'points_possible' => $totalPoints,
                'question_results' => $results,
                'passed' => $finalScore >= $assessment->passing_score,
            ];
            
        } catch (\Exception $e) {
            Log::error('Assessment grading failed', [
                'assessment_id' => $assessment->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Compare answers for multiple choice
     */
    private function compareAnswers($userAnswer, $correctAnswer): bool
    {
        // Normalize for comparison
        $user = trim(strtolower((string) $userAnswer));
        $correct = trim(strtolower((string) $correctAnswer));
        
        return $user === $correct;
    }

    /**
     * Grade coding answer using AI
     */
    private function gradeCodeAnswer(array $question, ?string $code): array
    {
        if (empty($code)) {
            return [
                'score_percentage' => 0,
                'feedback' => 'No code submitted.',
                'strengths' => [],
                'improvements' => ['Submit a solution'],
            ];
        }

        try {
            $prompt = <<<PROMPT
Grade this coding solution:

**Question:**
{$question['question_text']}

**Expected Solution Approach:**
{$question['explanation']}

**User's Code:**
```
{$code}
```

Evaluate:
1. **Correctness** (40%): Does it solve the problem?
2. **Code Quality** (30%): Clean, readable, follows best practices?
3. **Efficiency** (20%): Time/space complexity reasonable?
4. **Edge Cases** (10%): Handles errors and edge cases?

Provide:
- **Score** (0-100)
- **Feedback** (what's good, what needs work)
- **Strengths** (array of positive aspects)
- **Improvements** (array of specific suggestions)

Format as JSON.
PROMPT;

            $content = $this->ai(
                $prompt,
                'You are a code reviewer and technical interviewer. Grade fairly and provide constructive feedback.',
                ['temperature' => 0.3]
            );
            
            // Parse grading result
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $result = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($result['score'])) {
                    return [
                        'score_percentage' => min(100, max(0, $result['score'])),
                        'feedback' => $result['feedback'] ?? 'Code reviewed.',
                        'strengths' => $result['strengths'] ?? [],
                        'improvements' => $result['improvements'] ?? [],
                    ];
                }
            }
            
            // Fallback if parsing fails
            return [
                'score_percentage' => 50,
                'feedback' => 'Code submitted but could not be fully evaluated. Please review manually.',
                'strengths' => [],
                'improvements' => [],
            ];
            
        } catch (\Exception $e) {
            Log::error('Code grading failed', ['error' => $e->getMessage()]);
            
            return [
                'score_percentage' => 50,
                'feedback' => 'Auto-grading error. Manual review recommended.',
                'strengths' => [],
                'improvements' => [],
            ];
        }
    }

    /**
     * Grade open-ended answer using AI
     */
    private function gradeOpenEndedAnswer(array $question, ?string $answer): array
    {
        if (empty($answer)) {
            return [
                'score_percentage' => 0,
                'feedback' => 'No answer provided.',
            ];
        }

        try {
            $prompt = <<<PROMPT
Grade this answer to a scenario-based question:

**Question:**
{$question['question_text']}

**Key Concepts to Address:**
{$question['explanation']}

**User's Answer:**
{$answer}

Score 0-100 based on:
- Correctness (50%)
- Depth of understanding (30%)
- Practical applicability (20%)

Provide JSON with:
- **score** (0-100)
- **feedback** (constructive evaluation)
PROMPT;

            $content = $this->ai(
                $prompt,
                'You are an assessment grader. Be fair and constructive.',
                ['temperature' => 0.3, 'model' => 'gpt-5-mini']
            );
            
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $result = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'score_percentage' => $result['score'] ?? 50,
                        'feedback' => $result['feedback'] ?? 'Answer reviewed.',
                    ];
                }
            }
            
            return [
                'score_percentage' => 50,
                'feedback' => 'Answer submitted. Manual review recommended.',
            ];
            
        } catch (\Exception $e) {
            Log::error('Open-ended grading failed', ['error' => $e->getMessage()]);
            
            return [
                'score_percentage' => 50,
                'feedback' => 'Auto-grading error. Manual review needed.',
            ];
        }
    }

    /**
     * Calculate passing score based on difficulty
     */
    private function calculatePassingScore(string $difficulty): int
    {
        return match($difficulty) {
            'easy', 'beginner' => 60,
            'moderate', 'intermediate' => 70,
            'challenging', 'advanced' => 75,
            default => 70,
        };
    }

    /**
     * Calculate time limit based on question count and type
     */
    private function calculateTimeLimit(int $questionCount, string $type): int
    {
        $minutesPerQuestion = match($type) {
            'multiple_choice' => 2,
            'coding' => 10,
            'scenario_based' => 5,
            'project' => 60,
            default => 5,
        };
        
        return $questionCount * $minutesPerQuestion;
    }

    /**
     * Generate fallback questions when AI fails
     */
    private function generateFallbackQuestions(string $skillName, string $type, int $count): array
    {
        $questions = [];
        
        for ($i = 1; $i <= min($count, 5); $i++) {
            $questions[] = [
                'question_number' => $i,
                'question_text' => "Question {$i} about {$skillName} (AI generation failed - please create custom questions)",
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_answer' => 'Option A',
                'explanation' => 'Fallback question - manual review needed',
                'difficulty' => 'moderate',
                'concepts_tested' => [$skillName],
                'points' => 5,
            ];
        }
        
        return $questions;
    }

    /**
     * Track AI usage for cost monitoring
     */
    private function trackAIUsage(int $totalTokens): void
    {
        Log::info('AI tokens used', [
            'service' => 'SkillAssessmentGenerator',
            'total_tokens' => $totalTokens,
        ]);
    }
}
