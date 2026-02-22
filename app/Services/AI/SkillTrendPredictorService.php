<?php

namespace App\Services\AI;

use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SkillTrendPredictorService
{
    use InteractsWithAI;
    private const CACHE_TTL_PREDICTIONS = 2592000; // 30 days
    private const CACHE_TTL_INDUSTRY_ANALYSIS = 604800; // 1 week

    /**
     * Predict skill trends for next 2-5 years in specific industry
     */
    public function predictSkillTrends(string $industry, int $yearsAhead = 5): array
    {
        $cacheKey = "skill_trends_{$industry}_{$yearsAhead}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_PREDICTIONS, function() use ($industry, $yearsAhead) {
            try {
                $prompt = $this->buildTrendPredictionPrompt($industry, $yearsAhead);
                
                $content = $this->ai(
                    $prompt,
                    'You are a technology futurist and labor market analyst specializing in skill trend forecasting.',
                    ['temperature' => 0.5]
                );
                
                return $this->parseTrendPredictions($content);
                
            } catch (\Exception $e) {
                Log::error('Skill trend prediction failed', [
                    'industry' => $industry,
                    'error' => $e->getMessage()
                ]);
                
                return $this->getFallbackTrends($industry);
            }
        });
    }

    /**
     * Build GPT-4 prompt for trend predictions
     */
    private function buildTrendPredictionPrompt(string $industry, int $years): string
    {
        $currentYear = date('Y');
        $targetYear = $currentYear + $years;
        
        return <<<PROMPT
Analyze technology and skill trends in the {$industry} industry from {$currentYear} to {$targetYear}.

Provide detailed predictions based on:
1. Current technological advancements (AI, automation, cloud, blockchain, etc.)
2. Industry reports and market research
3. Emerging job roles and evolving requirements
4. Skills becoming obsolete vs. skills gaining importance
5. Impact of remote work and global talent competition

For each predicted trend, provide:
- **Skill Name**
- **Trend Direction** (emerging, rising, stable, declining, obsolete)
- **Demand Forecast** (0-100 score for {$targetYear})
- **Current Demand** (0-100 score for {$currentYear})
- **Growth Rate** (percentage change)
- **Key Drivers** (what's causing this trend)
- **Salary Impact** (estimated salary increase/decrease)
- **Urgency Score** (0-100: how soon to learn this)
- **Learning Difficulty** (easy, moderate, challenging, advanced)
- **Time to Proficiency** (weeks/months to job-ready level)
- **Related Skills** (complementary skills to learn)
- **Career Paths** (roles this skill leads to)

Format as JSON:
{{
  "predictions": [
    {{
      "skill_name": "Machine Learning Engineering",
      "category": "AI & Data Science",
      "trend_direction": "emerging",
      "demand_forecast_{$targetYear}": 95,
      "current_demand_{$currentYear}": 65,
      "growth_rate_percentage": 46,
      "key_drivers": ["AI adoption", "Automation", "Data-driven decision making"],
      "salary_impact_usd": 35000,
      "urgency_score": 85,
      "difficulty": "advanced",
      "time_to_proficiency_months": 18,
      "prerequisites": ["Python", "Statistics", "Linear Algebra"],
      "related_skills": ["Deep Learning", "MLOps", "Data Engineering"],
      "career_paths": ["ML Engineer", "AI Researcher", "Data Scientist"],
      "reasoning": "Detailed explanation of why this trend is predicted"
    }}
  ],
  "meta": {{
    "industry": "{$industry}",
    "forecast_year": {$targetYear},
    "confidence_level": "high|medium|low",
    "key_industry_shifts": ["Shift 1", "Shift 2", "Shift 3"]
  }}
}}

Be specific, data-driven, and focus on actionable insights for career planning.
PROMPT;
    }

    /**
     * Parse AI trend predictions
     */
    private function parseTrendPredictions(string $aiResponse): array
    {
        try {
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['predictions'])) {
                    return $data;
                }
            }
            
            throw new \Exception('Invalid JSON in trend predictions');
            
        } catch (\Exception $e) {
            Log::error('Failed to parse trend predictions', [
                'error' => $e->getMessage(),
                'response' => substr($aiResponse, 0, 500)
            ]);
            
            return ['predictions' => [], 'meta' => []];
        }
    }

    /**
     * Analyze specific skill's future outlook
     */
    public function analyzeSkillOutlook(string $skillName, string $industry): array
    {
        $cacheKey = "skill_outlook_{$skillName}_{$industry}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_INDUSTRY_ANALYSIS, function() use ($skillName, $industry) {
            try {
                $prompt = <<<PROMPT
Analyze the future outlook for "{$skillName}" in the {$industry} industry.

Provide:
1. **Current Market Status** (demand, saturation, salary range)
2. **5-Year Forecast** (will demand increase/decrease/stabilize)
3. **Obsolescence Risk** (0-100: likelihood this skill becomes outdated)
4. **Evolution Path** (how will this skill evolve)
5. **Complementary Skills** (what to learn alongside this)
6. **Career Longevity** (years this skill will remain relevant)
7. **Automation Threat** (0-100: risk of AI/automation replacing this)
8. **Geographic Hotspots** (regions/countries with highest demand)
9. **Industry Crossover** (other industries where this skill is valuable)
10. **Investment Recommendation** (should someone invest time learning this now?)

Format as JSON with detailed reasoning for each point.
PROMPT;

                $content = $this->ai(
                    $prompt,
                    'You are a career strategist and technology trend analyst.',
                    ['temperature' => 0.4]
                );
                
                return $this->parseSkillOutlook($content);
                
            } catch (\Exception $e) {
                Log::error('Skill outlook analysis failed', [
                    'skill' => $skillName,
                    'error' => $e->getMessage()
                ]);
                
                return $this->getDefaultOutlook($skillName);
            }
        });
    }

    /**
     * Parse skill outlook response
     */
    private function parseSkillOutlook(string $response): array
    {
        try {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                return json_decode($jsonStr, true) ?? [];
            }
            
            return [];
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Identify emerging skills in industry
     */
    public function identifyEmergingSkills(string $industry, int $minTrendScore = 75): array
    {
        $trends = $this->predictSkillTrends($industry, 3); // 3-year horizon for emerging skills
        
        $emergingSkills = array_filter(
            $trends['predictions'] ?? [],
            fn($skill) => 
                ($skill['trend_direction'] === 'emerging' || $skill['trend_direction'] === 'rising') &&
                ($skill['urgency_score'] ?? 0) >= $minTrendScore
        );
        
        // Sort by urgency score
        usort($emergingSkills, fn($a, $b) => ($b['urgency_score'] ?? 0) <=> ($a['urgency_score'] ?? 0));
        
        return array_slice($emergingSkills, 0, 10); // Top 10 emerging skills
    }

    /**
     * Compare skill portfolios (user's skills vs market needs)
     */
    public function compareSkillPortfolio(array $userSkills, string $industry): array
    {
        $marketTrends = $this->predictSkillTrends($industry, 5);
        $predictions = $marketTrends['predictions'] ?? [];
        
        $userSkillNames = array_map('strtolower', array_column($userSkills, 'skill_name'));
        
        $analysis = [
            'future_proof' => [], // Skills user has that will grow
            'at_risk' => [], // Skills user has that are declining
            'missing_emerging' => [], // Hot skills user doesn't have
            'well_positioned' => [], // Skills user has that are stable/rising
        ];
        
        foreach ($predictions as $prediction) {
            $skillName = strtolower($prediction['skill_name']);
            $userHasSkill = in_array($skillName, $userSkillNames);
            $trendDirection = $prediction['trend_direction'];
            
            if ($userHasSkill) {
                if ($trendDirection === 'rising' || $trendDirection === 'emerging') {
                    $analysis['future_proof'][] = $prediction;
                } elseif ($trendDirection === 'declining' || $trendDirection === 'obsolete') {
                    $analysis['at_risk'][] = $prediction;
                } elseif ($trendDirection === 'stable') {
                    $analysis['well_positioned'][] = $prediction;
                }
            } else {
                if ($trendDirection === 'emerging' || $trendDirection === 'rising') {
                    if (($prediction['urgency_score'] ?? 0) >= 70) {
                        $analysis['missing_emerging'][] = $prediction;
                    }
                }
            }
        }
        
        // Sort each category by urgency/impact
        foreach ($analysis as $key => &$skills) {
            usort($skills, fn($a, $b) => ($b['urgency_score'] ?? 0) <=> ($a['urgency_score'] ?? 0));
        }
        
        return $analysis;
    }

    /**
     * Generate skill investment recommendations
     */
    public function generateInvestmentRecommendations(array $userSkills, string $industry, array $careerGoals): array
    {
        $portfolio = $this->compareSkillPortfolio($userSkills, $industry);
        
        $recommendations = [];
        
        // High priority: Learn emerging skills
        foreach (array_slice($portfolio['missing_emerging'], 0, 5) as $skill) {
            $recommendations[] = [
                'skill_name' => $skill['skill_name'],
                'priority' => 'high',
                'reasoning' => "Emerging skill with {$skill['urgency_score']}% urgency. {$skill['reasoning']}",
                'expected_roi' => $skill['salary_impact_usd'] ?? 0,
                'time_investment_months' => $skill['time_to_proficiency_months'] ?? 6,
                'career_impact' => implode(', ', $skill['career_paths'] ?? []),
            ];
        }
        
        // Medium priority: Update declining skills
        foreach (array_slice($portfolio['at_risk'], 0, 3) as $skill) {
            $recommendations[] = [
                'skill_name' => "Modernize {$skill['skill_name']}",
                'priority' => 'medium',
                'reasoning' => "You have this skill but it's declining. Consider pivoting to related modern technologies: " . implode(', ', $skill['related_skills'] ?? []),
                'expected_roi' => 0,
                'time_investment_months' => 3,
                'career_impact' => 'Risk mitigation',
            ];
        }
        
        // Celebrate: Skills already well-positioned
        $recommendations[] = [
            'skill_name' => 'Portfolio Health Check',
            'priority' => 'info',
            'reasoning' => "You're well-positioned with " . count($portfolio['future_proof']) . " future-proof skills and " . count($portfolio['well_positioned']) . " stable skills.",
            'expected_roi' => null,
            'time_investment_months' => 0,
            'career_impact' => 'Keep honing these skills',
        ];
        
        return $recommendations;
    }

    /**
     * Get fallback trends when AI fails
     */
    private function getFallbackTrends(string $industry): array
    {
        $fallbacks = [
            'Technology' => [
                'predictions' => [
                    ['skill_name' => 'Machine Learning', 'trend_direction' => 'rising', 'urgency_score' => 85],
                    ['skill_name' => 'Cloud Architecture', 'trend_direction' => 'stable', 'urgency_score' => 75],
                    ['skill_name' => 'Cybersecurity', 'trend_direction' => 'emerging', 'urgency_score' => 90],
                ],
                'meta' => ['confidence_level' => 'low', 'key_industry_shifts' => []],
            ],
        ];
        
        return $fallbacks[$industry] ?? ['predictions' => [], 'meta' => []];
    }

    /**
     * Get default skill outlook
     */
    private function getDefaultOutlook(string $skillName): array
    {
        return [
            'current_market_status' => 'Data unavailable',
            'forecast_5_year' => 'Unknown',
            'obsolescence_risk' => 50,
            'investment_recommendation' => 'Consult industry reports for ' . $skillName,
        ];
    }

    /**
     * Track AI usage for cost monitoring
     */
    private function trackAIUsage(int $totalTokens): void
    {
        Log::info('AI tokens used', [
            'service' => 'SkillTrendPredictor',
            'total_tokens' => $totalTokens,
        ]);
    }
}
