<?php

namespace App\Services\AI;

use App\Models\Job;
use App\Models\User;

class InterviewPrepService extends AIService
{
    /**
     * Generate interview questions for a job
     */
    public function generateQuestions(Job $job, string $difficulty = 'mixed'): array
    {
        $systemPrompt = "You are an expert interviewer and hiring manager who creates realistic, relevant interview questions.";

        $prompt = <<<PROMPT
Generate interview questions for this position:

Job Title: {$job->title}
Company: {$job->company->name}
Description: {$job->description}
Experience Level: {$job->experience_level}

Difficulty: {$difficulty}

Return JSON:
{
  "behavioral_questions": [
    {
      "question": "Question text",
      "category": "teamwork/leadership/conflict/adaptability",
      "difficulty": "easy/medium/hard",
      "why_asked": "What the interviewer is looking for",
      "star_framework_tips": "How to structure the answer using STAR"
    }
  ],
  "technical_questions": [
    {
      "question": "Question text",
      "topic": "Topic area",
      "difficulty": "easy/medium/hard",
      "key_concepts": ["Concept to mention"]
    }
  ],
  "situational_questions": [
    {
      "question": "Question text",
      "scenario_type": "Type of situation",
      "what_they_assess": "What this question evaluates"
    }
  ],
  "company_culture_questions": [
    {
      "question": "Question text",
      "purpose": "What this reveals about fit"
    }
  ]
}

Generate 5 questions for each category.
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Evaluate a candidate's answer to an interview question
     */
    public function evaluateAnswer(string $question, string $answer, string $questionType = 'behavioral'): array
    {
        $systemPrompt = "You are an expert interview coach providing constructive feedback on interview answers.";

        $prompt = <<<PROMPT
Evaluate this interview answer:

Question: {$question}
Question Type: {$questionType}

Candidate's Answer:
{$answer}

Return JSON:
{
  "score": 85,
  "strengths": ["What was done well"],
  "weaknesses": ["What could be improved"],
  "specific_feedback": "Detailed constructive feedback",
  "star_compliance": {
    "has_situation": true,
    "has_task": true,
    "has_action": true,
    "has_result": false,
    "missing_elements": ["What's missing from STAR"]
  },
  "improved_answer": "Example of how to improve this answer",
  "key_takeaways": ["Main lessons for improvement"],
  "follow_up_likely": "Possible follow-up questions interviewer might ask"
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt, [
            'cache_hours' => 0, // Don't cache evaluations
        ]);
    }

    /**
     * Generate personalized mock interview for user and job
     */
    public function generateMockInterview(User $user, Job $job): array
    {
        $profile = $user->profile;
        
        $systemPrompt = "You are an expert at creating realistic, personalized mock interviews.";

        $userExperience = json_encode($profile->experience ?? []);
        $userSkills = json_encode($profile->skills ?? []);
        
        $prompt = <<<PROMPT
Create a personalized mock interview for this candidate and job:

CANDIDATE:
Name: {$user->name}
Experience: {$userExperience}
Skills: {$userSkills}

JOB:
Title: {$job->title}
Company: {$job->company->name}
Description: {$job->description}
Experience Level: {$job->experience_level}

Return JSON with a complete mock interview flow:
{
  "interview_format": "phone/video/in-person",
  "estimated_duration": "45 minutes",
  "sections": [
    {
      "section_name": "Introduction",
      "duration": "5 minutes",
      "questions": [
        {
          "question": "Tell me about yourself",
          "type": "opening",
          "suggested_answer_approach": "Brief guide on how to answer",
          "time_limit": "2 minutes"
        }
      ]
    },
    {
      "section_name": "Technical/Behavioral Questions",
      "duration": "25 minutes",
      "questions": [
        {
          "question": "Question based on candidate's background",
          "type": "behavioral/technical",
          "why_personalized": "Why this question is relevant to this candidate",
          "suggested_answer_approach": "How to approach this answer"
        }
      ]
    },
    {
      "section_name": "Candidate Questions",
      "duration": "10 minutes",
      "suggested_questions_to_ask": [
        {
          "question": "Question candidate should ask",
          "category": "role/team/culture/growth",
          "why_good": "Why this is a good question to ask"
        }
      ]
    }
  ],
  "preparation_tips": ["Specific tip 1", "Specific tip 2"],
  "common_pitfalls": ["What to avoid based on job and experience level"],
  "success_indicators": ["What success looks like in this interview"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Get tips for answering common interview questions
     */
    public function getAnsweringTips(string $questionType = 'behavioral'): array
    {
        $systemPrompt = "You are an expert interview coach specializing in helping candidates structure compelling answers.";

        $prompt = <<<PROMPT
Provide comprehensive tips for answering {$questionType} interview questions.

Return JSON:
{
  "framework": {
    "name": "STAR/CARL/PAR",
    "steps": [
      {
        "step": "Situation/Context/Problem",
        "description": "What to include",
        "tips": ["Tip 1", "Tip 2"],
        "example": "Example snippet"
      }
    ]
  },
  "dos": ["Best practice 1", "Best practice 2"],
  "donts": ["Common mistake 1", "Common mistake 2"],
  "time_management": {
    "ideal_length": "2-3 minutes",
    "how_to_stay_concise": "Tips for brevity",
    "how_to_elaborate_if_asked": "How to provide more detail"
  },
  "body_language_tips": ["Tip 1", "Tip 2"],
  "example_strong_answers": [
    {
      "question": "Example question",
      "strong_answer": "Full example answer following framework",
      "why_it_works": "What makes this answer effective"
    }
  ]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Generate questions candidate should ask the interviewer
     */
    public function generateCandidateQuestions(Job $job, string $interviewStage = 'first_round'): array
    {
        $systemPrompt = "You are an expert career coach helping candidates prepare thoughtful questions to ask interviewers.";

        $prompt = <<<PROMPT
Generate thoughtful questions a candidate should ask during the interview:

Job: {$job->title} at {$job->company->name}
Interview Stage: {$interviewStage}
Company Size: {$job->company->size}
Industry: {$job->company->industry}

Return JSON:
{
  "about_the_role": [
    {
      "question": "Question about role responsibilities",
      "why_ask": "What you learn from this",
      "follow_ups": ["Possible follow-up questions"]
    }
  ],
  "about_the_team": [
    {
      "question": "Question about team dynamics",
      "why_ask": "What this reveals",
      "red_flags_to_listen_for": ["Warning signs in the answer"]
    }
  ],
  "about_growth": [
    {
      "question": "Question about career development",
      "why_ask": "What this shows about the opportunity"
    }
  ],
  "about_culture": [
    {
      "question": "Question about company culture",
      "why_ask": "What you can infer"
    }
  ],
  "about_challenges": [
    {
      "question": "Question about role challenges",
      "why_ask": "How this helps you decide"
    }
  ],
  "questions_to_avoid": [
    {
      "bad_question": "Question not to ask",
      "why_avoid": "Why this is problematic",
      "better_alternative": "How to ask it better"
    }
  ]
}

Tailor questions to the interview stage - avoid salary/benefits in early rounds.
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Prepare for specific interview format
     */
    public function prepareForFormat(string $format, Job $job): array
    {
        $systemPrompt = "You are an expert at preparing candidates for different interview formats.";

        $prompt = <<<PROMPT
Provide preparation guidance for a {$format} interview:

Job: {$job->title}
Company: {$job->company->name}

Return JSON:
{
  "format_overview": "Description of this interview format",
  "what_to_expect": ["What typically happens"],
  "technical_setup": {
    "requirements": ["What you need (e.g., webcam, whiteboard)"],
    "setup_tips": ["How to prepare your environment"],
    "common_tech_issues": ["Problems and solutions"]
  },
  "specific_tips": ["Tips specific to this format"],
  "practice_exercises": [
    {
      "exercise": "What to practice",
      "how_to_practice": "Step-by-step practice instructions",
      "success_criteria": "How to know you're ready"
    }
  ],
  "common_mistakes": ["Mistakes specific to this format"],
  "how_to_stand_out": ["Ways to excel in this format"],
  "example_scenarios": [
    {
      "scenario": "Common situation in this format",
      "how_to_handle": "Best approach"
    }
  ]
}

Interview formats: phone_screen, video, technical, panel, case_study, presentation, behavioral
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Analyze company for interview prep
     */
    public function analyzeCompany(Job $job): array
    {
        $systemPrompt = "You are an expert at researching companies and preparing candidates for interviews.";

        $prompt = <<<PROMPT
Provide company research and interview prep guidance:

Company: {$job->company->name}
Industry: {$job->company->industry}
Size: {$job->company->size}
About: {$job->company->description}

Return JSON:
{
  "company_overview": "Summary of the company",
  "key_talking_points": [
    {
      "topic": "Company achievement/value/product",
      "why_mention": "How to naturally reference in interview",
      "example_incorporation": "Example of mentioning this in an answer"
    }
  ],
  "company_values": ["Value 1", "Value 2"],
  "how_to_demonstrate_fit": [
    {
      "value": "Company value",
      "how_to_show_alignment": "How to demonstrate you share this value",
      "example_story": "Type of story from your background to share"
    }
  ],
  "recent_news": ["What to research about recent company news"],
  "competitive_landscape": "Understanding of competitors (for context)",
  "culture_insights": "What the culture seems to value",
  "questions_to_research": ["What to look up before interview"],
  "how_to_show_enthusiasm": ["Genuine ways to express interest"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Create post-interview follow-up message
     */
    public function generateFollowUp(User $user, Job $job, array $interviewDetails = []): array
    {
        $systemPrompt = "You are an expert at crafting professional, memorable interview follow-up emails.";

        $interviewerName = $interviewDetails['interviewer_name'] ?? 'the interviewer';
        $interviewDate = $interviewDetails['date'] ?? 'today';
        $keyDiscussionPoints = json_encode($interviewDetails['key_points'] ?? []);
        
        $prompt = <<<PROMPT
Generate a follow-up email after an interview:

Candidate: {$user->name}
Job: {$job->title} at {$job->company->name}
Interviewer: {$interviewerName}
Interview Date: {$interviewDate}
Key Discussion Points: {$keyDiscussionPoints}

Return JSON:
{
  "subject_line": "Email subject",
  "email_body": "Complete email text",
  "key_elements_included": ["Thank you", "Reference to conversation", "Reiterate interest", "Mention next steps"],
  "tone": "professional/warm/enthusiastic",
  "send_timing_recommendation": "When to send (e.g., within 24 hours)",
  "alternative_version": "Shorter/longer alternative if needed"
}

Requirements:
- Send within 24 hours
- Reference specific conversation points
- Reiterate enthusiasm and qualifications
- Keep under 200 words
- Professional but personable tone
- Include subtle call to action
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt, [
            'cache_hours' => 0,
        ]);
    }

    /**
     * Prepare for salary negotiation discussion
     */
    public function prepareSalaryDiscussion(User $user, Job $job): array
    {
        $systemPrompt = "You are an expert salary negotiation coach.";

        $profileExpectedSalary = "{$user->profile->expected_salary_min}-{$user->profile->expected_salary_max}";
        $jobSalaryRange = "{$job->salary_min}-{$job->salary_max}";
        
        $prompt = <<<PROMPT
Prepare salary negotiation guidance:

Candidate's Expected Range: {$profileExpectedSalary}
Job's Salary Range: {$jobSalaryRange}
Job Title: {$job->title}
Experience Level: {$job->experience_level}
Location: {$job->location}

Return JSON:
{
  "range_analysis": {
    "overlap": true/false,
    "candidate_position": "below/within/above market",
    "negotiation_leverage": "low/medium/high",
    "recommendation": "Recommended approach"
  },
  "how_to_answer_salary_question": {
    "if_asked_early": "Script for early in process",
    "if_asked_after_offer": "Script for after offer",
    "deflection_strategies": ["Tactful ways to defer discussion"]
  },
  "negotiation_strategies": [
    {
      "strategy": "Strategy name",
      "when_to_use": "Appropriate situation",
      "script": "Example of what to say"
    }
  ],
  "total_compensation_considerations": [
    {
      "item": "Bonus/equity/benefits/etc.",
      "how_to_evaluate": "How to assess value",
      "negotiation_potential": "high/medium/low"
    }
  ],
  "common_mistakes": ["What to avoid"],
  "power_phrases": ["Effective phrases to use"],
  "walk_away_scenarios": ["When to decline"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }
}
