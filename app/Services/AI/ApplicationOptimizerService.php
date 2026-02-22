<?php

namespace App\Services\AI;

use App\Models\Job;
use App\Models\User;
use App\Models\Application;
use Carbon\Carbon;

class ApplicationOptimizerService extends AIService
{
    /**
     * Analyze application and suggest optimizations
     */
    public function analyzeApplication(User $user, Job $job): array
    {
        $profile = $user->profile;
        $systemPrompt = "You are an expert at optimizing job applications for maximum success rate.";

        $userSkills = json_encode($profile->skills ?? []);
        $userExperience = json_encode($profile->experience ?? []);
        $jobRequirements = json_encode($job->requirements ?? []);
        
        $prompt = <<<PROMPT
Analyze this job application and provide optimization suggestions:

CANDIDATE:
Skills: {$userSkills}
Experience: {$userExperience}
Headline: {$profile->headline}

JOB:
Title: {$job->title}
Description: {$job->description}
Requirements: {$jobRequirements}
Company: {$job->company->name}

Return JSON:
{
  "application_strength": 75,
  "match_analysis": {
    "skill_match": 80,
    "experience_match": 70,
    "culture_fit": 75,
    "overall_competitiveness": "strong/moderate/weak"
  },
  "optimization_suggestions": [
    {
      "area": "resume/cover_letter/profile/approach",
      "current_issue": "What needs improvement",
      "suggested_action": "Specific action to take",
      "expected_impact": "high/medium/low",
      "priority": "critical/important/nice-to-have"
    }
  ],
  "resume_customization": {
    "sections_to_emphasize": ["Section name"],
    "keywords_to_add": ["Keyword from job description"],
    "experiences_to_highlight": ["Which experiences to feature prominently"],
    "skills_to_feature": ["Skills to put in spotlight"]
  },
  "cover_letter_guidance": {
    "key_points_to_address": ["Point 1", "Point 2"],
    "company_values_to_reference": ["Value to mention"],
    "tone_recommendation": "professional/enthusiastic/creative",
    "opening_strategy": "How to start the letter",
    "closing_strategy": "How to end the letter"
  },
  "application_timing": {
    "best_time_to_apply": "When to submit for maximum visibility",
    "follow_up_timing": "When to follow up after applying"
  },
  "additional_materials": {
    "portfolio_items_to_include": ["What to showcase"],
    "projects_to_highlight": ["Relevant projects"],
    "references_strategy": "Who to use as references"
  },
  "success_probability": 75,
  "competitor_analysis": "Likely competition for this role",
  "differentiators": ["What makes you stand out"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Track application performance and suggest improvements
     */
    public function analyzeApplicationHistory(User $user, int $daysBack = 90): array
    {
        $applications = Application::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays($daysBack))
            ->with('job')
            ->get();

        if ($applications->isEmpty()) {
            return [
                'message' => 'Not enough application data to analyze',
                'suggestion' => 'Apply to more jobs to get personalized insights',
            ];
        }

        $stats = $this->calculateApplicationStats($applications);
        
        $systemPrompt = "You are an expert at analyzing job search performance and providing data-driven recommendations.";

        $statsJson = json_encode($stats);
        
        $prompt = <<<PROMPT
Analyze this job application performance data and provide actionable insights:

Application Statistics (last {$daysBack} days):
{$statsJson}

Return JSON:
{
  "performance_assessment": "Overall assessment of application strategy",
  "success_rate": {
    "application_to_response": "Percentage getting responses",
    "response_to_interview": "Percentage advancing to interview",
    "interview_to_offer": "Percentage receiving offers",
    "benchmark_comparison": "How this compares to typical rates"
  },
  "patterns_identified": [
    {
      "pattern": "Observed pattern in applications",
      "evidence": "What data shows this",
      "implication": "What this means for strategy"
    }
  ],
  "bottleneck_analysis": {
    "primary_bottleneck": "Where applications are getting stuck",
    "likely_reasons": ["Reason 1", "Reason 2"],
    "solutions": ["How to address this bottleneck"]
  },
  "recommendations": [
    {
      "recommendation": "Specific actionable advice",
      "rationale": "Why this will help",
      "priority": "high/medium/low",
      "expected_improvement": "What to expect if implemented"
    }
  ],
  "application_quality_assessment": {
    "current_quality": "Assessment of application materials",
    "areas_for_improvement": ["Area 1", "Area 2"],
    "quick_wins": ["Easy improvement with high impact"]
  },
  "targeting_advice": {
    "job_types_with_best_response": "Which roles get more traction",
    "companies_to_target": "Company profiles that respond well",
    "roles_to_avoid": "Types that consistently reject"
  },
  "timeline_optimization": {
    "average_time_to_response": "How long it typically takes",
    "best_days_to_apply": "When to submit applications",
    "follow_up_strategy": "Optimal follow-up timing and approach"
  },
  "next_steps": ["Immediate action 1", "Immediate action 2"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Calculate application statistics
     */
    protected function calculateApplicationStats($applications): array
    {
        $total = $applications->count();
        $byStatus = $applications->groupBy('status')->map->count();
        
        $avgResponseTime = $applications
            ->filter(fn($app) => $app->responded_at)
            ->map(fn($app) => $app->created_at->diffInDays($app->responded_at))
            ->avg();

        return [
            'total_applications' => $total,
            'by_status' => $byStatus->toArray(),
            'response_rate' => $byStatus->get('interview', 0) + $byStatus->get('offer', 0),
            'rejection_rate' => $byStatus->get('rejected', 0),
            'pending_rate' => $byStatus->get('pending', 0),
            'average_response_time_days' => round($avgResponseTime ?? 0, 1),
            'applications_by_week' => $this->groupByWeek($applications),
            'job_types_applied' => $this->getJobTypes($applications),
        ];
    }

    /**
     * Group applications by week
     */
    protected function groupByWeek($applications): array
    {
        return $applications->groupBy(function($app) {
            return $app->created_at->format('Y-W');
        })->map->count()->toArray();
    }

    /**
     * Get job types from applications
     */
    protected function getJobTypes($applications): array
    {
        return $applications->pluck('job.title')->countBy()->toArray();
    }

    /**
     * Generate follow-up reminders
     */
    public function getFollowUpReminders(User $user): array
    {
        $applications = Application::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'interview'])
            ->with('job')
            ->get();

        $reminders = [];
        
        foreach ($applications as $app) {
            $daysSinceApplication = $app->created_at->diffInDays(now());
            $daysSinceLastContact = $app->last_contact_at 
                ? $app->last_contact_at->diffInDays(now())
                : $daysSinceApplication;

            if ($this->shouldFollowUp($app, $daysSinceApplication, $daysSinceLastContact)) {
                $reminders[] = [
                    'application_id' => $app->id,
                    'job_title' => $app->job->title,
                    'company' => $app->job->company->name,
                    'status' => $app->status,
                    'days_since_application' => $daysSinceApplication,
                    'days_since_last_contact' => $daysSinceLastContact,
                    'urgency' => $this->calculateUrgency($daysSinceLastContact),
                    'suggested_action' => $this->getSuggestedFollowUpAction($app, $daysSinceLastContact),
                    'message_template' => $this->getFollowUpTemplate($app),
                ];
            }
        }

        return [
            'reminders' => $reminders,
            'total_pending_follow_ups' => count($reminders),
        ];
    }

    /**
     * Determine if follow-up is needed
     */
    protected function shouldFollowUp(Application $app, int $daysSinceApp, int $daysSinceContact): bool
    {
        if ($app->status === 'pending' && $daysSinceContact >= 7) {
            return true;
        }
        
        if ($app->status === 'interview' && $daysSinceContact >= 3) {
            return true;
        }
        
        return false;
    }

    /**
     * Calculate urgency of follow-up
     */
    protected function calculateUrgency(int $daysSinceContact): string
    {
        if ($daysSinceContact >= 14) return 'high';
        if ($daysSinceContact >= 7) return 'medium';
        return 'low';
    }

    /**
     * Get suggested follow-up action
     */
    protected function getSuggestedFollowUpAction(Application $app, int $daysSinceContact): string
    {
        if ($app->status === 'pending') {
            if ($daysSinceContact >= 14) {
                return 'Send a polite inquiry about application status';
            }
            return 'Send a brief follow-up expressing continued interest';
        }
        
        if ($app->status === 'interview') {
            return 'Follow up on interview next steps';
        }
        
        return 'Check application status';
    }

    /**
     * Get follow-up email template
     */
    protected function getFollowUpTemplate(Application $app): string
    {
        $templates = [
            'pending' => "I recently applied for the {job_title} position at {company} and wanted to express my continued interest. I believe my experience in {relevant_skill} would be valuable to your team. Is there any update on the application status?",
            
            'interview' => "Thank you again for the opportunity to interview for the {job_title} position. I remain very excited about the opportunity. Could you provide an update on the next steps in the hiring process?",
        ];

        return $templates[$app->status] ?? $templates['pending'];
    }

    /**
     * Generate personalized follow-up message
     */
    public function generateFollowUpMessage(Application $app, array $context = []): array
    {
        $systemPrompt = "You are an expert at crafting professional, effective follow-up messages for job applications.";

        $daysSinceApplication = $app->created_at->diffInDays(now());
        $previousContact = json_encode($context['previous_contact'] ?? 'Initial application only');
        
        $prompt = <<<PROMPT
Generate a professional follow-up message for this job application:

Job: {$app->job->title} at {$app->job->company->name}
Application Status: {$app->status}
Days Since Application: {$daysSinceApplication}
Previous Contact: {$previousContact}

Return JSON:
{
  "subject_line": "Email subject",
  "message_body": "Complete email message",
  "tone": "professional/friendly/formal",
  "key_elements": ["Element 1: Express continued interest", "Element 2: Reiterate value"],
  "call_to_action": "What you're asking for",
  "send_timing": "Best time to send this message",
  "alternative_shorter_version": "Brief version if needed",
  "linkedin_message_version": "Version for LinkedIn if email not available"
}

Requirements:
- Professional and courteous
- Brief (under 150 words)
- Express continued interest
- Add value or new information if possible
- Include clear but polite call to action
- Respect their time
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt, [
            'cache_hours' => 0,
        ]);
    }

    /**
     * Predict application success probability
     */
    public function predictSuccessProbability(User $user, Job $job): array
    {
        $profile = $user->profile;
        
        // Get user's historical application data
        $historicalSuccess = $this->getHistoricalSuccessRate($user);
        
        // Get match analysis
        $systemPrompt = "You are an expert at predicting job application success based on candidate-job fit analysis.";

        $userSkills = json_encode($profile->skills ?? []);
        $jobRequirements = json_encode($job->requirements ?? []);
        $historicalData = json_encode($historicalSuccess);
        
        $prompt = <<<PROMPT
Predict the probability of success for this job application:

CANDIDATE:
Skills: {$userSkills}
Experience Level: {$this->calculateExperienceLevel($profile)}
Historical Success Rate: {$historicalData}

JOB:
Title: {$job->title}
Requirements: {$jobRequirements}
Experience Level Required: {$job->experience_level}
Applicants: Estimated {$this->estimateCompetition($job)} candidates

Return JSON:
{
  "success_probability": 65,
  "confidence_level": "high/medium/low in this prediction",
  "contributing_factors": [
    {
      "factor": "Skill match/experience/timing/competition/etc.",
      "impact": "positive/negative/neutral",
      "weight": "How much this affects probability",
      "details": "Specific details about this factor"
    }
  ],
  "comparison_to_average": "How this compares to typical applications",
  "strengths_for_this_role": ["Your advantage 1", "Your advantage 2"],
  "weaknesses_for_this_role": ["Challenge 1", "Challenge 2"],
  "recommendations_to_improve_odds": [
    {
      "action": "Specific action",
      "potential_improvement": "How much this could help",
      "effort_required": "How hard this is to do"
    }
  ],
  "alternative_roles_with_higher_probability": [
    {
      "role_type": "Alternative role",
      "success_probability": "Estimated probability",
      "why_better_fit": "Reason this is a better match"
    }
  ]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Get historical success rate
     */
    protected function getHistoricalSuccessRate(User $user): array
    {
        $applications = Application::where('user_id', $user->id)->get();
        
        if ($applications->isEmpty()) {
            return ['rate' => 'No data', 'sample_size' => 0];
        }
        
        $total = $applications->count();
        $successful = $applications->whereIn('status', ['interview', 'offer', 'accepted'])->count();
        
        return [
            'rate' => round(($successful / $total) * 100, 1) . '%',
            'sample_size' => $total,
        ];
    }

    /**
     * Calculate experience level
     */
    protected function calculateExperienceLevel($profile): string
    {
        $years = $this->calculateYearsOfExperience($profile);
        
        if ($years < 2) return 'Entry Level';
        if ($years < 5) return 'Mid Level';
        if ($years < 10) return 'Senior Level';
        return 'Expert Level';
    }

    /**
     * Calculate years of experience
     */
    protected function calculateYearsOfExperience($profile): float
    {
        if (empty($profile->experience)) {
            return 0;
        }
        
        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $start = Carbon::parse($exp['start_date'] ?? 'now');
            $end = isset($exp['end_date']) && $exp['end_date'] !== 'Present' 
                ? Carbon::parse($exp['end_date'])
                : Carbon::now();
            
            $totalMonths += $start->diffInMonths($end);
        }
        
        return round($totalMonths / 12, 1);
    }

    /**
     * Estimate competition for job
     */
    protected function estimateCompetition(Job $job): int
    {
        // Simple estimation based on job characteristics
        $base = 50;
        
        if ($job->work_mode === 'remote') $base += 100;
        if ($job->employment_type === 'full_time') $base += 50;
        if ($job->salary_max > 100000) $base += 75;
        
        return $base;
    }
}
