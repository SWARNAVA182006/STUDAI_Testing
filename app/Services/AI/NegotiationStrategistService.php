<?php

namespace App\Services\AI;

use App\Models\NegotiationStrategy;
use App\Models\SalaryTrend;
use App\Models\SkillTrend;
use App\Models\User;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NegotiationStrategistService
{
    use InteractsWithAI;
    /**
     * Generate comprehensive negotiation strategy
     */
    public function generateStrategy(User $user, array $offerData): NegotiationStrategy
    {
        // Gather market research
        $marketData = $this->gatherMarketResearch(
            $offerData['role'],
            $offerData['location'],
            $offerData['years_experience']
        );

        // Calculate optimal negotiation range
        $negotiationRange = $this->calculateOptimalRange(
            $offerData['offered_salary'],
            $marketData,
            $offerData['current_salary'] ?? null
        );

        // Analyze user's strongest negotiation points
        $strengthAnalysis = $this->analyzeNegotiationStrength($user, $offerData);

        // Get company intelligence
        $companyIntelligence = $this->getCompanyIntelligence($offerData['company_name']);

        // Determine timing and tactics
        $tacticalRecommendations = $this->determineTactics(
            $negotiationRange,
            $strengthAnalysis,
            $companyIntelligence
        );

        // Generate AI insights
        $aiInsights = $this->generateAiInsights(
            $user,
            $offerData,
            $marketData,
            $strengthAnalysis,
            $tacticalRecommendations
        );

        // Create strategy record
        $strategy = NegotiationStrategy::create([
            'user_id' => $user->id,
            'role' => $offerData['role'],
            'company_name' => $offerData['company_name'],
            'location' => $offerData['location'],
            'offered_salary' => $offerData['offered_salary'],
            'current_salary' => $offerData['current_salary'] ?? null,
            'years_experience' => $offerData['years_experience'],
            
            // Market data
            'market_median' => $marketData['median'],
            'market_percentile_25' => $marketData['percentile_25'],
            'market_percentile_75' => $marketData['percentile_75'],
            'market_percentile_90' => $marketData['percentile_90'],
            'offered_salary_percentile' => $marketData['offered_percentile'],
            'company_salary_data' => $marketData['company_data'] ?? null,
            
            // Negotiation range
            'optimal_ask' => $negotiationRange['optimal'],
            'minimum_acceptable' => $negotiationRange['minimum'],
            'stretch_goal' => $negotiationRange['stretch'],
            'confidence_score' => $negotiationRange['confidence'],
            
            // Strength analysis
            'strongest_points' => $strengthAnalysis['strengths'],
            'value_propositions' => $strengthAnalysis['value_props'],
            'risk_factors' => $strengthAnalysis['risks'],
            
            // Tactical recommendations
            'recommended_timing' => $tacticalRecommendations['timing'],
            'timing_rationale' => $tacticalRecommendations['timing_rationale'],
            'recommended_tone' => $tacticalRecommendations['tone'],
            'recommended_tactics' => $tacticalRecommendations['tactics'],
            'benefits_to_negotiate' => $tacticalRecommendations['alternative_benefits'],
            'total_comp_optimization' => $tacticalRecommendations['total_comp'],
            
            // Company intelligence
            'company_culture_analysis' => $companyIntelligence['culture'],
            'hiring_manager_perspective' => $companyIntelligence['manager_perspective'],
            'company_negotiation_flexibility' => $companyIntelligence['flexibility'],
            
            // AI insights
            'ai_summary' => $aiInsights['summary'],
            'ai_rationale' => $aiInsights['rationale'],
            'ai_warnings' => $aiInsights['warnings'],
            
            'status' => 'active',
            'generated_at' => now(),
        ]);

        return $strategy;
    }

    /**
     * Gather comprehensive market research
     */
    protected function gatherMarketResearch(string $role, string $location, int $years_experience): array
    {
        $cacheKey = "market_research_{$role}_{$location}_" . md5($years_experience);

        return Cache::remember($cacheKey, 3600, function () use ($role, $location, $years_experience) {
            // Determine experience level
            $experienceLevel = $this->mapExperienceLevel($years_experience);

            // Get salary trends for role/location
            $salaryTrend = SalaryTrend::where('role', 'LIKE', "%{$role}%")
                ->where('location', 'LIKE', "%{$location}%")
                ->where('experience_level', $experienceLevel)
                ->orderBy('data_date', 'desc')
                ->first();

            if ($salaryTrend) {
                return [
                    'median' => (float) $salaryTrend->median_salary,
                    'percentile_25' => (float) $salaryTrend->percentile_25,
                    'percentile_75' => (float) $salaryTrend->percentile_75,
                    'percentile_90' => (float) $salaryTrend->percentile_90,
                    'sample_size' => $salaryTrend->sample_size,
                    'offered_percentile' => 0, // Will be calculated later
                    'trend' => $salaryTrend->trend_direction,
                    'mom_change' => (float) $salaryTrend->mom_change,
                    'yoy_change' => (float) $salaryTrend->yoy_change,
                ];
            }

            // Fallback estimates if no data
            $baseEstimate = $this->estimateBaseSalary($role, $location);
            
            return [
                'median' => $baseEstimate,
                'percentile_25' => $baseEstimate * 0.85,
                'percentile_75' => $baseEstimate * 1.15,
                'percentile_90' => $baseEstimate * 1.30,
                'sample_size' => 0,
                'offered_percentile' => 0,
                'trend' => 'stable',
                'mom_change' => 0,
                'yoy_change' => 0,
            ];
        });
    }

    /**
     * Calculate optimal negotiation range
     */
    protected function calculateOptimalRange(float $offeredSalary, array $marketData, ?float $currentSalary): array
    {
        $median = $marketData['median'];
        $p75 = $marketData['percentile_75'];
        $p90 = $marketData['percentile_90'];

        // Calculate where the offer stands
        $offeredPercentile = $this->calculatePercentile($offeredSalary, $marketData);
        $marketData['offered_percentile'] = $offeredPercentile;

        // Determine optimal ask based on offer position
        if ($offeredPercentile < 50) {
            // Offer below market median - aim for 60-70th percentile
            $optimal = $median * 1.10;
            $minimum = max($median, $offeredSalary * 1.05);
            $stretch = $p75;
            $confidence = 85; // High confidence when offer is below market
        } elseif ($offeredPercentile < 75) {
            // Offer at median - aim for 75-80th percentile
            $optimal = ($median + $p75) / 2;
            $minimum = max($median, $offeredSalary * 1.03);
            $stretch = $p90;
            $confidence = 70; // Good confidence
        } else {
            // Offer already strong - modest increase possible
            $optimal = $offeredSalary * 1.05;
            $minimum = $offeredSalary * 1.02;
            $stretch = $p90;
            $confidence = 50; // Lower confidence when offer is already high
        }

        // Adjust based on current salary (if provided)
        if ($currentSalary && $currentSalary > 0) {
            $minAcceptableIncrease = $currentSalary * 1.10; // 10% minimum increase
            $minimum = max($minimum, $minAcceptableIncrease);
            $optimal = max($optimal, $currentSalary * 1.15); // 15% target increase
        }

        // Ensure logical order
        $minimum = min($minimum, $optimal);
        $stretch = max($stretch, $optimal);

        return [
            'optimal' => round($optimal, 2),
            'minimum' => round($minimum, 2),
            'stretch' => round($stretch, 2),
            'confidence' => $confidence,
        ];
    }

    /**
     * Calculate salary percentile
     */
    protected function calculatePercentile(float $salary, array $marketData): float
    {
        $p25 = $marketData['percentile_25'];
        $p75 = $marketData['percentile_75'];
        $p90 = $marketData['percentile_90'];
        $median = $marketData['median'];

        if ($salary <= $p25) {
            return ($salary / $p25) * 25;
        } elseif ($salary <= $median) {
            return 25 + (($salary - $p25) / ($median - $p25)) * 25;
        } elseif ($salary <= $p75) {
            return 50 + (($salary - $median) / ($p75 - $median)) * 25;
        } elseif ($salary <= $p90) {
            return 75 + (($salary - $p75) / ($p90 - $p75)) * 15;
        } else {
            return min(100, 90 + (($salary - $p90) / ($p90 * 0.1)) * 10);
        }
    }

    /**
     * Analyze user's negotiation strength
     */
    protected function analyzeNegotiationStrength(User $user, array $offerData): array
    {
        $strengths = [];
        $valueProps = [];
        $risks = [];

        $profile = $user->profile;

        // Experience-based strengths
        if ($offerData['years_experience'] >= 10) {
            $strengths[] = [
                'category' => 'experience',
                'point' => 'Extensive industry experience',
                'leverage' => 'high',
            ];
            $valueProps[] = 'Over 10 years of proven track record in the industry';
        } elseif ($offerData['years_experience'] >= 5) {
            $strengths[] = [
                'category' => 'experience',
                'point' => 'Solid mid-level experience',
                'leverage' => 'medium',
            ];
        }

        // Skills-based strengths
        if ($profile) {
            $skills = is_array($profile->skills) ? $profile->skills : json_decode($profile->skills ?? '[]', true);
            $hotSkills = $this->identifyHotSkills($skills);
            
            foreach ($hotSkills as $skill) {
                $strengths[] = [
                    'category' => 'skills',
                    'point' => "High-demand skill: {$skill['name']}",
                    'leverage' => 'high',
                ];
                $valueProps[] = "Expertise in {$skill['name']}, which is experiencing {$skill['growth']}% growth in demand";
            }
        }

        // Education-based strengths
        if ($profile && !empty($profile->education)) {
            $education = is_array($profile->education) ? $profile->education : json_decode($profile->education ?? '[]', true);
            
            foreach ($education as $edu) {
                if (isset($edu['degree']) && in_array($edu['degree'], ['Master', 'PhD', 'MBA'])) {
                    $strengths[] = [
                        'category' => 'education',
                        'point' => "Advanced degree: {$edu['degree']}",
                        'leverage' => 'medium',
                    ];
                }
            }
        }

        // Current employment status
        if (isset($offerData['current_salary']) && $offerData['current_salary'] > 0) {
            $strengths[] = [
                'category' => 'alternatives',
                'point' => 'Currently employed with competitive salary',
                'leverage' => 'high',
            ];
            $valueProps[] = 'Leaving a stable position, bringing proven performance';
        } else {
            $risks[] = [
                'category' => 'alternatives',
                'factor' => 'Currently seeking employment',
                'impact' => 'May reduce negotiation leverage',
            ];
        }

        // Market conditions
        $roleInDemand = $this->isRoleInDemand($offerData['role']);
        if ($roleInDemand) {
            $strengths[] = [
                'category' => 'market',
                'point' => 'Role is in high demand',
                'leverage' => 'medium',
            ];
        }

        return [
            'strengths' => $strengths,
            'value_props' => $valueProps,
            'risks' => $risks,
        ];
    }

    /**
     * Get company intelligence
     */
    protected function getCompanyIntelligence(string $companyName): array
    {
        $cacheKey = "company_intelligence_" . md5($companyName);

        return Cache::remember($cacheKey, 86400, function () use ($companyName) {
            // In production, this would integrate with company databases, Glassdoor, etc.
            // For now, we'll use AI to generate insights
            
            try {
                $analysis = $this->ai(
                    "Analyze {$companyName}'s typical salary negotiation flexibility and company culture. Keep response under 200 words with specific, actionable insights.",
                    'You are a company culture and negotiation analyst. Provide brief, actionable insights about company negotiation flexibility and culture.',
                    ['temperature' => 0.7]
                );

                // Parse flexibility level
                $flexibility = 'medium'; // default
                if (stripos($analysis, 'highly flexible') !== false || stripos($analysis, 'very flexible') !== false) {
                    $flexibility = 'high';
                } elseif (stripos($analysis, 'not flexible') !== false || stripos($analysis, 'rigid') !== false) {
                    $flexibility = 'low';
                }

                return [
                    'culture' => [
                        'analysis' => $analysis,
                        'key_values' => $this->extractKeyValues($analysis),
                    ],
                    'manager_perspective' => "Based on {$companyName}'s culture, the hiring manager is likely focused on finding the right fit and may have some flexibility within their approved budget range.",
                    'flexibility' => $flexibility,
                ];
            } catch (\Exception $e) {
                Log::error('Company intelligence generation failed', [
                    'company' => $companyName,
                    'error' => $e->getMessage()
                ]);

                return $this->getFallbackCompanyIntelligence();
            }
        });
    }

    /**
     * Determine negotiation tactics
     */
    protected function determineTactics(array $negotiationRange, array $strengthAnalysis, array $companyIntelligence): array
    {
        $tactics = [];
        $alternativeBenefits = [];
        
        // Determine timing based on confidence and offer strength
        $confidence = $negotiationRange['confidence'];
        if ($confidence >= 80) {
            $timing = 'within_24h';
            $timingRationale = 'Strong negotiation position allows for quick response while maintaining enthusiasm.';
        } elseif ($confidence >= 60) {
            $timing = 'within_48h';
            $timingRationale = 'Take time to gather market data and prepare compelling justification.';
        } else {
            $timing = 'within_week';
            $timingRationale = 'Carefully consider all factors and potentially seek alternative offers.';
        }

        // Determine tone based on company culture
        $flexibility = $companyIntelligence['flexibility'];
        if ($flexibility === 'high') {
            $tone = 'collaborative';
            $tactics[] = 'collaborative_problem_solving';
            $tactics[] = 'value_demonstration';
        } elseif ($flexibility === 'low') {
            $tone = 'professional';
            $tactics[] = 'data_driven_justification';
            $tactics[] = 'alternative_benefits_focus';
            
            // Add alternative benefits for inflexible companies
            $alternativeBenefits = [
                'sign_on_bonus' => 'One-time signing bonus',
                'equity' => 'Stock options or RSUs',
                'performance_bonus' => 'Higher performance bonus percentage',
                'additional_pto' => 'Additional vacation days',
                'professional_development' => 'Learning and development budget',
                'remote_work' => 'Remote work flexibility',
            ];
        } else {
            $tone = 'confident';
            $tactics[] = 'market_anchoring';
            $tactics[] = 'skills_leverage';
        }

        // Add tactics based on strengths
        $strengthCount = count($strengthAnalysis['strengths']);
        if ($strengthCount >= 5) {
            $tactics[] = 'multiple_value_points';
        }

        if (count($strengthAnalysis['value_props']) > 0) {
            $tactics[] = 'unique_value_proposition';
        }

        // Total compensation optimization
        $totalComp = [
            'base_salary' => $negotiationRange['optimal'],
            'target_bonus' => 15, // percentage
            'equity_value' => 0,
            'benefits_value' => 0,
        ];

        return [
            'timing' => $timing,
            'timing_rationale' => $timingRationale,
            'tone' => $tone,
            'tactics' => $tactics,
            'alternative_benefits' => $alternativeBenefits,
            'total_comp' => $totalComp,
        ];
    }

    /**
     * Generate AI-powered insights
     */
    protected function generateAiInsights(User $user, array $offerData, array $marketData, array $strengthAnalysis, array $tacticalRecommendations): array
    {
        $cacheKey = "ai_insights_" . md5(json_encode($offerData));

        return Cache::remember($cacheKey, 3600, function () use ($user, $offerData, $marketData, $strengthAnalysis, $tacticalRecommendations) {
            try {
                $prompt = $this->buildInsightsPrompt($user, $offerData, $marketData, $strengthAnalysis);

                $fullResponse = $this->ai(
                    $prompt,
                    'You are an expert salary negotiation strategist with 20+ years of experience. Provide strategic, actionable advice.',
                    ['temperature' => 0.7]
                );

                // Parse response into summary and rationale
                $parts = explode("\n\n", $fullResponse, 2);
                $summary = $parts[0] ?? $fullResponse;
                $rationale = $parts[1] ?? '';

                // Extract warnings
                $warnings = $this->extractWarnings($fullResponse);

                return [
                    'summary' => $summary,
                    'rationale' => $rationale,
                    'warnings' => $warnings,
                ];
            } catch (\Exception $e) {
                Log::error('AI insights generation failed', ['error' => $e->getMessage()]);
                return $this->getFallbackInsights($offerData, $marketData);
            }
        });
    }

    /**
     * Build prompt for AI insights
     */
    protected function buildInsightsPrompt(User $user, array $offerData, array $marketData, array $strengthAnalysis): string
    {
        $prompt = "Analyze this job offer and provide strategic negotiation advice:\n\n";
        $prompt .= "**Job Offer:**\n";
        $prompt .= "- Role: {$offerData['role']}\n";
        $prompt .= "- Company: {$offerData['company_name']}\n";
        $prompt .= "- Offered Salary: $" . number_format($offerData['offered_salary']) . "\n";
        $prompt .= "- Market Median: $" . number_format($marketData['median']) . "\n";
        $prompt .= "- Offer Percentile: " . round($marketData['offered_percentile']) . "th\n";
        $prompt .= "- Experience: {$offerData['years_experience']} years\n\n";
        
        if (!empty($strengthAnalysis['strengths'])) {
            $prompt .= "**Candidate Strengths:**\n";
            foreach (array_slice($strengthAnalysis['strengths'], 0, 3) as $strength) {
                $prompt .= "- {$strength['point']}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Provide:\n";
        $prompt .= "1. Executive summary (2-3 sentences) on negotiation viability\n";
        $prompt .= "2. Key strategic recommendations\n";
        $prompt .= "3. Potential risks or warnings\n\n";
        $prompt .= "Be specific and actionable. Focus on maximizing value while maintaining positive relationship.";

        return $prompt;
    }

    /**
     * Helper: Map years of experience to level
     */
    protected function mapExperienceLevel(int $years): string
    {
        if ($years < 2) return 'junior';
        if ($years < 5) return 'mid';
        if ($years < 10) return 'senior';
        return 'lead';
    }

    /**
     * Helper: Estimate base salary if no data available
     */
    protected function estimateBaseSalary(string $role, string $location): float
    {
        // Simplified estimation logic
        $baseEstimates = [
            'engineer' => 100000,
            'developer' => 95000,
            'designer' => 85000,
            'manager' => 110000,
            'analyst' => 75000,
        ];

        $estimate = 80000; // default
        foreach ($baseEstimates as $keyword => $value) {
            if (stripos($role, $keyword) !== false) {
                $estimate = $value;
                break;
            }
        }

        // Adjust for location (simplified)
        if (stripos($location, 'San Francisco') !== false || stripos($location, 'New York') !== false) {
            $estimate *= 1.3;
        } elseif (stripos($location, 'Seattle') !== false || stripos($location, 'Boston') !== false) {
            $estimate *= 1.2;
        }

        return $estimate;
    }

    /**
     * Helper: Identify hot skills
     */
    protected function identifyHotSkills(array $skills): array
    {
        $hotSkills = [];

        foreach ($skills as $skill) {
            $skillName = is_array($skill) ? ($skill['name'] ?? $skill) : $skill;
            
            $trend = SkillTrend::where('skill_name', 'LIKE', "%{$skillName}%")
                ->whereIn('trend_status', ['hot', 'emerging'])
                ->orderBy('growth_rate', 'desc')
                ->first();

            if ($trend) {
                $hotSkills[] = [
                    'name' => $skillName,
                    'growth' => round((float) $trend->growth_rate, 1),
                ];
            }
        }

        return array_slice($hotSkills, 0, 3); // Top 3
    }

    /**
     * Helper: Check if role is in demand
     */
    protected function isRoleInDemand(string $role): bool
    {
        // Simplified check - in production would use more sophisticated logic
        return true; // Most roles have some demand
    }

    /**
     * Helper: Extract key values from text
     */
    protected function extractKeyValues(string $text): array
    {
        $values = [];
        
        if (stripos($text, 'innovation') !== false) $values[] = 'Innovation';
        if (stripos($text, 'collaboration') !== false) $values[] = 'Collaboration';
        if (stripos($text, 'growth') !== false) $values[] = 'Growth';
        if (stripos($text, 'data-driven') !== false) $values[] = 'Data-Driven';
        
        return $values;
    }

    /**
     * Helper: Extract warnings from AI response
     */
    protected function extractWarnings(string $text): array
    {
        $warnings = [];
        
        if (stripos($text, 'risk') !== false || stripos($text, 'warning') !== false) {
            preg_match_all('/(?:risk|warning|caution):?\s*([^.]+)/i', $text, $matches);
            $warnings = array_slice($matches[1] ?? [], 0, 3);
        }

        return $warnings;
    }

    /**
     * Fallback insights when AI unavailable
     */
    protected function getFallbackInsights(array $offerData, array $marketData): array
    {
        $percentile = $marketData['offered_percentile'];
        
        if ($percentile < 50) {
            $summary = "The offer is below market median. You have strong negotiation leverage to request a higher salary.";
            $rationale = "Market data shows this offer is at the {$percentile}th percentile. Requesting the market median or higher is well-justified.";
        } else {
            $summary = "The offer is competitive and at/above market median. Modest negotiation is still possible.";
            $rationale = "The offer is at the {$percentile}th percentile. Focus on demonstrating unique value to justify additional compensation.";
        }

        return [
            'summary' => $summary,
            'rationale' => $rationale,
            'warnings' => [],
        ];
    }

    /**
     * Fallback company intelligence
     */
    protected function getFallbackCompanyIntelligence(): array
    {
        return [
            'culture' => [
                'analysis' => 'Company culture analysis unavailable. Proceed with professional, data-driven approach.',
                'key_values' => ['Professionalism', 'Merit-based'],
            ],
            'manager_perspective' => 'Hiring manager likely has budget constraints but values finding the right candidate.',
            'flexibility' => 'medium',
        ];
    }
}
