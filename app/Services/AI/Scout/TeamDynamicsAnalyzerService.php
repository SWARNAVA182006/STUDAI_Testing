<?php

namespace App\Services\AI\Scout;

use App\Models\Company;
use App\Models\TeamDynamic;
use App\Models\SuccessIndicator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TeamDynamicsAnalyzerService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1

    public function analyzeTeamDynamics(int $companyId, ?string $department = null): array
    {
        $cacheKey = "team_dynamics_{$companyId}" . ($department ? "_{$department}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $department) {
            $company = Company::findOrFail($companyId);
            
            // Gather team data
            $teamData = $this->gatherTeamData($company, $department);
            
            if ($teamData['team_member_count'] < 3) {
                return [
                    'success' => false,
                    'message' => 'Insufficient data: Minimum 3 team members required',
                ];
            }

            try {
                // Analyze with GPT-4
                $collaborationAnalysis = $this->analyzeCollaborationPatterns($teamData);
                $psychologicalSafety = $this->assessPsychologicalSafety($teamData);
                $compatibilityPatterns = $this->identifyCompatibilityPatterns($teamData);
                $idealHireProfile = $this->generateIdealHireProfile($teamData);

                return [
                    'success' => true,
                    'collaboration_analysis' => $collaborationAnalysis,
                    'psychological_safety' => $psychologicalSafety,
                    'compatibility_patterns' => $compatibilityPatterns,
                    'ideal_hire_profile' => $idealHireProfile,
                    'team_health_score' => $this->calculateTeamHealthScore($collaborationAnalysis, $psychologicalSafety),
                    'team_member_count' => $teamData['team_member_count'],
                ];

            } catch (\Exception $e) {
                Log::error('Team Dynamics Analysis Failed', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Analysis failed: ' . $e->getMessage(),
                ];
            }
        });
    }

    private function gatherTeamData(Company $company, ?string $department): array
    {
        $teamMembers = SuccessIndicator::where('company_id', $company->id);
        
        if ($department) {
            // Filter by department if specified (assuming department is stored somewhere)
            // For now, we'll use all team members
        }

        $members = $teamMembers->get();

        return [
            'company_name' => $company->name,
            'department' => $department ?? 'All Departments',
            'team_member_count' => $members->count(),
            'team_size_avg' => $members->count(),
            'performance_distribution' => $this->analyzePerformanceDistribution($members),
            'skill_diversity' => $this->analyzeSkillDiversity($members),
            'collaboration_metrics' => $this->extractCollaborationMetrics($members),
            'tenure_distribution' => $this->analyzeTenureDistribution($members),
            'work_style_patterns' => $this->extractWorkStylePatterns($members),
        ];
    }

    private function analyzeCollaborationPatterns(array $teamData): array
    {
        $prompt = <<<PROMPT
Analyze team collaboration patterns and provide insights.

**Team Profile:**
- Department: {$teamData['department']}
- Team Size: {$teamData['team_member_count']}
- Performance Distribution: {$this->formatPerformanceDistribution($teamData['performance_distribution'])}

**Collaboration Metrics:**
{$this->formatCollaborationMetrics($teamData['collaboration_metrics'])}

**Work Style Patterns:**
{$this->formatWorkStylePatterns($teamData['work_style_patterns'])}

Return JSON analysis:
{
  "collaboration_frequency_score": 85,
  "cross_team_collaboration_score": 70,
  "communication_style": "async-first" | "meeting-heavy" | "balanced",
  "collaboration_strengths": ["Strong knowledge sharing", "Cross-functional collaboration"],
  "collaboration_challenges": ["Timezone differences", "Meeting overload"],
  "recommended_practices": ["Daily standups", "Async updates in Slack"]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an organizational psychology expert specializing in team collaboration.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1200,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function assessPsychologicalSafety(array $teamData): array
    {
        $prompt = <<<PROMPT
Assess psychological safety indicators for this team.

**Team Metrics:**
- Size: {$teamData['team_member_count']}
- Performance: {$this->formatPerformanceDistribution($teamData['performance_distribution'])}
- Tenure: {$this->formatTenureDistribution($teamData['tenure_distribution'])}
- Collaboration: {$this->formatCollaborationMetrics($teamData['collaboration_metrics'])}

Based on high collaboration scores, knowledge sharing, and performance distribution, assess:

Return JSON:
{
  "psychological_safety_score": 85,
  "trust_level": 90,
  "openness_to_feedback_score": 80,
  "has_healthy_conflict": true,
  "safety_indicators": ["High knowledge sharing", "Open communication patterns"],
  "risk_factors": ["New team composition", "Rapid growth"],
  "recommendations": ["Regular team retrospectives", "Anonymous feedback channels"]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a team psychology expert specializing in psychological safety assessment.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function identifyCompatibilityPatterns(array $teamData): array
    {
        $prompt = <<<PROMPT
Identify what personality traits, work styles, and skills work well together in this team.

**Team Composition:**
- Size: {$teamData['team_member_count']}
- Skill Diversity: {$this->formatSkillDiversity($teamData['skill_diversity'])}
- Work Styles: {$this->formatWorkStylePatterns($teamData['work_style_patterns'])}

Return JSON with compatibility insights:
{
  "successful_combinations": [
    {"combination": "Technical depth + Design thinking", "outcome": "Innovative products"},
    {"combination": "Introvert + Extrovert balance", "outcome": "Balanced communication"}
  ],
  "personality_balance": {
    "current_balance": "70% analytical, 30% creative",
    "ideal_balance": "60% analytical, 40% creative"
  },
  "skill_complementarity": ["Backend + Frontend balance", "Strategy + Execution mix"],
  "cultural_fit_patterns": ["Values autonomy", "Prefers written communication"]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a team composition expert analyzing compatibility patterns.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function generateIdealHireProfile(array $teamData): array
    {
        $prompt = <<<PROMPT
Based on current team composition, recommend ideal traits for next hire.

**Current Team:**
- Size: {$teamData['team_member_count']}
- Skills: {$this->formatSkillDiversity($teamData['skill_diversity'])}
- Performance: {$this->formatPerformanceDistribution($teamData['performance_distribution'])}
- Work Styles: {$this->formatWorkStylePatterns($teamData['work_style_patterns'])}

Return JSON with ideal new hire profile:
{
  "ideal_traits": ["Self-directed", "Collaborative", "Detail-oriented"],
  "skill_gaps_to_fill": ["DevOps expertise", "Data analysis"],
  "personality_balance_needed": ["Add creative thinking", "Strengthen strategic planning"],
  "cultural_additions": ["Bring different industry perspective", "Remote work experience"],
  "work_style_fit": "Comfortable with async communication, values autonomy",
  "integration_tips": ["Pair with senior mentor", "Start with smaller projects"]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a team building expert recommending ideal candidate profiles.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    public function assessCandidateTeamFit(int $companyId, array $candidateProfile, ?string $department = null): array
    {
        $teamAnalysis = $this->analyzeTeamDynamics($companyId, $department);
        
        if (!$teamAnalysis['success']) {
            return $teamAnalysis;
        }

        // Pre-format complex expressions for heredoc
        $commStyle = $teamAnalysis['collaboration_analysis']['communication_style'] ?? 'Unknown';
        $psychSafety = $teamAnalysis['psychological_safety']['psychological_safety_score'] ?? 0;
        $idealProfile = $this->formatIdealProfile($teamAnalysis['ideal_hire_profile']);
        $candidateSkills = $this->formatArray($candidateProfile['skills'] ?? []);
        $candidateWorkStyle = $candidateProfile['work_style'] ?? 'Unknown';
        $yearsExp = $candidateProfile['years_experience'] ?? 0;
        $candidateTraits = $this->formatArray($candidateProfile['traits'] ?? []);

        $prompt = <<<PROMPT
Assess how well this candidate fits the team based on team dynamics.

**Team Profile:**
- Size: {$teamAnalysis['team_member_count']}
- Health Score: {$teamAnalysis['team_health_score']}/100
- Collaboration Style: {$commStyle}
- Psychological Safety: {$psychSafety}/100

**Ideal Hire Profile:**
{$idealProfile}

**Candidate Profile:**
- Skills: {$candidateSkills}
- Work Style: {$candidateWorkStyle}
- Experience: {$yearsExp} years
- Traits: {$candidateTraits}

Return JSON with fit assessment:
{
  "team_fit_score": 85,
  "fit_level": "Strong Fit" | "Moderate Fit" | "Weak Fit",
  "strengths": ["Fills critical skill gap", "Matches work style"],
  "concerns": ["Limited remote experience", "May need autonomy coaching"],
  "integration_prediction": "6-8 weeks to full productivity",
  "onboarding_recommendations": ["Assign mentor", "Schedule weekly 1:1s"]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a team integration expert assessing candidate-team compatibility.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        $fitAssessment = json_decode($response->choices[0]->message->content, true) ?? [];

        return array_merge(['success' => true], $fitAssessment);
    }

    private function calculateTeamHealthScore(array $collaboration, array $safety): int
    {
        $collabScore = $collaboration['collaboration_frequency_score'] ?? 50;
        $safetyScore = $safety['psychological_safety_score'] ?? 50;
        $trustScore = $safety['trust_level'] ?? 50;

        return (int) round(
            ($collabScore * 0.35) + 
            ($safetyScore * 0.40) + 
            ($trustScore * 0.25)
        );
    }

    // Helper methods
    private function analyzePerformanceDistribution($members): array
    {
        $distribution = [
            'top_performer' => 0,
            'average' => 0,
            'underperformer' => 0,
        ];

        foreach ($members as $member) {
            $type = $member->employee_type ?? 'average';
            $distribution[$type] = ($distribution[$type] ?? 0) + 1;
        }

        return $distribution;
    }

    private function analyzeSkillDiversity($members): array
    {
        $allSkills = [];
        foreach ($members as $member) {
            $skills = array_merge(
                $member->technical_skills ?? [],
                $member->soft_skills ?? []
            );
            foreach ($skills as $skill) {
                $allSkills[$skill] = ($allSkills[$skill] ?? 0) + 1;
            }
        }
        arsort($allSkills);
        return array_slice($allSkills, 0, 20, true);
    }

    private function extractCollaborationMetrics($members): array
    {
        $scores = [];
        foreach ($members as $member) {
            if ($member->team_collaboration_score) {
                $scores[] = $member->team_collaboration_score;
            }
        }

        return [
            'avg_collaboration_score' => !empty($scores) ? round(array_sum($scores) / count($scores)) : 0,
            'knowledge_sharers' => $members->where('is_knowledge_sharer', true)->count(),
            'mentors' => $members->where('mentorship_activity', '>', 0)->count(),
        ];
    }

    private function analyzeTenureDistribution($members): array
    {
        $tenures = $members->pluck('tenure_months')->filter()->values();
        
        return [
            'avg_tenure' => $tenures->isNotEmpty() ? round($tenures->average(), 1) : 0,
            'min_tenure' => $tenures->isNotEmpty() ? $tenures->min() : 0,
            'max_tenure' => $tenures->isNotEmpty() ? $tenures->max() : 0,
        ];
    }

    private function extractWorkStylePatterns($members): array
    {
        $patterns = [];
        foreach ($members as $member) {
            $preferences = $member->work_preferences ?? [];
            foreach ($preferences as $pref) {
                $patterns[$pref] = ($patterns[$pref] ?? 0) + 1;
            }
        }
        arsort($patterns);
        return $patterns;
    }

    // Formatting helpers
    private function formatPerformanceDistribution(array $dist): string
    {
        $total = array_sum($dist);
        if ($total === 0) return 'No data';

        $lines = [];
        foreach ($dist as $type => $count) {
            $pct = round(($count / $total) * 100);
            $lines[] = ucfirst($type) . ": {$pct}%";
        }
        return implode(', ', $lines);
    }

    private function formatCollaborationMetrics(array $metrics): string
    {
        return sprintf(
            "Avg Collaboration: %d/100, Knowledge Sharers: %d, Mentors: %d",
            $metrics['avg_collaboration_score'],
            $metrics['knowledge_sharers'],
            $metrics['mentors']
        );
    }

    private function formatWorkStylePatterns(array $patterns): string
    {
        if (empty($patterns)) return 'No data';
        
        $top5 = array_slice($patterns, 0, 5, true);
        $formatted = [];
        foreach ($top5 as $pattern => $count) {
            $formatted[] = "{$pattern} ({$count})";
        }
        return implode(', ', $formatted);
    }

    private function formatSkillDiversity(array $skills): string
    {
        if (empty($skills)) return 'No data';
        
        $top8 = array_slice($skills, 0, 8, true);
        $formatted = [];
        foreach ($top8 as $skill => $count) {
            $formatted[] = "{$skill} ({$count})";
        }
        return implode(', ', $formatted);
    }

    private function formatTenureDistribution(array $dist): string
    {
        return sprintf(
            "Avg: %.1f months, Range: %d-%d months",
            $dist['avg_tenure'],
            $dist['min_tenure'],
            $dist['max_tenure']
        );
    }

    private function formatIdealProfile(array $profile): string
    {
        $lines = [];
        if (isset($profile['ideal_traits'])) {
            $lines[] = "Traits: " . implode(', ', $profile['ideal_traits']);
        }
        if (isset($profile['skill_gaps_to_fill'])) {
            $lines[] = "Skills Needed: " . implode(', ', $profile['skill_gaps_to_fill']);
        }
        return implode("\n", $lines) ?: 'No profile data';
    }

    private function formatArray(array $arr): string
    {
        return empty($arr) ? 'None' : implode(', ', $arr);
    }
}
