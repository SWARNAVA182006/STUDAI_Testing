<?php

namespace App\Services\AI;

use App\Models\User;

class CareerAdvisorService extends AIService
{
    /**
     * Get personalized career path recommendations
     */
    public function getCareerPaths(User $user, array $options = []): array
    {
        $profile = $user->profile;
        $timeHorizon = $options['time_horizon'] ?? '5_years';
        $riskTolerance = $options['risk_tolerance'] ?? 'moderate';
        
        $systemPrompt = "You are an expert career advisor with deep knowledge of career trajectories, industry trends, and professional development.";

        $userSkills = json_encode($profile->skills ?? []);
        $userExperience = json_encode($profile->experience ?? []);
        $userEducation = json_encode($profile->education ?? []);
        
        $prompt = <<<PROMPT
Provide personalized career path recommendations for this professional:

CURRENT PROFILE:
Skills: {$userSkills}
Experience: {$userExperience}
Education: {$userEducation}
Current Role: {$profile->headline}
Years of Experience: {$this->calculateYearsOfExperience($profile)}

PREFERENCES:
Time Horizon: {$timeHorizon}
Risk Tolerance: {$riskTolerance}
Career Goals: {$profile->career_goals}

Return JSON:
{
  "recommended_paths": [
    {
      "path_name": "Career path title",
      "path_type": "linear/lateral/pivot/entrepreneurial",
      "description": "Overview of this path",
      "alignment_score": 85,
      "why_good_fit": "Why this matches the user's profile",
      "steps": [
        {
          "step_number": 1,
          "title": "Target role or milestone",
          "timeline": "6-12 months",
          "requirements": {
            "skills_needed": ["Skill to develop"],
            "experience_needed": "Type of experience to gain",
            "certifications": ["Certification to obtain"]
          },
          "estimated_salary_range": "Range for this step",
          "success_probability": "high/medium/low"
        }
      ],
      "pros": ["Advantage 1", "Advantage 2"],
      "cons": ["Challenge 1", "Challenge 2"],
      "estimated_income_trajectory": {
        "year_1": "Salary range",
        "year_3": "Salary range",
        "year_5": "Salary range"
      },
      "required_effort": "low/medium/high",
      "job_market_outlook": "Demand and growth trends for this path"
    }
  ],
  "skill_development_priorities": [
    {
      "skill": "Skill name",
      "current_level": "beginner/intermediate/advanced",
      "target_level": "Level needed for career goals",
      "importance": "critical/important/beneficial",
      "time_to_develop": "Estimated months",
      "learning_resources": ["Resource type suggestions"]
    }
  ],
  "networking_strategy": {
    "key_connections": ["Type of people to connect with"],
    "communities_to_join": ["Professional communities"],
    "events_to_attend": ["Types of events"],
    "online_presence_tips": ["How to build visibility"]
  },
  "industry_insights": {
    "growing_sectors": ["Sector 1", "Sector 2"],
    "declining_sectors": ["Sector to potentially avoid"],
    "emerging_opportunities": ["New roles or niches"],
    "automation_risk": "Assessment of automation risk for current skills"
  }
}

Provide 3-5 realistic career paths with detailed step-by-step progression.
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Get salary insights for a role
     */
    public function getSalaryInsights(string $jobTitle, array $context = []): array
    {
        $systemPrompt = "You are a compensation analysis expert with comprehensive knowledge of salary trends, geographic variations, and industry standards.";

        $location = $context['location'] ?? 'United States';
        $experienceLevel = $context['experience_level'] ?? 'mid-level';
        $industry = $context['industry'] ?? 'Technology';
        $companySize = $context['company_size'] ?? 'Medium (50-500)';
        
        $prompt = <<<PROMPT
Provide comprehensive salary insights for:

Role: {$jobTitle}
Location: {$location}
Experience Level: {$experienceLevel}
Industry: {$industry}
Company Size: {$companySize}

Return JSON:
{
  "salary_ranges": {
    "p25": "25th percentile",
    "median": "Median salary",
    "p75": "75th percentile",
    "p90": "90th percentile (top performers)"
  },
  "total_compensation_breakdown": {
    "base_salary": "Typical base range",
    "bonus": "Annual bonus range (if typical)",
    "equity": "Equity/stock compensation info",
    "benefits_value": "Estimated value of standard benefits"
  },
  "factors_affecting_salary": [
    {
      "factor": "Experience/skills/location/company/etc.",
      "impact": "How much this affects compensation",
      "how_to_maximize": "How to leverage this factor"
    }
  ],
  "geographic_variations": [
    {
      "location": "City/region",
      "salary_range": "Range for this location",
      "cost_of_living_adjusted": "Adjusted for COL",
      "market_demand": "Demand level in this location"
    }
  ],
  "salary_growth_trajectory": {
    "entry_level": "Range",
    "mid_level": "Range",
    "senior_level": "Range",
    "principal_staff": "Range",
    "years_to_advance": "Typical progression timeline"
  },
  "negotiation_insights": {
    "typical_negotiation_room": "Percentage employers typically budge",
    "leverage_points": ["What gives you negotiating power"],
    "red_flags": ["Signs the offer is below market"],
    "when_to_walk_away": "Threshold for declining"
  },
  "market_trends": {
    "demand_trend": "increasing/stable/decreasing",
    "competition_level": "Competition for these roles",
    "future_outlook": "3-5 year outlook",
    "hot_skills_commanding_premium": ["Skills that increase salary"]
  }
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Analyze career transition feasibility
     */
    public function analyzeCareerTransition(User $user, string $targetRole, ?string $targetIndustry = null): array
    {
        $profile = $user->profile;
        $systemPrompt = "You are a career transition specialist helping professionals navigate career changes.";

        $currentRole = $profile->headline ?? 'Unknown';
        $currentSkills = json_encode($profile->skills ?? []);
        $currentExperience = json_encode($profile->experience ?? []);
        
        $prompt = <<<PROMPT
Analyze the feasibility of this career transition:

CURRENT SITUATION:
Role: {$currentRole}
Skills: {$currentSkills}
Experience: {$currentExperience}
Years of Experience: {$this->calculateYearsOfExperience($profile)}

TARGET:
Role: {$targetRole}
Industry: {$targetIndustry}

Return JSON:
{
  "feasibility_score": 75,
  "feasibility_level": "highly feasible/feasible/challenging/difficult",
  "assessment": "Overall assessment of the transition",
  "transferable_skills": [
    {
      "skill": "Current skill",
      "relevance_to_target": "How it applies",
      "strength": "strong/moderate/weak"
    }
  ],
  "skill_gaps": [
    {
      "skill": "Needed skill",
      "criticality": "critical/important/nice-to-have",
      "how_to_acquire": "Learning path",
      "estimated_time": "Time to proficiency"
    }
  ],
  "experience_gaps": [
    {
      "gap": "Missing experience type",
      "how_to_gain": "Ways to get this experience",
      "alternatives": ["Alternative ways to demonstrate capability"]
    }
  ],
  "transition_strategies": [
    {
      "strategy": "Transition approach",
      "timeline": "Expected duration",
      "steps": ["Step 1", "Step 2", "Step 3"],
      "pros": ["Advantage"],
      "cons": ["Challenge"],
      "success_probability": "Likelihood of success"
    }
  ],
  "income_impact": {
    "short_term": "Immediate financial impact",
    "long_term": "5-year financial outlook",
    "risk_level": "Financial risk assessment"
  },
  "recommended_actions": [
    {
      "action": "Specific action to take",
      "priority": "high/medium/low",
      "timeline": "When to do this",
      "expected_outcome": "What this achieves"
    }
  ],
  "success_stories": "Examples of similar successful transitions",
  "realistic_timeline": "Total time for transition",
  "confidence_level": "Overall confidence in success"
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Get industry trends and insights
     */
    public function getIndustryTrends(string $industry): array
    {
        $systemPrompt = "You are an industry analyst with expertise in market trends, emerging technologies, and workforce dynamics.";

        $prompt = <<<PROMPT
Provide comprehensive industry trends and insights for: {$industry}

Return JSON:
{
  "industry_overview": "Current state of the industry",
  "growth_trends": {
    "overall_growth": "Growing/stable/declining",
    "growth_rate": "Percentage or description",
    "driving_factors": ["What's driving growth/decline"]
  },
  "hot_roles": [
    {
      "role": "In-demand job title",
      "demand_level": "high/medium",
      "salary_trend": "increasing/stable",
      "why_hot": "Reason for high demand",
      "skills_required": ["Key skills"]
    }
  ],
  "emerging_technologies": [
    {
      "technology": "Technology name",
      "adoption_stage": "early/growing/mature",
      "impact_on_jobs": "How it affects employment",
      "skills_to_learn": ["Related skills to develop"]
    }
  ],
  "disruption_factors": [
    {
      "factor": "AI/automation/regulation/etc.",
      "impact": "How it's changing the industry",
      "jobs_at_risk": ["Roles that may be affected"],
      "jobs_created": ["New roles emerging"]
    }
  ],
  "key_players": {
    "top_companies": ["Leading companies"],
    "rising_startups": ["Companies to watch"],
    "market_leaders": ["Dominant players"]
  },
  "geographic_hotspots": [
    {
      "location": "City/region",
      "why_hot": "What makes this location attractive",
      "job_availability": "Level of opportunity"
    }
  ],
  "skill_demand_forecast": [
    {
      "skill": "Skill name",
      "current_demand": "high/medium/low",
      "future_demand": "Projected trend",
      "urgency_to_learn": "How soon to acquire"
    }
  ],
  "challenges_facing_industry": ["Challenge 1", "Challenge 2"],
  "opportunities": ["Opportunity 1", "Opportunity 2"],
  "five_year_outlook": "Predictions for the next 5 years"
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Create personalized professional development plan
     */
    public function createDevelopmentPlan(User $user, array $goals = []): array
    {
        $profile = $user->profile;
        $systemPrompt = "You are a professional development coach creating personalized growth plans.";

        $careerGoals = !empty($goals) ? json_encode($goals) : $profile->career_goals;
        $currentSkills = json_encode($profile->skills ?? []);
        
        $prompt = <<<PROMPT
Create a personalized professional development plan:

CURRENT STATE:
Skills: {$currentSkills}
Role: {$profile->headline}
Experience: {$this->calculateYearsOfExperience($profile)} years

GOALS:
{$careerGoals}

Return JSON:
{
  "development_phases": [
    {
      "phase": "Phase 1: Foundation",
      "duration": "3 months",
      "objectives": ["Objective 1", "Objective 2"],
      "activities": [
        {
          "activity": "Specific activity",
          "type": "course/project/reading/networking/etc.",
          "time_commitment": "Hours per week",
          "resources": ["Specific resource recommendations"],
          "success_metrics": ["How to measure completion/success"]
        }
      ]
    }
  ],
  "skill_roadmap": [
    {
      "skill": "Skill to develop",
      "current_level": "Level",
      "target_level": "Target",
      "learning_path": ["Step 1", "Step 2"],
      "milestones": ["Milestone to track progress"],
      "estimated_completion": "Timeline"
    }
  ],
  "networking_goals": {
    "target_connections": "Number and type of connections",
    "events_to_attend": ["Event types or specific events"],
    "communities": ["Communities to join"],
    "mentorship": "Mentorship strategy"
  },
  "project_ideas": [
    {
      "project": "Project idea",
      "skills_practiced": ["Skills this develops"],
      "visibility_potential": "How this builds your profile",
      "time_required": "Estimated time"
    }
  ],
  "certifications_to_pursue": [
    {
      "certification": "Certification name",
      "relevance": "Why valuable for your goals",
      "difficulty": "Level",
      "time_to_complete": "Timeline",
      "cost": "Approximate cost",
      "roi": "Return on investment assessment"
    }
  ],
  "monthly_goals": [
    {
      "month": "Month 1",
      "primary_focus": "Main area of focus",
      "specific_goals": ["Measurable goal 1"],
      "checkpoint_questions": ["How to assess progress"]
    }
  ],
  "accountability_mechanisms": ["How to stay on track"],
  "potential_obstacles": [
    {
      "obstacle": "Potential challenge",
      "mitigation": "How to overcome or prevent"
    }
  ]
}

Create a 6-12 month development plan with specific, actionable steps.
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Get work-life balance and career wellness advice
     */
    public function getWellnessAdvice(User $user, array $concerns = []): array
    {
        $systemPrompt = "You are a career wellness coach focusing on sustainable career success and work-life integration.";

        $concernsText = !empty($concerns) ? json_encode($concerns) : 'general career wellness';
        $currentRole = $user->profile->headline ?? 'Professional';
        
        $prompt = <<<PROMPT
Provide career wellness and work-life balance guidance:

Current Role: {$currentRole}
Specific Concerns: {$concernsText}

Return JSON:
{
  "wellness_assessment": "Overall assessment based on common patterns",
  "recommendations": [
    {
      "area": "Work-life balance/stress/burnout prevention/etc.",
      "current_state": "Observed patterns",
      "actionable_advice": ["Specific action 1", "Specific action 2"],
      "implementation_tips": "How to put this into practice",
      "expected_benefits": "What improvement to expect"
    }
  ],
  "boundary_setting": {
    "work_boundaries": ["Boundary to establish"],
    "how_to_communicate": ["How to set expectations with employer"],
    "tools_and_techniques": ["Practical boundary-setting methods"]
  },
  "stress_management": {
    "stress_indicators": ["Signs of excessive stress in your career"],
    "coping_strategies": ["Strategy 1", "Strategy 2"],
    "when_to_seek_help": "Red flags requiring professional support"
  },
  "sustainable_productivity": {
    "energy_management": "Tips for managing energy vs. time",
    "prioritization": "How to focus on what matters",
    "delegation": "What to delegate or eliminate"
  },
  "career_satisfaction_factors": [
    {
      "factor": "Autonomy/mastery/purpose/compensation/etc.",
      "current_satisfaction": "high/medium/low",
      "how_to_improve": "Steps to increase satisfaction"
    }
  ],
  "long_term_sustainability": "Advice for a long, fulfilling career",
  "resources": ["Book/course/community recommendations for wellness"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt, [
            'cache_hours' => 0, // Personalized advice shouldn't be cached
        ]);
    }

    /**
     * Calculate years of experience from profile
     */
    protected function calculateYearsOfExperience($profile): float
    {
        if (empty($profile->experience)) {
            return 0;
        }
        
        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $start = \Carbon\Carbon::parse($exp['start_date'] ?? 'now');
            $end = isset($exp['end_date']) && $exp['end_date'] !== 'Present' 
                ? \Carbon\Carbon::parse($exp['end_date'])
                : \Carbon\Carbon::now();
            
            $totalMonths += $start->diffInMonths($end);
        }
        
        return round($totalMonths / 12, 1);
    }
}
