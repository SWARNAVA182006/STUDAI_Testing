<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserMarketPosition;
use App\Models\CompetitiveBenchmark;
use App\Models\SalaryTrend;
use App\Models\SkillTrend;
use App\Models\RolePrediction;
use App\Models\Profile;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Market Positioning Service
 * 
 * Calculates user's market readiness score, competitive positioning,
 * identifies skill gaps, and provides personalized recommendations.
 */
class MarketPositioningService
{
    /**
     * Calculate comprehensive market position for user
     */
    public function calculateMarketPosition(User $user): UserMarketPosition
    {
        // Calculate all components
        $readinessScore = $this->calculateReadinessScore($user);
        $percentiles = $this->calculatePercentileRankings($user);
        $competitiveAnalysis = $this->analyzeCompetitivePosition($user);
        $roleAnalysis = $this->analyzeRoleFit($user);
        $recommendations = $this->generateRecommendations($user, $competitiveAnalysis, $roleAnalysis);
        
        // Create or update position
        return UserMarketPosition::updateOrCreate(
            ['user_id' => $user->id],
            [
                'readiness_score' => $readinessScore['overall'],
                'readiness_breakdown' => $readinessScore['breakdown'],
                'overall_percentile' => $percentiles['overall'],
                'experience_percentile' => $percentiles['experience'],
                'skills_percentile' => $percentiles['skills'],
                'compensation_percentile' => $percentiles['compensation'],
                'competitive_advantages' => $competitiveAnalysis['advantages'],
                'competitive_weaknesses' => $competitiveAnalysis['weaknesses'],
                'skill_gaps' => $competitiveAnalysis['skill_gaps'],
                'best_fit_roles' => $roleAnalysis['best_fit'],
                'trending_opportunities' => $roleAnalysis['trending'],
                'roles_to_avoid' => $roleAnalysis['avoid'],
                'recommendations' => $recommendations['actions'],
                'recommendation_priority' => $recommendations['priority'],
                'calculated_at' => now(),
                'next_update_at' => now()->addDay(),
            ]
        );
    }

    /**
     * Calculate market readiness score (0-100)
     */
    protected function calculateReadinessScore(User $user): array
    {
        $profile = $user->profile;
        
        // Component scores (each 0-100)
        $profileCompleteness = $this->scoreProfileCompleteness($profile);
        $experienceQuality = $this->scoreExperienceQuality($profile);
        $skillsModernity = $this->scoreSkillsModernity($profile);
        $educationRelevance = $this->scoreEducationRelevance($profile);
        $marketAlignment = $this->scoreMarketAlignment($user);
        
        // Weighted average
        $overall = (
            $profileCompleteness * 0.15 +
            $experienceQuality * 0.30 +
            $skillsModernity * 0.25 +
            $educationRelevance * 0.10 +
            $marketAlignment * 0.20
        );
        
        return [
            'overall' => round($overall, 2),
            'breakdown' => [
                'profile_completeness' => round($profileCompleteness, 2),
                'experience_quality' => round($experienceQuality, 2),
                'skills_modernity' => round($skillsModernity, 2),
                'education_relevance' => round($educationRelevance, 2),
                'market_alignment' => round($marketAlignment, 2),
            ],
        ];
    }

    /**
     * Score profile completeness
     */
    protected function scoreProfileCompleteness(?Profile $profile): float
    {
        if (!$profile) return 0;
        
        $fields = [
            'bio' => 10,
            'experience' => 25,
            'education' => 15,
            'skills' => 20,
            'certifications' => 10,
            'portfolio_url' => 5,
            'github_url' => 5,
            'linkedin_url' => 5,
            'resume_path' => 5,
        ];
        
        $score = 0;
        foreach ($fields as $field => $weight) {
            $value = $profile->$field;
            if ($field === 'experience' || $field === 'education' || $field === 'skills') {
                // JSON fields - check if array has items
                $score += !empty($value) ? $weight : 0;
            } else {
                // Regular fields - check if not null
                $score += !empty($value) ? $weight : 0;
            }
        }
        
        return $score; // Already out of 100
    }

    /**
     * Score experience quality
     */
    protected function scoreExperienceQuality(?Profile $profile): float
    {
        if (!$profile || empty($profile->experience)) return 0;
        
        $experiences = $profile->experience;
        $totalMonths = 0;
        $relevantMonths = 0;
        $hasLeadership = false;
        $companyQuality = 0;
        
        foreach ($experiences as $exp) {
            $start = \Carbon\Carbon::parse($exp['start_date'] ?? now());
            $end = isset($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date']) : now();
            $months = $start->diffInMonths($end);
            
            $totalMonths += $months;
            
            // Check relevance based on recent vs. old
            if ($end->gte(now()->subYears(3))) {
                $relevantMonths += $months;
            }
            
            // Check for leadership roles
            $title = strtolower($exp['title'] ?? '');
            if (str_contains($title, 'lead') || str_contains($title, 'senior') || 
                str_contains($title, 'manager') || str_contains($title, 'director')) {
                $hasLeadership = true;
            }
            
            // Company quality (check if recognized company)
            // In production, this would check against a company ranking database
            $companyQuality += 50; // Placeholder
        }
        
        $avgCompanyQuality = count($experiences) > 0 ? $companyQuality / count($experiences) : 0;
        
        // Score components
        $durationScore = min(100, ($totalMonths / 60) * 100); // 5 years = 100%
        $recencyScore = $totalMonths > 0 ? ($relevantMonths / $totalMonths) * 100 : 0;
        $seniorityScore = $hasLeadership ? 100 : 50;
        $qualityScore = $avgCompanyQuality;
        
        return ($durationScore * 0.3 + $recencyScore * 0.3 + $seniorityScore * 0.2 + $qualityScore * 0.2);
    }

    /**
     * Score skills modernity (how up-to-date skills are)
     */
    protected function scoreSkillsModernity(?Profile $profile): float
    {
        if (!$profile || empty($profile->skills)) return 0;
        
        $userSkills = $profile->skills;
        $modernityScore = 0;
        $skillCount = 0;
        
        foreach ($userSkills as $skill) {
            $skillName = is_array($skill) ? ($skill['name'] ?? $skill['skill'] ?? '') : $skill;
            
            // Get skill trend
            $trend = SkillTrend::getLatest($skillName);
            
            if ($trend) {
                // Score based on trend status
                $skillScore = match($trend->trend_status) {
                    'emerging' => 100,
                    'hot' => 95,
                    'stable' => 75,
                    'declining' => 40,
                    'obsolete' => 10,
                    default => 50,
                };
                
                $modernityScore += $skillScore;
                $skillCount++;
            } else {
                // No trend data, assume moderate score
                $modernityScore += 60;
                $skillCount++;
            }
        }
        
        return $skillCount > 0 ? $modernityScore / $skillCount : 0;
    }

    /**
     * Score education relevance
     */
    protected function scoreEducationRelevance(?Profile $profile): float
    {
        if (!$profile || empty($profile->education)) return 0;
        
        $educations = $profile->education;
        $maxScore = 0;
        
        foreach ($educations as $edu) {
            $degree = strtolower($edu['degree'] ?? '');
            $field = strtolower($edu['field_of_study'] ?? '');
            
            // Degree level score
            $degreeScore = 0;
            if (str_contains($degree, 'phd') || str_contains($degree, 'doctorate')) {
                $degreeScore = 100;
            } elseif (str_contains($degree, 'master') || str_contains($degree, 'mba')) {
                $degreeScore = 85;
            } elseif (str_contains($degree, 'bachelor')) {
                $degreeScore = 70;
            } elseif (str_contains($degree, 'associate')) {
                $degreeScore = 50;
            } else {
                $degreeScore = 30;
            }
            
            // Field relevance (check against tech/business fields)
            $techFields = ['computer', 'software', 'engineering', 'data', 'technology'];
            $isRelevant = false;
            foreach ($techFields as $techField) {
                if (str_contains($field, $techField)) {
                    $isRelevant = true;
                    break;
                }
            }
            
            $fieldScore = $isRelevant ? 100 : 70;
            
            // Combined score
            $score = ($degreeScore * 0.6) + ($fieldScore * 0.4);
            
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }
        
        return $maxScore;
    }

    /**
     * Score market alignment (how well user fits current market needs)
     */
    protected function scoreMarketAlignment(User $user): float
    {
        $profile = $user->profile;
        if (!$profile) return 0;
        
        // Get user's target roles from preferences or recent applications
        $targetRoles = $this->getUserTargetRoles($user);
        
        $alignmentScores = [];
        foreach ($targetRoles as $role) {
            // Get market demand for this role
            $prediction = RolePrediction::getLatest($role);
            
            if ($prediction) {
                // Higher demand = better alignment
                $demandScore = $prediction->current_demand_score;
                
                // Check if role is recommended
                $recommendationBonus = match($prediction->recommendation) {
                    'pursue' => 20,
                    'consider' => 10,
                    'avoid' => -20,
                    default => 0,
                };
                
                $alignmentScores[] = min(100, $demandScore + $recommendationBonus);
            } else {
                // No prediction data, assume moderate alignment
                $alignmentScores[] = 50;
            }
        }
        
        return !empty($alignmentScores) ? array_sum($alignmentScores) / count($alignmentScores) : 50;
    }

    /**
     * Calculate percentile rankings vs. market
     */
    protected function calculatePercentileRankings(User $user): array
    {
        $profile = $user->profile;
        
        $experiencePercentile = $this->calculateExperiencePercentile($profile);
        $skillsPercentile = $this->calculateSkillsPercentile($profile);
        $compensationPercentile = $this->calculateCompensationPercentile($user);
        
        $overall = ($experiencePercentile + $skillsPercentile + $compensationPercentile) / 3;
        
        return [
            'overall' => round($overall, 2),
            'experience' => round($experiencePercentile, 2),
            'skills' => round($skillsPercentile, 2),
            'compensation' => round($compensationPercentile, 2),
        ];
    }

    /**
     * Calculate experience percentile
     */
    protected function calculateExperiencePercentile(?Profile $profile): float
    {
        if (!$profile || empty($profile->experience)) return 0;
        
        // Calculate total years of experience
        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $start = \Carbon\Carbon::parse($exp['start_date'] ?? now());
            $end = isset($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date']) : now();
            $totalMonths += $start->diffInMonths($end);
        }
        
        $years = $totalMonths / 12;
        
        // Percentile based on years (simplified - in production, query actual distribution)
        if ($years >= 15) return 95;
        if ($years >= 10) return 85;
        if ($years >= 7) return 75;
        if ($years >= 5) return 65;
        if ($years >= 3) return 50;
        if ($years >= 1) return 35;
        return 20;
    }

    /**
     * Calculate skills percentile
     */
    protected function calculateSkillsPercentile(?Profile $profile): float
    {
        if (!$profile || empty($profile->skills)) return 0;
        
        $totalValue = 0;
        $skillCount = count($profile->skills);
        
        foreach ($profile->skills as $skill) {
            $skillName = is_array($skill) ? ($skill['name'] ?? $skill['skill'] ?? '') : $skill;
            $trend = SkillTrend::getLatest($skillName);
            
            if ($trend) {
                // Use value score (0-100)
                $totalValue += $trend->value_score;
            } else {
                $totalValue += 50; // Default value
            }
        }
        
        $avgValue = $skillCount > 0 ? $totalValue / $skillCount : 0;
        
        // More skills = bonus (up to 15 skills)
        $quantityBonus = min(20, ($skillCount / 15) * 20);
        
        return min(100, $avgValue * 0.8 + $quantityBonus);
    }

    /**
     * Calculate compensation percentile
     */
    protected function calculateCompensationPercentile(User $user): float
    {
        $profile = $user->profile;
        if (!$profile) return 50;
        
        // Get user's current or expected salary
        $userSalary = $profile->expected_salary ?? $profile->current_salary ?? null;
        
        if (!$userSalary) return 50; // No salary data
        
        // Get target roles
        $targetRoles = $this->getUserTargetRoles($user);
        
        if (empty($targetRoles)) return 50;
        
        // Get salary trend for primary role
        $primaryRole = $targetRoles[0];
        $salaryTrend = SalaryTrend::getLatest($primaryRole);
        
        if (!$salaryTrend) return 50;
        
        // Calculate percentile using trend data
        return $salaryTrend->getUserPercentile($userSalary);
    }

    /**
     * Analyze competitive position
     */
    protected function analyzeCompetitivePosition(User $user): array
    {
        $profile = $user->profile;
        
        // Identify strengths
        $advantages = $this->identifyCompetitiveAdvantages($user);
        
        // Identify weaknesses
        $weaknesses = $this->identifyCompetitiveWeaknesses($user);
        
        // Identify skill gaps
        $skillGaps = $this->identifySkillGaps($user);
        
        // Store benchmarks
        $this->storeBenchmarks($user, $advantages, $weaknesses, $skillGaps);
        
        return [
            'advantages' => $advantages,
            'weaknesses' => $weaknesses,
            'skill_gaps' => $skillGaps,
        ];
    }

    /**
     * Identify competitive advantages
     */
    protected function identifyCompetitiveAdvantages(User $user): array
    {
        $profile = $user->profile;
        $advantages = [];
        
        // Strong education
        if ($profile && !empty($profile->education)) {
            foreach ($profile->education as $edu) {
                $degree = strtolower($edu['degree'] ?? '');
                if (str_contains($degree, 'phd') || str_contains($degree, 'master')) {
                    $advantages[] = [
                        'category' => 'education',
                        'description' => 'Advanced degree: ' . ($edu['degree'] ?? 'Master\'s/PhD'),
                        'impact' => 'high',
                    ];
                    break;
                }
            }
        }
        
        // Hot skills
        if ($profile && !empty($profile->skills)) {
            foreach ($profile->skills as $skill) {
                $skillName = is_array($skill) ? ($skill['name'] ?? $skill['skill'] ?? '') : $skill;
                $trend = SkillTrend::getLatest($skillName);
                
                if ($trend && in_array($trend->trend_status, ['emerging', 'hot'])) {
                    $advantages[] = [
                        'category' => 'skills',
                        'description' => "Trending skill: {$skillName}",
                        'impact' => 'high',
                        'value_score' => $trend->value_score,
                    ];
                }
            }
        }
        
        // Extensive experience
        $totalYears = $this->calculateTotalExperience($profile);
        if ($totalYears >= 7) {
            $advantages[] = [
                'category' => 'experience',
                'description' => "Extensive experience: {$totalYears} years",
                'impact' => 'medium',
            ];
        }
        
        return array_slice($advantages, 0, 10); // Top 10
    }

    /**
     * Identify competitive weaknesses
     */
    protected function identifyCompetitiveWeaknesses(User $user): array
    {
        $profile = $user->profile;
        $weaknesses = [];
        
        // Obsolete skills
        if ($profile && !empty($profile->skills)) {
            foreach ($profile->skills as $skill) {
                $skillName = is_array($skill) ? ($skill['name'] ?? $skill['skill'] ?? '') : $skill;
                $trend = SkillTrend::getLatest($skillName);
                
                if ($trend && in_array($trend->trend_status, ['declining', 'obsolete'])) {
                    $weaknesses[] = [
                        'category' => 'skills',
                        'description' => "Declining skill: {$skillName}",
                        'severity' => 'medium',
                        'recommendation' => 'Consider learning replacement skills',
                    ];
                }
            }
        }
        
        // Limited experience
        $totalYears = $this->calculateTotalExperience($profile);
        if ($totalYears < 2) {
            $weaknesses[] = [
                'category' => 'experience',
                'description' => "Limited experience: {$totalYears} years",
                'severity' => 'high',
                'recommendation' => 'Focus on gaining practical experience',
            ];
        }
        
        // Incomplete profile
        $completeness = $this->scoreProfileCompleteness($profile);
        if ($completeness < 70) {
            $weaknesses[] = [
                'category' => 'profile',
                'description' => "Incomplete profile: {$completeness}% complete",
                'severity' => 'low',
                'recommendation' => 'Complete your profile to improve visibility',
            ];
        }
        
        return $weaknesses;
    }

    /**
     * Identify skill gaps for target roles
     */
    protected function identifySkillGaps(User $user): array
    {
        $profile = $user->profile;
        $targetRoles = $this->getUserTargetRoles($user);
        
        $allGaps = [];
        
        foreach ($targetRoles as $role) {
            // Get required skills for role
            $prediction = RolePrediction::getLatest($role);
            
            if ($prediction && !empty($prediction->required_skills)) {
                $requiredSkills = $prediction->required_skills;
                $userSkills = $profile ? array_map(function($skill) {
                    return is_array($skill) ? ($skill['name'] ?? $skill['skill'] ?? '') : $skill;
                }, $profile->skills ?? []) : [];
                
                foreach ($requiredSkills as $requiredSkill) {
                    if (!in_array($requiredSkill, $userSkills)) {
                        $trend = SkillTrend::getLatest($requiredSkill);
                        
                        $allGaps[] = [
                            'skill' => $requiredSkill,
                            'role' => $role,
                            'demand_score' => $trend ? $trend->demand_score : 50,
                            'value_score' => $trend ? $trend->value_score : 50,
                            'priority' => $trend && $trend->trend_status === 'hot' ? 'high' : 'medium',
                        ];
                    }
                }
            }
        }
        
        // Sort by value score
        usort($allGaps, fn($a, $b) => $b['value_score'] <=> $a['value_score']);
        
        return array_slice($allGaps, 0, 15); // Top 15 gaps
    }

    /**
     * Analyze role fit
     */
    protected function analyzeRoleFit(User $user): array
    {
        $profile = $user->profile;
        $userSkills = $profile ? array_map(function($skill) {
            return is_array($skill) ? ($skill['name'] ?? $skill['skill'] ?? '') : $skill;
        }, $profile->skills ?? []) : [];
        
        // Get all role predictions
        $allPredictions = RolePrediction::where('prediction_date', '>=', now()->subDays(30))->get();
        
        $bestFit = [];
        $trending = [];
        $avoid = [];
        
        foreach ($allPredictions as $prediction) {
            $requiredSkills = $prediction->required_skills ?? [];
            
            // Calculate match score
            $matchCount = 0;
            foreach ($requiredSkills as $skill) {
                if (in_array($skill, $userSkills)) {
                    $matchCount++;
                }
            }
            
            $matchScore = count($requiredSkills) > 0 
                ? ($matchCount / count($requiredSkills)) * 100 
                : 0;
            
            $roleData = [
                'role' => $prediction->role_title,
                'match_score' => round($matchScore, 2),
                'demand_score' => $prediction->current_demand_score,
                'salary' => $prediction->current_avg_salary,
                'status' => $prediction->role_status,
            ];
            
            // Categorize
            if ($matchScore >= 70 && $prediction->current_demand_score >= 60) {
                $bestFit[] = $roleData;
            }
            
            if (in_array($prediction->role_status, ['emerging', 'growing']) && $matchScore >= 50) {
                $trending[] = $roleData;
            }
            
            if (in_array($prediction->role_status, ['declining', 'obsolete'])) {
                $avoid[] = $roleData;
            }
        }
        
        // Sort
        usort($bestFit, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        usort($trending, fn($a, $b) => $b['demand_score'] <=> $a['demand_score']);
        
        return [
            'best_fit' => array_slice($bestFit, 0, 10),
            'trending' => array_slice($trending, 0, 10),
            'avoid' => array_slice($avoid, 0, 10),
        ];
    }

    /**
     * Generate personalized recommendations
     */
    protected function generateRecommendations(User $user, array $competitive, array $roles): array
    {
        $recommendations = [];
        $priority = 5; // Default priority
        
        // Skill gap recommendations
        foreach (array_slice($competitive['skill_gaps'], 0, 5) as $gap) {
            $recommendations[] = [
                'type' => 'skill',
                'action' => "Learn {$gap['skill']}",
                'reason' => "Required for {$gap['role']}, high value skill",
                'impact' => 'high',
                'effort' => 'medium',
                'timeline' => '1-3 months',
            ];
            
            if ($gap['priority'] === 'high') {
                $priority = max($priority, 8);
            }
        }
        
        // Profile improvement
        $completeness = $this->scoreProfileCompleteness($user->profile);
        if ($completeness < 80) {
            $recommendations[] = [
                'type' => 'profile',
                'action' => 'Complete your profile',
                'reason' => 'Improve visibility to employers',
                'impact' => 'medium',
                'effort' => 'low',
                'timeline' => '1 week',
            ];
        }
        
        // Role targeting
        if (!empty($roles['trending'])) {
            $topTrending = $roles['trending'][0];
            $recommendations[] = [
                'type' => 'role',
                'action' => "Consider applying for {$topTrending['role']} positions",
                'reason' => 'Emerging role with high demand',
                'impact' => 'high',
                'effort' => 'low',
                'timeline' => 'Immediate',
            ];
        }
        
        return [
            'actions' => $recommendations,
            'priority' => $priority,
        ];
    }

    /**
     * Store competitive benchmarks
     */
    protected function storeBenchmarks(User $user, array $advantages, array $weaknesses, array $skillGaps): void
    {
        // Store skill benchmark
        CompetitiveBenchmark::create([
            'user_id' => $user->id,
            'benchmark_category' => 'skills',
            'user_data' => ['skills' => $user->profile->skills ?? []],
            'user_score' => $this->calculateSkillsPercentile($user->profile),
            'market_average' => ['avg_skills' => 10],
            'market_top_10' => ['avg_skills' => 20],
            'market_top_25' => ['avg_skills' => 15],
            'gaps_identified' => $skillGaps,
            'strengths_identified' => array_filter($advantages, fn($a) => $a['category'] === 'skills'),
            'gap_severity' => count($skillGaps) > 10 ? 80 : count($skillGaps) * 5,
            'improvement_actions' => array_map(fn($gap) => "Learn {$gap['skill']}", array_slice($skillGaps, 0, 5)),
            'benchmarked_at' => now(),
        ]);
    }

    /**
     * Get user's target roles
     */
    protected function getUserTargetRoles(User $user): array
    {
        // Check if user has agent configuration
        if ($user->agentConfiguration && !empty($user->agentConfiguration->target_roles)) {
            return $user->agentConfiguration->target_roles;
        }
        
        // Fallback: Infer from recent applications
        $recentApplications = $user->applications()
            ->with('job')
            ->latest()
            ->limit(10)
            ->get();
        
        $roles = $recentApplications->pluck('job.title')->unique()->toArray();
        
        return !empty($roles) ? $roles : ['Software Engineer']; // Default fallback
    }

    /**
     * Calculate total years of experience
     */
    protected function calculateTotalExperience(?Profile $profile): float
    {
        if (!$profile || empty($profile->experience)) return 0;
        
        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $start = \Carbon\Carbon::parse($exp['start_date'] ?? now());
            $end = isset($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date']) : now();
            $totalMonths += $start->diffInMonths($end);
        }
        
        return round($totalMonths / 12, 1);
    }
}
