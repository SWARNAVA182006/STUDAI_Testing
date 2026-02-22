<?php

namespace App\Services\Interview;

use App\Models\CompanyInterviewData;
use App\Models\InterviewSession;
use App\Models\InterviewQuestion;
use App\Models\InterviewCoachingTip;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InterviewGenerationService
{
    public function __construct(
        protected OpenAIService $openAIService
    ) {}

    /**
     * Generate a complete interview session with personalized questions
     */
    public function generateInterviewSession(
        int $userId,
        string $companyName,
        string $roleTitle,
        string $interviewType = 'mixed',
        ?int $discoveredJobId = null,
        int $questionCount = 10
    ): InterviewSession {
        // Fetch company data
        $companyData = $this->getCompanyData($companyName, $roleTitle);

        // Create interview session
        $session = InterviewSession::create([
            'user_id' => $userId,
            'discovered_job_id' => $discoveredJobId,
            'company_name' => $companyName,
            'role_title' => $roleTitle,
            'interview_type' => $interviewType,
            'status' => 'in_progress',
            'total_questions' => $questionCount,
            'started_at' => now(),
            'interviewer_style' => $this->predictInterviewerStyle($companyData),
        ]);

        // Generate questions
        $questions = $this->generateQuestions(
            $session,
            $companyData,
            $questionCount,
            $interviewType
        );

        // Generate coaching tips
        $this->generateCoachingTips($session, $companyData);

        return $session->fresh(['questions', 'coachingTips']);
    }

    /**
     * Get or fetch company interview data
     */
    protected function getCompanyData(string $companyName, string $roleTitle): ?CompanyInterviewData
    {
        $cacheKey = "company_interview_data_{$companyName}_{$roleTitle}";

        return Cache::remember($cacheKey, 3600, function () use ($companyName, $roleTitle) {
            return CompanyInterviewData::forCompany($companyName)
                ->forRole($roleTitle)
                ->recent(180)
                ->first();
        });
    }

    /**
     * Generate interview questions based on company data and AI
     */
    protected function generateQuestions(
        InterviewSession $session,
        ?CompanyInterviewData $companyData,
        int $count,
        string $interviewType
    ): array {
        $questions = [];

        // Determine question distribution
        $distribution = $this->getQuestionDistribution($interviewType, $count);

        $order = 1;

        // Generate behavioral questions
        for ($i = 0; $i < $distribution['behavioral']; $i++) {
            $questions[] = $this->generateBehavioralQuestion(
                $session,
                $companyData,
                $order++
            );
        }

        // Generate technical questions
        for ($i = 0; $i < $distribution['technical']; $i++) {
            $questions[] = $this->generateTechnicalQuestion(
                $session,
                $companyData,
                $order++
            );
        }

        // Generate situational questions
        for ($i = 0; $i < $distribution['situational']; $i++) {
            $questions[] = $this->generateSituationalQuestion(
                $session,
                $companyData,
                $order++
            );
        }

        return $questions;
    }

    /**
     * Determine question distribution based on interview type
     */
    protected function getQuestionDistribution(string $type, int $total): array
    {
        return match($type) {
            'technical' => [
                'technical' => (int) ceil($total * 0.7),
                'behavioral' => (int) floor($total * 0.2),
                'situational' => (int) floor($total * 0.1),
            ],
            'behavioral' => [
                'behavioral' => (int) ceil($total * 0.7),
                'situational' => (int) floor($total * 0.2),
                'technical' => (int) floor($total * 0.1),
            ],
            default => [ // mixed
                'behavioral' => (int) ceil($total * 0.4),
                'technical' => (int) ceil($total * 0.4),
                'situational' => (int) floor($total * 0.2),
            ],
        };
    }

    /**
     * Generate a behavioral question using AI
     */
    protected function generateBehavioralQuestion(
        InterviewSession $session,
        ?CompanyInterviewData $companyData,
        int $order
    ): InterviewQuestion {
        $prompt = $this->buildBehavioralQuestionPrompt($session, $companyData);

        $aiResponse = $this->openAIService->generateCompletion($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        $questionData = $this->parseQuestionResponse($aiResponse);

        return InterviewQuestion::create([
            'interview_session_id' => $session->id,
            'question_order' => $order,
            'question_type' => 'behavioral',
            'question_text' => $questionData['question_text'],
            'question_context' => $questionData['context'] ?? null,
            'difficulty_level' => $questionData['difficulty'] ?? 'medium',
            'expected_elements' => $questionData['expected_elements'] ?? [],
            'star_components' => [
                'situation' => 'Describe the context and background',
                'task' => 'Explain what needed to be accomplished',
                'action' => 'Detail the specific actions you took',
                'result' => 'Share the outcomes and what you learned',
            ],
            'ideal_answer_outline' => $questionData['ideal_outline'] ?? null,
            'follow_up_questions' => $questionData['follow_ups'] ?? [],
            'interviewer_notes' => $questionData['interviewer_notes'] ?? [],
            'is_company_specific' => !empty($companyData),
            'company_context' => $questionData['company_context'] ?? null,
        ]);
    }

    /**
     * Generate a technical question
     */
    protected function generateTechnicalQuestion(
        InterviewSession $session,
        ?CompanyInterviewData $companyData,
        int $order
    ): InterviewQuestion {
        $prompt = $this->buildTechnicalQuestionPrompt($session, $companyData);

        $aiResponse = $this->openAIService->generateCompletion($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.6,
        ]);

        $questionData = $this->parseQuestionResponse($aiResponse);

        return InterviewQuestion::create([
            'interview_session_id' => $session->id,
            'question_order' => $order,
            'question_type' => 'technical',
            'question_text' => $questionData['question_text'],
            'question_context' => $questionData['context'] ?? null,
            'difficulty_level' => $questionData['difficulty'] ?? 'medium',
            'expected_elements' => $questionData['expected_elements'] ?? [],
            'ideal_answer_outline' => $questionData['ideal_outline'] ?? null,
            'follow_up_questions' => $questionData['follow_ups'] ?? [],
            'interviewer_notes' => $questionData['interviewer_notes'] ?? [],
            'is_company_specific' => !empty($companyData),
            'company_context' => $questionData['company_context'] ?? null,
        ]);
    }

    /**
     * Generate a situational question
     */
    protected function generateSituationalQuestion(
        InterviewSession $session,
        ?CompanyInterviewData $companyData,
        int $order
    ): InterviewQuestion {
        $prompt = $this->buildSituationalQuestionPrompt($session, $companyData);

        $aiResponse = $this->openAIService->generateCompletion($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        $questionData = $this->parseQuestionResponse($aiResponse);

        return InterviewQuestion::create([
            'interview_session_id' => $session->id,
            'question_order' => $order,
            'question_type' => 'situational',
            'question_text' => $questionData['question_text'],
            'question_context' => $questionData['context'] ?? null,
            'difficulty_level' => $questionData['difficulty'] ?? 'medium',
            'expected_elements' => $questionData['expected_elements'] ?? [],
            'ideal_answer_outline' => $questionData['ideal_outline'] ?? null,
            'follow_up_questions' => $questionData['follow_ups'] ?? [],
            'interviewer_notes' => $questionData['interviewer_notes'] ?? [],
            'is_company_specific' => !empty($companyData),
            'company_context' => $questionData['company_context'] ?? null,
        ]);
    }

    /**
     * Build prompt for behavioral question generation
     */
    protected function buildBehavioralQuestionPrompt(
        InterviewSession $session,
        ?CompanyInterviewData $companyData
    ): string {
        $prompt = "Generate a behavioral interview question for a {$session->role_title} position";
        
        if ($companyData) {
            $prompt .= " at {$session->company_name}";
            
            if ($culturalValues = $companyData->getCulturalKeywords()) {
                $values = implode(', ', array_slice($culturalValues, 0, 3));
                $prompt .= ". The company values: {$values}";
            }
        }

        $prompt .= ".\n\nReturn a JSON object with:\n";
        $prompt .= "- question_text: The behavioral question\n";
        $prompt .= "- difficulty: easy, medium, or hard\n";
        $prompt .= "- expected_elements: Array of key points a good answer should cover\n";
        $prompt .= "- ideal_outline: Brief outline of an ideal answer structure\n";
        $prompt .= "- follow_ups: Array of 2-3 relevant follow-up questions\n";
        $prompt .= "- interviewer_notes: What the interviewer is looking for\n";
        
        if ($companyData) {
            $prompt .= "- company_context: Why this question matters for this specific company\n";
        }

        return $prompt;
    }

    /**
     * Build prompt for technical question generation
     */
    protected function buildTechnicalQuestionPrompt(
        InterviewSession $session,
        ?CompanyInterviewData $companyData
    ): string {
        $prompt = "Generate a technical interview question for a {$session->role_title} position";
        
        if ($companyData && $techTopics = $companyData->getTechnicalTopics()) {
            $topics = implode(', ', array_slice($techTopics, 0, 3));
            $prompt .= ". Focus on: {$topics}";
        }

        $prompt .= ".\n\nReturn a JSON object with the same structure as behavioral questions.";

        return $prompt;
    }

    /**
     * Build prompt for situational question generation
     */
    protected function buildSituationalQuestionPrompt(
        InterviewSession $session,
        ?CompanyInterviewData $companyData
    ): string {
        $prompt = "Generate a situational interview question for a {$session->role_title} position";
        
        if ($companyData) {
            $prompt .= " at {$session->company_name}. Create a realistic scenario this candidate might face.";
        }

        $prompt .= ".\n\nReturn a JSON object with the same structure as behavioral questions.";

        return $prompt;
    }

    /**
     * Parse AI response into structured question data
     */
    protected function parseQuestionResponse(string $aiResponse): array
    {
        try {
            $data = json_decode($aiResponse, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response');
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning('Failed to parse AI question response', [
                'error' => $e->getMessage(),
                'response' => $aiResponse,
            ]);

            // Return fallback structure
            return [
                'question_text' => 'Tell me about a challenging project you worked on.',
                'difficulty' => 'medium',
                'expected_elements' => ['Challenge description', 'Your approach', 'Outcome'],
                'ideal_outline' => 'Use STAR method to structure your answer.',
                'follow_ups' => ['What would you do differently?', 'How did this experience change you?'],
                'interviewer_notes' => ['Problem-solving skills', 'Communication', 'Learning ability'],
            ];
        }
    }

    /**
     * Predict interviewer style based on company data
     */
    protected function predictInterviewerStyle(?CompanyInterviewData $companyData): array
    {
        if (!$companyData) {
            return [
                'approach' => 'standard',
                'focus' => 'balanced',
                'style' => 'conversational',
            ];
        }

        $profiles = $companyData->getInterviewerProfiles();

        if (empty($profiles)) {
            return [
                'approach' => 'company-standard',
                'focus' => 'cultural-fit',
                'style' => 'structured',
            ];
        }

        // Analyze profiles to predict style
        return [
            'approach' => $profiles[0]['approach'] ?? 'structured',
            'focus' => $profiles[0]['focus'] ?? 'technical-and-cultural',
            'style' => $profiles[0]['style'] ?? 'conversational',
            'background' => $profiles[0]['background'] ?? 'experienced-professional',
        ];
    }

    /**
     * Generate coaching tips for the session
     */
    protected function generateCoachingTips(
        InterviewSession $session,
        ?CompanyInterviewData $companyData
    ): InterviewCoachingTip {
        $talkingPoints = [];
        $successStrategies = [];
        $commonMistakes = [];
        $culturalPoints = [];
        $techAreas = [];

        if ($companyData) {
            $talkingPoints = $this->extractTalkingPoints($companyData);
            $successStrategies = $companyData->success_patterns ?? [];
            $culturalPoints = $companyData->cultural_values ?? [];
            $techAreas = $companyData->technical_focus_areas ?? [];
        }

        // Generate AI-powered tips if no company data
        if (empty($talkingPoints)) {
            $talkingPoints = $this->generateAITalkingPoints($session);
        }

        return InterviewCoachingTip::create([
            'interview_session_id' => $session->id,
            'company_name' => $session->company_name,
            'role_title' => $session->role_title,
            'company_talking_points' => $talkingPoints,
            'role_specific_tips' => $this->getRoleSpecificTips($session->role_title),
            'interviewer_insights' => $session->interviewer_style,
            'cultural_alignment_points' => $culturalPoints,
            'technical_prep_areas' => $techAreas,
            'common_mistakes' => $commonMistakes,
            'success_strategies' => $successStrategies,
        ]);
    }

    /**
     * Extract talking points from company data
     */
    protected function extractTalkingPoints(CompanyInterviewData $companyData): array
    {
        $points = [];

        if ($values = $companyData->cultural_values) {
            foreach (array_slice($values, 0, 5) as $value) {
                $points[] = "Emphasize alignment with company value: {$value}";
            }
        }

        return $points;
    }

    /**
     * Generate AI-powered talking points
     */
    protected function generateAITalkingPoints(InterviewSession $session): array
    {
        // Fallback talking points
        return [
            "Research {$session->company_name}'s recent news and achievements",
            "Prepare specific examples that demonstrate relevant skills for {$session->role_title}",
            "Have thoughtful questions ready about the role and team",
            "Practice explaining your career transitions and motivations",
            "Prepare to discuss how your values align with the company culture",
        ];
    }

    /**
     * Get role-specific tips
     */
    protected function getRoleSpecificTips(string $roleTitle): array
    {
        // This could be enhanced with AI or database lookup
        $roleLower = strtolower($roleTitle);

        if (str_contains($roleLower, 'engineer') || str_contains($roleLower, 'developer')) {
            return [
                'Be prepared to write code or discuss technical architectures',
                'Explain your thought process while solving problems',
                'Discuss trade-offs in your technical decisions',
                'Highlight collaboration with cross-functional teams',
            ];
        }

        if (str_contains($roleLower, 'manager') || str_contains($roleLower, 'lead')) {
            return [
                'Prepare examples of team leadership and conflict resolution',
                'Discuss your approach to delegation and empowerment',
                'Highlight strategic thinking and business impact',
                'Show how you develop and mentor team members',
            ];
        }

        return [
            'Use specific examples to demonstrate your skills',
            'Quantify your achievements whenever possible',
            'Show enthusiasm for the role and company',
            'Ask insightful questions about the team and culture',
        ];
    }
}
