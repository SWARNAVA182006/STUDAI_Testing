<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillGap;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SkillGapAnalyzerService
{
    use InteractsWithAI;
    private const CACHE_TTL_MARKET_DATA = 86400; // 24 hours
    private const CACHE_TTL_GAP_ANALYSIS = 3600; // 1 hour
    private const CACHE_TTL_INDUSTRY_TRENDS = 604800; // 1 week

    /**
     * Analyze skill gaps for a user by comparing their skills against market demand
     */
    public function analyzeUserSkillGaps(User $user, bool $forceRefresh = false): Collection
    {
        $cacheKey = "skill_gaps_analysis_{$user->id}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Get user's current skills
            $userSkills = $user->skills()->with('validations', 'assessments')->get();
            
            // Get target roles from user profile
            $targetRoles = $user->profile->career_goals['target_roles'] ?? [];
            $targetIndustry = $user->profile->preferences['industry'] ?? 'Technology';
            
            // Analyze what skills are needed for target roles
            $requiredSkillsData = $this->getRequiredSkillsForRoles($targetRoles, $targetIndustry);
            
            // Compare user skills vs required skills
            $gaps = $this->identifyGaps($userSkills, $requiredSkillsData);
            
            // Enrich gaps with market data (salary impact, demand scores)
            $enrichedGaps = $this->enrichGapsWithMarketData($gaps, $targetIndustry);
            
            // Score and rank gaps by priority
            $rankedGaps = $this->rankGapsByPriority($enrichedGaps, $user);
            
            // Persist to database
            $this->persistGaps($user, $rankedGaps);
            
            Cache::put($cacheKey, $rankedGaps, self::CACHE_TTL_GAP_ANALYSIS);
            
            return $rankedGaps;
            
        } catch (\Exception $e) {
            Log::error('Skill gap analysis failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return cached data if available, otherwise return existing gaps from DB
            return Cache::get($cacheKey) ?? $user->skillGaps()->rankedByPriority()->get();
        }
    }

    /**
     * Get required skills for target roles using AI analysis of job market data
     */
    private function getRequiredSkillsForRoles(array $roles, string $industry): array
    {
        if (empty($roles)) {
            $roles = ['Software Developer']; // Default fallback
        }

        $cacheKey = "required_skills_" . md5(implode('_', $roles) . '_' . $industry);
        
        return Cache::remember($cacheKey, self::CACHE_TTL_MARKET_DATA, function() use ($roles, $industry) {
            try {
                $prompt = $this->buildSkillRequirementsPrompt($roles, $industry);
                
                $content = $this->ai(
                    $prompt,
                    'You are an expert career advisor and labor market analyst. Provide detailed, data-driven insights about skill requirements for specific roles.',
                    ['temperature' => 0.3]
                );
                
                return $this->parseSkillRequirements($content);
                
            } catch (\Exception $e) {
                Log::error('Failed to fetch skill requirements from AI', [
                    'roles' => $roles,
                    'industry' => $industry,
                    'error' => $e->getMessage()
                ]);
                
                // Fallback to basic skill list
                return $this->getDefaultSkillsForIndustry($industry);
            }
        });
    }

    /**
     * Build GPT-4 prompt for skill requirements analysis
     */
    private function buildSkillRequirementsPrompt(array $roles, string $industry): string
    {
        $rolesStr = implode(', ', $roles);
        
        return <<<PROMPT
Analyze the current job market for these roles in the {$industry} industry: {$rolesStr}

For each role, provide:
1. **Essential Skills** (must-have technical skills)
2. **Preferred Skills** (nice-to-have, gives competitive advantage)
3. **Emerging Skills** (trending skills with high growth in demand)
4. **Skill Proficiency Levels** (beginner/intermediate/advanced/expert requirement)
5. **Market Demand Score** (0-100, based on how often this skill appears in job postings)
6. **Salary Impact** (estimated annual salary increase for having this skill)
7. **Typical Learning Time** (weeks/months to reach job-ready proficiency)
8. **Prerequisites** (skills you need before learning this skill)

Format your response as JSON with this structure:
{
  "skills": [
    {
      "name": "Python",
      "category": "Programming Language",
      "importance": "essential|preferred|emerging",
      "required_proficiency": "intermediate",
      "market_demand_score": 85,
      "avg_salary_impact": 15000,
      "learning_time_weeks": 12,
      "difficulty": "moderate",
      "prerequisites": ["Basic Programming Concepts"],
      "required_for_roles": ["Data Scientist", "Backend Developer"],
      "trend": "rising|stable|declining"
    }
  ]
}

Be specific, data-driven, and focus on skills relevant for {$industry} professionals.
PROMPT;
    }

    /**
     * Parse AI response into structured skill requirements
     */
    private function parseSkillRequirements(string $aiResponse): array
    {
        try {
            // Extract JSON from response (handles markdown code blocks)
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['skills'])) {
                    return $data['skills'];
                }
            }
            
            throw new \Exception('Invalid JSON response from AI');
            
        } catch (\Exception $e) {
            Log::error('Failed to parse skill requirements', [
                'error' => $e->getMessage(),
                'response' => substr($aiResponse, 0, 500)
            ]);
            
            return [];
        }
    }

    /**
     * Identify gaps between user's current skills and required skills
     */
    private function identifyGaps(Collection $userSkills, array $requiredSkillsData): array
    {
        $gaps = [];
        $userSkillNames = $userSkills->pluck('skill_name')->map(fn($s) => strtolower($s))->toArray();
        
        foreach ($requiredSkillsData as $requiredSkill) {
            $skillName = $requiredSkill['name'];
            $skillNameLower = strtolower($skillName);
            
            // Check if user has this skill
            $userSkill = $userSkills->first(fn($s) => strtolower($s->skill_name) === $skillNameLower);
            
            if (!$userSkill) {
                // Complete gap - user doesn't have this skill at all
                $gaps[] = [
                    'skill_name' => $skillName,
                    'category' => $requiredSkill['category'] ?? 'General',
                    'gap_type' => 'missing',
                    'current_proficiency' => 0,
                    'required_proficiency' => $this->mapProficiencyToScore($requiredSkill['required_proficiency'] ?? 'intermediate'),
                    'gap_severity' => $this->calculateSeverity($requiredSkill),
                    'impact_score' => $requiredSkill['market_demand_score'] ?? 50,
                    'market_demand_score' => $requiredSkill['market_demand_score'] ?? 50,
                    'salary_impact' => $requiredSkill['avg_salary_impact'] ?? 0,
                    'estimated_learning_time_weeks' => $requiredSkill['learning_time_weeks'] ?? 8,
                    'difficulty' => $requiredSkill['difficulty'] ?? 'moderate',
                    'prerequisites' => $requiredSkill['prerequisites'] ?? [],
                    'required_for_roles' => $requiredSkill['required_for_roles'] ?? [],
                    'is_emerging_skill' => ($requiredSkill['importance'] ?? '') === 'emerging',
                    'trend_direction' => $requiredSkill['trend'] ?? 'stable',
                    'ai_reasoning' => $this->generateGapReasoning($requiredSkill, null),
                ];
            } else {
                // Proficiency gap - user has skill but may not be at required level
                $requiredLevel = $this->mapProficiencyToScore($requiredSkill['required_proficiency'] ?? 'intermediate');
                
                if ($userSkill->proficiency_score < $requiredLevel) {
                    $gaps[] = [
                        'skill_name' => $skillName,
                        'category' => $requiredSkill['category'] ?? 'General',
                        'gap_type' => 'proficiency',
                        'current_proficiency' => $userSkill->proficiency_score,
                        'required_proficiency' => $requiredLevel,
                        'gap_severity' => $this->calculateProficiencySeverity($userSkill->proficiency_score, $requiredLevel),
                        'impact_score' => $requiredSkill['market_demand_score'] ?? 50,
                        'market_demand_score' => $requiredSkill['market_demand_score'] ?? 50,
                        'salary_impact' => $requiredSkill['avg_salary_impact'] ?? 0,
                        'estimated_learning_time_weeks' => max(2, ($requiredSkill['learning_time_weeks'] ?? 8) * (($requiredLevel - $userSkill->proficiency_score) / 100)),
                        'difficulty' => $requiredSkill['difficulty'] ?? 'moderate',
                        'prerequisites' => $requiredSkill['prerequisites'] ?? [],
                        'required_for_roles' => $requiredSkill['required_for_roles'] ?? [],
                        'is_emerging_skill' => ($requiredSkill['importance'] ?? '') === 'emerging',
                        'trend_direction' => $requiredSkill['trend'] ?? 'stable',
                        'ai_reasoning' => $this->generateGapReasoning($requiredSkill, $userSkill),
                    ];
                }
            }
        }
        
        return $gaps;
    }

    /**
     * Map proficiency level text to numeric score
     */
    private function mapProficiencyToScore(string $level): int
    {
        return match(strtolower($level)) {
            'expert' => 90,
            'advanced' => 70,
            'intermediate' => 50,
            'beginner' => 30,
            default => 50,
        };
    }

    /**
     * Calculate gap severity based on skill importance
     */
    private function calculateSeverity(array $skillData): string
    {
        $importance = $skillData['importance'] ?? 'preferred';
        $marketDemand = $skillData['market_demand_score'] ?? 50;
        
        if ($importance === 'essential' && $marketDemand >= 80) return 'critical';
        if ($importance === 'essential' || $marketDemand >= 70) return 'high';
        if ($marketDemand >= 50) return 'medium';
        return 'low';
    }

    /**
     * Calculate proficiency gap severity
     */
    private function calculateProficiencySeverity(int $current, int $required): string
    {
        $gap = $required - $current;
        
        if ($gap >= 40) return 'high';
        if ($gap >= 20) return 'medium';
        return 'low';
    }

    /**
     * Generate AI reasoning for why this gap matters
     */
    private function generateGapReasoning(array $skillData, ?UserSkill $userSkill): array
    {
        $gapType = $userSkill ? 'proficiency' : 'missing';
        
        return [
            'why_important' => $this->explainImportance($skillData),
            'career_impact' => $this->explainCareerImpact($skillData),
            'urgency' => $this->calculateUrgency($skillData),
            'gap_type' => $gapType,
            'current_level' => $userSkill ? $userSkill->proficiency_level : 'none',
        ];
    }

    /**
     * Explain why skill is important
     */
    private function explainImportance(array $skillData): string
    {
        $name = $skillData['name'];
        $demand = $skillData['market_demand_score'] ?? 50;
        $roles = $skillData['required_for_roles'] ?? [];
        
        $rolesStr = !empty($roles) ? implode(', ', array_slice($roles, 0, 3)) : 'multiple roles';
        
        if ($demand >= 80) {
            return "{$name} is in very high demand ({$demand}% of job postings) and essential for {$rolesStr}.";
        } elseif ($demand >= 60) {
            return "{$name} is frequently requested ({$demand}% of job postings) for {$rolesStr}.";
        } else {
            return "{$name} is a valuable skill for {$rolesStr} and can differentiate your profile.";
        }
    }

    /**
     * Explain career impact of learning this skill
     */
    private function explainCareerImpact(array $skillData): string
    {
        $salaryImpact = $skillData['avg_salary_impact'] ?? 0;
        
        if ($salaryImpact >= 20000) {
            return "Learning this skill could increase your earning potential by \$" . number_format($salaryImpact) . "+/year.";
        } elseif ($salaryImpact >= 10000) {
            return "This skill can boost your salary by \$" . number_format($salaryImpact) . "/year on average.";
        } else {
            return "While not directly tied to higher salaries, this skill opens doors to new opportunities.";
        }
    }

    /**
     * Calculate urgency score (0-100)
     */
    private function calculateUrgency(array $skillData): int
    {
        $trend = $skillData['trend'] ?? 'stable';
        $importance = $skillData['importance'] ?? 'preferred';
        
        $urgency = 50; // Base urgency
        
        if ($trend === 'rising') $urgency += 20;
        if ($importance === 'essential') $urgency += 30;
        if ($importance === 'emerging') $urgency += 15;
        
        return min(100, $urgency);
    }

    /**
     * Enrich gaps with real-time market data
     */
    private function enrichGapsWithMarketData(array $gaps, string $industry): array
    {
        // In production, integrate with APIs like:
        // - LinkedIn Talent Insights
        // - Indeed Job Trends API
        // - Stack Overflow Developer Survey
        // - GitHub Jobs API
        
        // For now, we'll use the AI-provided data as baseline
        // and could enhance with web scraping or third-party APIs
        
        foreach ($gaps as &$gap) {
            // Calculate trend score based on direction
            $gap['trend_score'] = match($gap['trend_direction']) {
                'rising' => 85,
                'stable' => 50,
                'declining' => 20,
                default => 50,
            };
            
            // Set target completion date based on urgency
            $gap['target_completion_date'] = now()->addWeeks(
                $gap['estimated_learning_time_weeks']
            )->toDateString();
        }
        
        return $gaps;
    }

    /**
     * Rank gaps by priority (impact, demand, urgency)
     */
    private function rankGapsByPriority(array $gaps, User $user): Collection
    {
        // Calculate priority score for each gap
        foreach ($gaps as &$gap) {
            $impactWeight = 0.40;  // 40% weight to market impact
            $demandWeight = 0.30;  // 30% weight to market demand
            $urgencyWeight = 0.30; // 30% weight to urgency
            
            $urgency = $this->calculateUrgencyScore($gap);
            
            $gap['priority_score'] = (
                ($gap['impact_score'] * $impactWeight) +
                ($gap['market_demand_score'] * $demandWeight) +
                ($urgency * $urgencyWeight)
            );
        }
        
        // Sort by priority score (highest first)
        usort($gaps, fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
        
        return collect($gaps);
    }

    /**
     * Calculate urgency score from gap data
     */
    private function calculateUrgencyScore(array $gap): int
    {
        $baseUrgency = $gap['ai_reasoning']['urgency'] ?? 50;
        
        // Increase urgency for emerging skills
        if ($gap['is_emerging_skill']) {
            $baseUrgency += 15;
        }
        
        // Increase urgency for critical severity
        if ($gap['gap_severity'] === 'critical') {
            $baseUrgency += 20;
        }
        
        return min(100, $baseUrgency);
    }

    /**
     * Persist identified gaps to database
     */
    private function persistGaps(User $user, Collection $gaps): void
    {
        // Mark all existing gaps as 'identified' (not actively learning)
        $user->skillGaps()->whereIn('status', ['identified', 'deferred'])->update([
            'status' => 'identified'
        ]);
        
        foreach ($gaps as $gapData) {
            // Check if gap already exists
            $existingGap = $user->skillGaps()
                ->where('skill_name', $gapData['skill_name'])
                ->first();
            
            if ($existingGap) {
                // Update existing gap with fresh data
                $existingGap->update([
                    'impact_score' => $gapData['impact_score'],
                    'market_demand_score' => $gapData['market_demand_score'],
                    'salary_impact' => $gapData['salary_impact'],
                    'trend_score' => $gapData['trend_score'],
                    'trend_direction' => $gapData['trend_direction'],
                    'ai_reasoning' => $gapData['ai_reasoning'],
                ]);
            } else {
                // Create new gap record
                SkillGap::create([
                    'user_id' => $user->id,
                    'skill_name' => $gapData['skill_name'],
                    'category' => $gapData['category'],
                    'gap_type' => $gapData['gap_type'],
                    'current_proficiency' => $gapData['current_proficiency'],
                    'required_proficiency' => $gapData['required_proficiency'],
                    'gap_severity' => $gapData['gap_severity'],
                    'impact_score' => $gapData['impact_score'],
                    'market_demand_score' => $gapData['market_demand_score'],
                    'salary_impact' => $gapData['salary_impact'],
                    'estimated_learning_time_weeks' => $gapData['estimated_learning_time_weeks'],
                    'difficulty' => $gapData['difficulty'],
                    'prerequisites' => $gapData['prerequisites'],
                    'required_for_roles' => $gapData['required_for_roles'],
                    'is_emerging_skill' => $gapData['is_emerging_skill'],
                    'trend_score' => $gapData['trend_score'],
                    'trend_direction' => $gapData['trend_direction'],
                    'target_completion_date' => $gapData['target_completion_date'],
                    'ai_reasoning' => $gapData['ai_reasoning'],
                    'status' => 'identified',
                ]);
            }
        }
    }

    /**
     * Get default skills for industry (fallback when AI fails)
     */
    private function getDefaultSkillsForIndustry(string $industry): array
    {
        $defaults = [
            'Technology' => [
                ['name' => 'Python', 'category' => 'Programming', 'importance' => 'essential', 'required_proficiency' => 'intermediate', 'market_demand_score' => 85, 'avg_salary_impact' => 15000, 'learning_time_weeks' => 12, 'difficulty' => 'moderate'],
                ['name' => 'JavaScript', 'category' => 'Programming', 'importance' => 'essential', 'required_proficiency' => 'intermediate', 'market_demand_score' => 90, 'avg_salary_impact' => 12000, 'learning_time_weeks' => 10, 'difficulty' => 'moderate'],
                ['name' => 'SQL', 'category' => 'Database', 'importance' => 'essential', 'required_proficiency' => 'intermediate', 'market_demand_score' => 75, 'avg_salary_impact' => 10000, 'learning_time_weeks' => 8, 'difficulty' => 'easy'],
            ],
        ];
        
        return $defaults[$industry] ?? $defaults['Technology'];
    }

    /**
     * Track AI API usage for cost monitoring
     */
    private function trackAIUsage(int $totalTokens, int $promptTokens, int $completionTokens): void
    {
        // Implement token tracking logic
        // Store in ai_usage_logs table for cost analysis
        Log::info('AI tokens used', [
            'service' => 'SkillGapAnalyzer',
            'total_tokens' => $totalTokens,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ]);
    }

    /**
     * Get industry trends for emerging skills prediction
     */
    public function getIndustryTrends(string $industry): array
    {
        $cacheKey = "industry_trends_{$industry}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_INDUSTRY_TRENDS, function() use ($industry) {
            try {
                $prompt = <<<PROMPT
Analyze the {$industry} industry and identify emerging technology trends for the next 2-5 years.

Focus on:
1. **Emerging Skills** - New skills gaining traction
2. **Growing Skills** - Existing skills with increasing demand
3. **Declining Skills** - Skills becoming less relevant
4. **Future Predictions** - What skills will be critical in 3-5 years

Provide data-driven insights based on current market trends, technological advancements, and industry reports.

Format as JSON:
{
  "emerging": [{"skill": "Name", "trend_score": 85, "predicted_demand_2025": 90}],
  "growing": [{"skill": "Name", "growth_rate": 25, "current_demand": 70}],
  "declining": [{"skill": "Name", "decline_rate": -15, "current_demand": 40}],
  "future_critical": [{"skill": "Name", "importance_2027": 95, "learning_time_months": 6}]
}
PROMPT;

                $content = $this->ai(
                    $prompt,
                    'You are a technology trend analyst and labor market expert.',
                    ['temperature' => 0.4]
                );
                
                // Parse JSON response
                $jsonStart = strpos($content, '{');
                $jsonEnd = strrpos($content, '}');
                
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $trends = json_decode($jsonStr, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $trends;
                    }
                }
                
                throw new \Exception('Invalid trends response');
                
            } catch (\Exception $e) {
                Log::error('Failed to fetch industry trends', [
                    'industry' => $industry,
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'emerging' => [],
                    'growing' => [],
                    'declining' => [],
                    'future_critical' => []
                ];
            }
        });
    }
}
