<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Company;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MockInterviewService
{
    protected $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Generate interview questions for a specific job/role
     */
    public function generateQuestions($jobTitle, $level, ?Company $company = null, $count = 10)
    {
        $cacheKey = "interview_questions_" . md5($jobTitle . $level . ($company?->id ?? ''));
        
        return Cache::remember($cacheKey, 86400, function () use ($jobTitle, $level, $company, $count) {
            $prompt = "Generate {$count} realistic interview questions for a {$level} level {$jobTitle} position";
            
            if ($company) {
                $prompt .= " at {$company->name}";
                if ($company->industry) {
                    $prompt .= " in the {$company->industry} industry";
                }
            }
            
            $prompt .= ".\n\nReturn as JSON with this structure:
{
    \"behavioral\": [
        {\"question\": \"...\", \"category\": \"leadership/teamwork/problem-solving\", \"difficulty\": \"easy/medium/hard\"}
    ],
    \"technical\": [
        {\"question\": \"...\", \"topic\": \"...\", \"difficulty\": \"easy/medium/hard\"}
    ],
    \"situational\": [
        {\"question\": \"...\", \"scenario\": \"...\", \"difficulty\": \"easy/medium/hard\"}
    ]
}";
            
            $systemPrompt = "You are an expert interview coach specializing in technical and behavioral interviews. Generate realistic, relevant questions that test both hard and soft skills.";
            
            try {
                $response = $this->aiService->generateText($prompt, $systemPrompt);
                $questions = json_decode($response, true);
                
                if (!$questions) {
                    return $this->getFallbackQuestions($jobTitle, $level);
                }
                
                return $questions;
            } catch (\Exception $e) {
                Log::error('Failed to generate interview questions: ' . $e->getMessage());
                return $this->getFallbackQuestions($jobTitle, $level);
            }
        });
    }
    
    /**
     * Evaluate an interview answer using AI
     */
    public function evaluateAnswer($question, $answer, $context = [])
    {
        $prompt = "Evaluate this interview answer:\n\n";
        $prompt .= "Question: {$question}\n";
        $prompt .= "Answer: {$answer}\n\n";
        
        if (!empty($context['job_title'])) {
            $prompt .= "Job Role: {$context['job_title']}\n";
        }
        
        if (!empty($context['experience_level'])) {
            $prompt .= "Experience Level: {$context['experience_level']}\n";
        }
        
        $prompt .= "\nProvide feedback as JSON:
{
    \"score\": 0-100,
    \"strengths\": [\"point1\", \"point2\"],
    \"areas_for_improvement\": [\"point1\", \"point2\"],
    \"suggestions\": [\"tip1\", \"tip2\"],
    \"star_method_usage\": {\"situation\": true/false, \"task\": true/false, \"action\": true/false, \"result\": true/false},
    \"overall_feedback\": \"detailed feedback paragraph\"
}";
        
        $systemPrompt = "You are an experienced interview coach. Evaluate answers constructively, focusing on clarity, relevance, use of STAR method for behavioral questions, and concrete examples.";
        
        try {
            $response = $this->aiService->generateText($prompt, $systemPrompt);
            $evaluation = json_decode($response, true);
            
            if (!$evaluation) {
                return $this->getBasicEvaluation($answer);
            }
            
            return $evaluation;
        } catch (\Exception $e) {
            Log::error('Failed to evaluate answer: ' . $e->getMessage());
            return $this->getBasicEvaluation($answer);
        }
    }
    
    /**
     * Format answer using STAR method
     */
    public function formatWithSTAR($rawAnswer)
    {
        $prompt = "Reformat this interview answer using the STAR method (Situation, Task, Action, Result):\n\n{$rawAnswer}\n\n";
        $prompt .= "Return as JSON:
{
    \"situation\": \"context and background\",
    \"task\": \"challenge or responsibility\",
    \"action\": \"what you did\",
    \"result\": \"outcome and impact\",
    \"formatted_answer\": \"complete formatted paragraph\"
}";
        
        $systemPrompt = "You are an interview coach helping candidates structure their answers clearly using the STAR method.";
        
        try {
            $response = $this->aiService->generateText($prompt, $systemPrompt);
            return json_decode($response, true);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get common questions for a role
     */
    public function getCommonQuestions($jobTitle, $limit = 20)
    {
        $cacheKey = "common_questions_" . md5($jobTitle);
        
        return Cache::remember($cacheKey, 86400, function () use ($jobTitle, $limit) {
            // This would ideally pull from a database of frequently asked questions
            // For now, we'll generate with AI
            
            $prompt = "List the {$limit} most commonly asked interview questions for a {$jobTitle} position.";
            $prompt .= "\n\nReturn as JSON array of objects:
[
    {
        \"question\": \"...\",
        \"type\": \"behavioral/technical/situational\",
        \"frequency\": \"very_common/common/occasional\",
        \"tips\": \"brief tip for answering\"
    }
]";
            
            try {
                $response = $this->aiService->generateText($prompt, "You are an interview expert with deep knowledge of hiring practices.");
                return json_decode($response, true) ?? [];
            } catch (\Exception $e) {
                return $this->getGenericCommonQuestions();
            }
        });
    }
    
    /**
     * Get interview tips for specific role
     */
    public function getInterviewTips($jobTitle, $company = null)
    {
        $prompt = "Provide 10 specific interview tips for a candidate interviewing for a {$jobTitle} position";
        
        if ($company) {
            $prompt .= " at {$company->name}";
        }
        
        $prompt .= ".\n\nReturn as JSON array: [{\"tip\": \"...\", \"category\": \"preparation/presentation/technical/behavioral\"}]";
        
        try {
            $response = $this->aiService->generateText($prompt, "You are a career coach specializing in interview preparation.");
            return json_decode($response, true) ?? $this->getGenericTips();
        } catch (\Exception $e) {
            return $this->getGenericTips();
        }
    }
    
    /**
     * Generate salary negotiation script
     */
    public function getSalaryNegotiationGuide($jobTitle, $currentSalary, $targetSalary, $context = [])
    {
        $prompt = "Create a salary negotiation strategy for a {$jobTitle} position.\n";
        $prompt .= "Current/Expected Salary: ₹{$currentSalary}\n";
        $prompt .= "Target Salary: ₹{$targetSalary}\n\n";
        
        if (!empty($context['years_experience'])) {
            $prompt .= "Experience: {$context['years_experience']} years\n";
        }
        
        if (!empty($context['unique_skills'])) {
            $prompt .= "Unique Skills: " . implode(', ', $context['unique_skills']) . "\n";
        }
        
        $prompt .= "\nProvide as JSON:
{
    \"opening_statement\": \"how to broach the topic\",
    \"justification_points\": [\"reason1\", \"reason2\", \"reason3\"],
    \"counter_responses\": [
        {\"objection\": \"...\", \"response\": \"...\"}
    ],
    \"negotiation_tactics\": [\"tactic1\", \"tactic2\"],
    \"alternative_benefits\": [\"benefit1\", \"benefit2\"],
    \"sample_script\": \"complete conversation example\"
}";
        
        try {
            $response = $this->aiService->generateText($prompt, "You are a salary negotiation expert helping professionals maximize their compensation.");
            return json_decode($response, true);
        } catch (\Exception $e) {
            return $this->getGenericNegotiationGuide();
        }
    }
    
    /**
     * Practice session - generate follow-up questions
     */
    public function generateFollowUp($originalQuestion, $userAnswer)
    {
        $prompt = "Based on this interview answer, generate 2-3 relevant follow-up questions:\n\n";
        $prompt .= "Original Question: {$originalQuestion}\n";
        $prompt .= "Candidate's Answer: {$userAnswer}\n\n";
        $prompt .= "Return as JSON array: [{\"question\": \"...\", \"purpose\": \"clarify/probe_deeper/test_knowledge\"}]";
        
        try {
            $response = $this->aiService->generateText($prompt, "You are an interviewer conducting a thorough interview.");
            return json_decode($response, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Fallback questions when AI fails
     */
    protected function getFallbackQuestions($jobTitle, $level)
    {
        return [
            'behavioral' => [
                ['question' => 'Tell me about yourself', 'category' => 'general', 'difficulty' => 'easy'],
                ['question' => 'Describe a challenging project you worked on', 'category' => 'problem-solving', 'difficulty' => 'medium'],
                ['question' => 'How do you handle conflicts with team members?', 'category' => 'teamwork', 'difficulty' => 'medium'],
            ],
            'technical' => [
                ['question' => "What are your key strengths as a {$jobTitle}?", 'topic' => 'skills', 'difficulty' => 'easy'],
                ['question' => 'Explain a technical challenge you solved recently', 'topic' => 'problem-solving', 'difficulty' => 'medium'],
            ],
            'situational' => [
                ['question' => 'How would you handle a tight deadline?', 'scenario' => 'time management', 'difficulty' => 'medium'],
                ['question' => 'What would you do if you disagreed with your manager?', 'scenario' => 'conflict', 'difficulty' => 'hard'],
            ],
        ];
    }
    
    /**
     * Basic evaluation when AI unavailable
     */
    protected function getBasicEvaluation($answer)
    {
        $wordCount = str_word_count($answer);
        $score = min(100, ($wordCount / 100) * 100); // Basic score based on length
        
        return [
            'score' => $score,
            'strengths' => ['Answer provided'],
            'areas_for_improvement' => ['Consider adding more specific examples'],
            'suggestions' => ['Use the STAR method', 'Quantify your achievements'],
            'overall_feedback' => 'Your answer has been recorded. Consider providing more specific examples and concrete results.',
        ];
    }
    
    /**
     * Generic common questions
     */
    protected function getGenericCommonQuestions()
    {
        return [
            ['question' => 'Tell me about yourself', 'type' => 'behavioral', 'frequency' => 'very_common', 'tips' => 'Keep it professional and relevant to the role'],
            ['question' => 'Why do you want to work here?', 'type' => 'behavioral', 'frequency' => 'very_common', 'tips' => 'Research the company beforehand'],
            ['question' => 'What are your strengths and weaknesses?', 'type' => 'behavioral', 'frequency' => 'very_common', 'tips' => 'Be honest but strategic'],
            ['question' => 'Where do you see yourself in 5 years?', 'type' => 'behavioral', 'frequency' => 'common', 'tips' => 'Show ambition aligned with the role'],
            ['question' => 'Why should we hire you?', 'type' => 'behavioral', 'frequency' => 'common', 'tips' => 'Highlight unique value you bring'],
        ];
    }
    
    /**
     * Generic interview tips
     */
    public function getGenericTips()
    {
        return [
            ['tip' => 'Research the company thoroughly before the interview', 'category' => 'preparation'],
            ['tip' => 'Prepare specific examples using the STAR method', 'category' => 'preparation'],
            ['tip' => 'Dress appropriately for the company culture', 'category' => 'presentation'],
            ['tip' => 'Arrive 10-15 minutes early', 'category' => 'presentation'],
            ['tip' => 'Maintain good eye contact and positive body language', 'category' => 'presentation'],
            ['tip' => 'Ask thoughtful questions about the role and company', 'category' => 'behavioral'],
            ['tip' => 'Follow up with a thank-you email within 24 hours', 'category' => 'behavioral'],
        ];
    }
    
    /**
     * Generic negotiation guide
     */
    protected function getGenericNegotiationGuide()
    {
        return [
            'opening_statement' => 'Thank you for the offer. I\'m excited about this opportunity. Before accepting, I\'d like to discuss the compensation package.',
            'justification_points' => [
                'Your years of experience and proven track record',
                'Specialized skills that match the role requirements',
                'Market research showing competitive salaries',
            ],
            'negotiation_tactics' => [
                'Focus on value you bring, not personal needs',
                'Use market data to support your request',
                'Be prepared to walk away if needed',
            ],
        ];
    }
}
