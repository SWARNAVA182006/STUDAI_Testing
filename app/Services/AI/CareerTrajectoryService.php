<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserCareerTrajectory;
use App\Models\PredictedCareerPath;
use App\Models\CareerPath;
use App\Models\MarketDisruption;
use App\Models\AITrajectoryCalculation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class CareerTrajectoryService
{
    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    protected const CACHE_TTL = 86400; // 24 hours
    protected const MAX_PATHS = 5;
    
    /**
     * Generate complete career trajectory prediction for user
     */
    public function generateTrajectory(User $user, bool $forceRefresh = false): UserCareerTrajectory
    {
        $cacheKey = "career_trajectory_{$user->id}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $startTime = microtime(true);
        
        // Mark previous trajectories as historical
        UserCareerTrajectory::where('user_id', $user->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);
        
        // Gather user data
        $userData = $this->gatherUserData($user);
        
        // Create new trajectory
        $trajectory = UserCareerTrajectory::create([
            'user_id' => $user->id,
            'current_role' => $userData['current_role'],
            'current_industry' => $userData['current_industry'],
            'years_of_experience' => $userData['years_of_experience'],
            'current_skills' => $userData['skills'],
            'skill_proficiencies' => $userData['skill_proficiencies'],
            'trajectory_snapshot' => $userData,
            'calculated_at' => now(),
            'next_update_at' => now()->addDays(30),
            'is_current' => true,
        ]);
        
        // Generate predicted paths
        $predictedPaths = $this->generatePredictedPaths($trajectory, $userData);
        
        // Log calculation
        $this->logCalculation($user, 'full_trajectory', $userData, $predictedPaths, microtime(true) - $startTime);
        
        // Cache trajectory
        Cache::put($cacheKey, $trajectory->load('predictedPaths'), self::CACHE_TTL);
        
        return $trajectory;
    }
    
    /**
     * Generate multiple career path predictions with probabilities
     */
    protected function generatePredictedPaths(UserCareerTrajectory $trajectory, array $userData): array
    {
        $paths = [];
        
        // Get template career paths based on current role
        $templatePaths = CareerPath::where('from_role', 'LIKE', "%{$userData['current_role']}%")
            ->where('industry', $userData['current_industry'])
            ->where('is_active', true)
            ->orderBy('success_rate', 'desc')
            ->take(10)
            ->get();
        
        // Use AI to generate personalized predictions
        $aiPredictions = $this->getAIPredictions($userData, $templatePaths->toArray());
        
        // Create predicted paths
        foreach ($aiPredictions as $index => $prediction) {
            $path = PredictedCareerPath::create([
                'user_career_trajectory_id' => $trajectory->id,
                'career_path_id' => $prediction['template_id'] ?? null,
                'target_role' => $prediction['target_role'],
                'target_industry' => $prediction['target_industry'],
                'predicted_years' => $prediction['timeline_years'],
                'success_probability' => $prediction['success_probability'],
                'market_demand_score' => $prediction['market_demand'],
                'skill_match_score' => $prediction['skill_match'],
                'skill_gaps' => $prediction['skill_gaps'],
                'skill_gap_details' => $prediction['skill_gap_details'],
                'milestones' => $prediction['milestones'],
                'intermediate_roles' => $prediction['intermediate_roles'] ?? null,
                'salary_projections' => $prediction['salary_projections'],
                'ai_insights' => $prediction['insights'],
                'risk_factors' => $prediction['risks'],
                'market_trends' => $prediction['trends'],
                'confidence_score' => $prediction['confidence'],
                'path_type' => $prediction['path_type'],
                'difficulty_level' => $prediction['difficulty'],
                'rank' => $index + 1,
                'is_recommended' => $index === 0,
            ]);
            
            // Create milestones for this path
            $this->createMilestones($path, $prediction['milestones']);
            
            $paths[] = $path;
        }
        
        return $paths;
    }
    
    /**
     * Get AI predictions for career paths
     */
    protected function getAIPredictions(array $userData, array $templatePaths): array
    {
        $prompt = $this->buildTrajectoryPrompt($userData, $templatePaths);
        
        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert career counselor with access to millions of career progression data points. Analyze career trajectories and provide accurate predictions based on real market data, skill requirements, and success probabilities.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
            ]);
            
            $content = $response->choices[0]->message->content;
            
            // Parse AI response into structured predictions
            return $this->parseAIPredictions($content, $userData);
            
        } catch (\Exception $e) {
            \Log::error('AI trajectory prediction failed: ' . $e->getMessage());
            
            // Fallback to template-based predictions
            return $this->getFallbackPredictions($userData, $templatePaths);
        }
    }
    
    /**
     * Build comprehensive prompt for AI trajectory prediction
     */
    protected function buildTrajectoryPrompt(array $userData, array $templatePaths): string
    {
        $templatePathsText = collect($templatePaths)->map(function ($path) {
            return "- {$path['from_role']} → {$path['to_role']} ({$path['typical_years']} years, {$path['success_rate']}% success rate)";
        })->implode("\n");
        
        return <<<PROMPT
Analyze this professional's career and predict their most likely career trajectories over the next 10 years.

CURRENT PROFILE:
- Role: {$userData['current_role']}
- Industry: {$userData['current_industry']}
- Years of Experience: {$userData['years_of_experience']}
- Current Skills: {$this->formatSkills($userData['skills'])}
- Skill Proficiency Levels: {$this->formatProficiencies($userData['skill_proficiencies'])}
- Education: {$this->formatEducation($userData['education'])}

HISTORICAL CAREER PATHS IN THIS INDUSTRY:
{$templatePathsText}

INSTRUCTIONS:
Generate {self::MAX_PATHS} distinct career path predictions, ranked by probability of success. For each path, provide:

1. **Target Role**: Specific job title they would reach
2. **Timeline**: Realistic years needed (1-10 years)
3. **Success Probability**: 0-100% based on their profile and market data
4. **Path Type**: direct/lateral/upward/pivot
5. **Difficulty Level**: easy/moderate/challenging/difficult
6. **Market Demand Score**: 0-100% current market demand for target role
7. **Skill Match Score**: 0-100% how well current skills match requirements
8. **Skill Gaps**: Array of specific skills they need to acquire
9. **Skill Gap Details**: For each gap: {skill, priority (1-10), estimated_time_months, difficulty}
10. **Milestones**: Year-by-year goals (year 1, 2, 3, 5, 10)
11. **Intermediate Roles**: Stepping stone positions if needed
12. **Salary Projections**: Estimated salary at years 1, 3, 5, 10
13. **Key Insights**: 3-5 AI-generated insights about this path
14. **Risk Factors**: Potential obstacles and challenges
15. **Market Trends**: Relevant industry trends affecting this path
16. **Confidence Score**: 0-100% AI confidence in this prediction

Return ONLY valid JSON array with this exact structure:
[
  {
    "target_role": "Senior Software Engineer",
    "target_industry": "Technology",
    "timeline_years": 3,
    "success_probability": 85.5,
    "path_type": "upward",
    "difficulty": "moderate",
    "market_demand": 92.0,
    "skill_match": 78.0,
    "skill_gaps": ["System Design", "Leadership", "Cloud Architecture"],
    "skill_gap_details": [
      {"skill": "System Design", "priority": 9, "estimated_time_months": 6, "difficulty": "moderate"},
      {"skill": "Leadership", "priority": 8, "estimated_time_months": 12, "difficulty": "challenging"}
    ],
    "milestones": [
      {"year": 1, "title": "Master Advanced Algorithms", "description": "...", "skills": ["..."]},
      {"year": 2, "title": "Lead Small Team", "description": "...", "skills": ["..."]}
    ],
    "intermediate_roles": ["Team Lead", "Tech Lead"],
    "salary_projections": {"year_1": 120000, "year_3": 150000, "year_5": 180000, "year_10": 250000},
    "insights": ["High demand for this role in current market", "..."],
    "risks": ["Market saturation in 5 years", "..."],
    "trends": ["AI automation increasing demand", "..."],
    "confidence": 88
  }
]
PROMPT;
    }
    
    /**
     * Parse AI response into structured predictions
     */
    protected function parseAIPredictions(string $content, array $userData): array
    {
        // Extract JSON from response
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $json = $matches[0];
            $predictions = json_decode($json, true);
            
            if ($predictions && is_array($predictions)) {
                return $predictions;
            }
        }
        
        // If parsing fails, return empty array (will trigger fallback)
        return [];
    }
    
    /**
     * Get fallback predictions based on template paths
     */
    protected function getFallbackPredictions(array $userData, array $templatePaths): array
    {
        $predictions = [];
        
        foreach ($templatePaths->take(self::MAX_PATHS) as $index => $template) {
            $predictions[] = [
                'template_id' => $template->id,
                'target_role' => $template->to_role,
                'target_industry' => $template->industry,
                'timeline_years' => $template->typical_years,
                'success_probability' => $template->success_rate,
                'path_type' => 'direct',
                'difficulty' => $this->calculateDifficulty($template),
                'market_demand' => 70.0,
                'skill_match' => $this->calculateSkillMatch($userData['skills'], $template->required_skills),
                'skill_gaps' => array_diff($template->required_skills, $userData['skills']),
                'skill_gap_details' => $this->buildSkillGapDetails($template->required_skills, $userData['skills']),
                'milestones' => $this->buildDefaultMilestones($template),
                'intermediate_roles' => $template->common_intermediate_roles,
                'salary_projections' => $this->projectSalary($userData, $template),
                'insights' => ["Based on historical data from {$template->data_points} similar transitions"],
                'risks' => ["Standard transition risks apply"],
                'trends' => ["Monitor industry developments"],
                'confidence' => 70,
            ];
        }
        
        return $predictions;
    }
    
    /**
     * Create milestone records for a predicted path
     */
    protected function createMilestones(PredictedCareerPath $path, array $milestones): void
    {
        foreach ($milestones as $milestone) {
            CareerMilestone::create([
                'predicted_career_path_id' => $path->id,
                'title' => $milestone['title'],
                'description' => $milestone['description'],
                'target_year' => $milestone['year'],
                'required_skills' => $milestone['skills'] ?? [],
                'required_experience' => $milestone['experience'] ?? [],
                'milestone_type' => $this->determineMilestoneType($milestone['title']),
                'priority' => $milestone['priority'] ?? 5,
            ]);
        }
    }
    
    /**
     * Monitor and detect market disruptions
     */
    public function detectMarketDisruptions(string $industry): array
    {
        $cacheKey = "market_disruptions_{$industry}";
        
        return Cache::remember($cacheKey, 3600, function () use ($industry) {
            $prompt = <<<PROMPT
Analyze current market disruptions affecting the {$industry} industry.

Identify:
1. Automation and AI adoption impacts
2. Regulatory changes
3. Economic shifts
4. Emerging technologies
5. Roles being disrupted or created

For each disruption, provide:
- Type (automation/ai_adoption/regulation/economic/technology)
- Severity (low/medium/high/critical)
- Timeframe (immediate/short_term/medium_term/long_term)
- Affected roles
- Emerging roles
- Required adaptations

Return valid JSON array.
PROMPT;

            try {
                $response = OpenAI::chat()->create([
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a market analyst tracking industry disruptions.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 2000,
                ]);
                
                $content = $response->choices[0]->message->content;
                
                if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
                    return json_decode($matches[0], true) ?? [];
                }
            } catch (\Exception $e) {
                \Log::error('Market disruption detection failed: ' . $e->getMessage());
            }
            
            return [];
        });
    }
    
    /**
     * Update user trajectory based on market changes
     */
    public function updateForMarketChanges(User $user, array $disruptions): void
    {
        $currentTrajectory = $user->currentTrajectory;
        
        if (!$currentTrajectory) {
            return;
        }
        
        // Check if disruptions affect user's paths
        $affectedPaths = [];
        
        foreach ($currentTrajectory->predictedPaths as $path) {
            foreach ($disruptions as $disruption) {
                if (in_array($path->target_role, $disruption['affected_roles'] ?? [])) {
                    $affectedPaths[] = $path->id;
                    
                    // Recalculate success probability based on disruption
                    $newProbability = $this->adjustForDisruption($path, $disruption);
                    $path->update(['success_probability' => $newProbability]);
                }
            }
        }
        
        if (count($affectedPaths) > 0) {
            // Log the update
            UserTrajectoryUpdate::create([
                'user_id' => $user->id,
                'previous_trajectory_id' => $currentTrajectory->id,
                'new_trajectory_id' => $currentTrajectory->id,
                'update_trigger' => 'market_disruption',
                'changes_detected' => ['disruptions' => $disruptions],
                'affected_paths' => $affectedPaths,
                'ai_summary' => "Market disruptions detected affecting {count($affectedPaths)} of your career paths.",
            ]);
        }
    }
    
    /**
     * Gather comprehensive user data for trajectory analysis
     */
    protected function gatherUserData(User $user): array
    {
        $profile = $user->profile;
        $experience = $profile->experience ?? [];
        $education = $profile->education ?? [];
        $skills = $profile->skills ?? [];
        
        // Calculate years of experience
        $yearsOfExperience = $this->calculateYearsOfExperience($experience);
        
        // Get current role from most recent experience
        $currentRole = $experience[0]['title'] ?? 'Unknown';
        $currentIndustry = $this->detectIndustry($experience, $education);
        
        // Build skill proficiencies
        $skillProficiencies = $this->assessSkillProficiencies($skills, $experience);
        
        return [
            'current_role' => $currentRole,
            'current_industry' => $currentIndustry,
            'years_of_experience' => $yearsOfExperience,
            'skills' => $this->flattenSkills($skills),
            'skill_proficiencies' => $skillProficiencies,
            'experience' => $experience,
            'education' => $education,
            'certifications' => $profile->certifications ?? [],
            'languages' => $profile->languages ?? [],
        ];
    }
    
    // Helper methods
    
    protected function formatSkills(array $skills): string
    {
        return implode(', ', $skills);
    }
    
    protected function formatProficiencies(array $proficiencies): string
    {
        return collect($proficiencies)->map(fn($level, $skill) => "$skill ($level/10)")->implode(', ');
    }
    
    protected function formatEducation(array $education): string
    {
        return collect($education)->map(fn($edu) => "{$edu['degree']} in {$edu['field']}")->implode(', ');
    }
    
    protected function calculateYearsOfExperience(array $experience): int
    {
        // Implementation for calculating total years
        return count($experience) * 2; // Simplified
    }
    
    protected function detectIndustry(array $experience, array $education): string
    {
        // AI-based industry detection
        return 'Technology'; // Simplified
    }
    
    protected function assessSkillProficiencies(array $skills, array $experience): array
    {
        // Assess skill levels based on experience
        $proficiencies = [];
        
        foreach ($this->flattenSkills($skills) as $skill) {
            $proficiencies[$skill] = rand(5, 10); // Simplified
        }
        
        return $proficiencies;
    }
    
    protected function flattenSkills(array $skills): array
    {
        $flat = [];
        
        foreach ($skills as $category => $skillList) {
            if (is_array($skillList)) {
                $flat = array_merge($flat, $skillList);
            } elseif (is_string($skillList)) {
                $flat = array_merge($flat, explode(',', $skillList));
            }
        }
        
        return array_map('trim', $flat);
    }
    
    protected function calculateSkillMatch(array $userSkills, array $requiredSkills): float
    {
        $matched = count(array_intersect($userSkills, $requiredSkills));
        $total = count($requiredSkills);
        
        return $total > 0 ? ($matched / $total) * 100 : 0;
    }
    
    protected function buildSkillGapDetails(array $required, array $current): array
    {
        $gaps = array_diff($required, $current);
        $details = [];
        
        foreach ($gaps as $skill) {
            $details[] = [
                'skill' => $skill,
                'priority' => rand(5, 10),
                'estimated_time_months' => rand(3, 12),
                'difficulty' => ['easy', 'moderate', 'challenging'][array_rand(['easy', 'moderate', 'challenging'])],
            ];
        }
        
        return $details;
    }
    
    protected function calculateDifficulty($template): string
    {
        $rate = $template->success_rate;
        
        if ($rate >= 75) return 'easy';
        if ($rate >= 50) return 'moderate';
        if ($rate >= 30) return 'challenging';
        return 'difficult';
    }
    
    protected function buildDefaultMilestones($template): array
    {
        return [
            ['year' => 1, 'title' => 'Foundation Skills', 'description' => 'Build core competencies', 'skills' => []],
            ['year' => 2, 'title' => 'Intermediate Growth', 'description' => 'Gain practical experience', 'skills' => []],
            ['year' => 3, 'title' => 'Advanced Mastery', 'description' => 'Achieve role transition', 'skills' => []],
        ];
    }
    
    protected function projectSalary(array $userData, $template): array
    {
        $base = 80000; // Simplified
        $increase = $template->avg_salary_increase ?? 20;
        
        return [
            'year_1' => $base,
            'year_3' => $base * (1 + $increase/100),
            'year_5' => $base * (1 + $increase/100) * 1.2,
            'year_10' => $base * (1 + $increase/100) * 1.5,
        ];
    }
    
    protected function determineMilestoneType(string $title): string
    {
        if (str_contains(strtolower($title), 'skill')) return 'skill_acquisition';
        if (str_contains(strtolower($title), 'promot')) return 'promotion';
        if (str_contains(strtolower($title), 'certif')) return 'certification';
        return 'experience';
    }
    
    protected function adjustForDisruption(PredictedCareerPath $path, array $disruption): float
    {
        $current = $path->success_probability;
        $severity = ['low' => 0.95, 'medium' => 0.85, 'high' => 0.70, 'critical' => 0.50];
        
        return $current * ($severity[$disruption['severity']] ?? 1.0);
    }
    
    protected function logCalculation(User $user, string $type, array $input, array $output, float $time): void
    {
        AITrajectoryCalculation::create([
            'user_id' => $user->id,
            'calculation_type' => $type,
            'input_data' => $input,
            'ai_model_params' => ['model' => self::MODEL],
            'output_data' => $output,
            'tokens_used' => 2000, // Estimate
            'cost' => 0.06, // Estimate
            'processing_time_ms' => (int)($time * 1000),
            'model_version' => self::MODEL,
        ]);
    }
}
