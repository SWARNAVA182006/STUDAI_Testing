<?php

namespace App\Services\AI;

use App\Models\SkillTrend;
use App\Models\Job;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Carbon\Carbon;

/**
 * Skill Trend Analysis Service
 * 
 * Analyzes skill demand trends, calculates skill value scores, identifies
 * emerging/declining skills, and generates personalized upskilling roadmaps.
 */
class SkillTrendAnalysisService
{
    /**
     * Analyze demand for a specific skill
     */
    public function analyzeSkillDemand(string $skill): array
    {
        $cacheKey = "skill_demand_{$skill}";
        
        return Cache::remember($cacheKey, 3600, function() use ($skill) {
            // Count job postings mentioning this skill
            $currentMonthCount = $this->getSkillJobCount($skill, now()->subDays(30), now());
            $lastMonthCount = $this->getSkillJobCount($skill, now()->subDays(60), now()->subDays(30));
            $lastYearCount = $this->getSkillJobCount($skill, now()->subYear(), now()->subYear()->addDays(30));
            
            // Calculate demand score (0-100 scale)
            $demandScore = min(100, ($currentMonthCount / 100) * 100); // Normalize to 100 jobs = 100 score
            
            // Calculate growth rate
            $growthRate = $lastMonthCount > 0 
                ? (($currentMonthCount - $lastMonthCount) / $lastMonthCount) * 100 
                : 0;
            
            $yearOverYearGrowth = $lastYearCount > 0 
                ? (($currentMonthCount - $lastYearCount) / $lastYearCount) * 100 
                : 0;
            
            // Calculate value score (salary premium)
            $valueScore = $this->calculateSkillValue($skill);
            
            // Determine trend status
            $trendStatus = $this->determineTrendStatus($demandScore, $growthRate);
            
            // Calculate trend velocity (how fast it's changing)
            $trendVelocity = abs($growthRate);
            
            // Store in database
            $this->storeSkillTrend($skill, [
                'demand_score' => $demandScore,
                'growth_rate' => $growthRate,
                'salary_premium' => $valueScore['premium'],
                'value_score' => $valueScore['score'],
                'trend_status' => $trendStatus,
                'trend_velocity' => $trendVelocity,
            ]);
            
            return [
                'skill' => $skill,
                'demand_score' => round($demandScore, 2),
                'growth_rate' => round($growthRate, 2),
                'year_over_year_growth' => round($yearOverYearGrowth, 2),
                'value_score' => $valueScore['score'],
                'salary_premium' => $valueScore['premium'],
                'trend_status' => $trendStatus,
                'trend_velocity' => round($trendVelocity, 2),
                'job_count' => $currentMonthCount,
            ];
        });
    }

    /**
     * Get job count for skill in date range
     */
    protected function getSkillJobCount(string $skill, Carbon $start, Carbon $end): int
    {
        return Job::where(function($q) use ($skill) {
            $q->where('title', 'like', "%{$skill}%")
              ->orWhere('description', 'like', "%{$skill}%")
              ->orWhereJsonContains('extracted_skills', $skill);
        })
        ->whereBetween('created_at', [$start, $end])
        ->where('status', 'published')
        ->count();
    }

    /**
     * Calculate skill value (salary premium)
     */
    public function calculateSkillValue(string $skill): array
    {
        // Get average salary for jobs requiring this skill
        $jobsWithSkill = Job::where(function($q) use ($skill) {
            $q->where('title', 'like', "%{$skill}%")
              ->orWhere('description', 'like', "%{$skill}%")
              ->orWhereJsonContains('extracted_skills', $skill);
        })
        ->whereNotNull('min_salary')
        ->where('created_at', '>=', now()->subDays(90))
        ->get();
        
        $avgWithSkill = $jobsWithSkill->avg(fn($job) => ($job->min_salary + $job->max_salary) / 2) ?? 0;
        
        // Get average salary for jobs NOT requiring this skill (baseline)
        $jobsWithoutSkill = Job::where(function($q) use ($skill) {
            $q->where('title', 'not like', "%{$skill}%")
              ->where('description', 'not like', "%{$skill}%");
        })
        ->whereNotNull('min_salary')
        ->where('created_at', '>=', now()->subDays(90))
        ->limit(500) // Sample for performance
        ->get();
        
        $avgWithoutSkill = $jobsWithoutSkill->avg(fn($job) => ($job->min_salary + $job->max_salary) / 2) ?? 0;
        
        // Calculate premium
        $premium = $avgWithoutSkill > 0 
            ? (($avgWithSkill - $avgWithoutSkill) / $avgWithoutSkill) * 100 
            : 0;
        
        // Calculate value score (0-100)
        $valueScore = min(100, max(0, 50 + $premium)); // 0% premium = 50, 50% premium = 100
        
        return [
            'premium' => round($premium, 2),
            'score' => round($valueScore, 2),
            'avg_with_skill' => round($avgWithSkill, 2),
            'avg_without_skill' => round($avgWithoutSkill, 2),
        ];
    }

    /**
     * Identify valuable skill combinations
     */
    public function identifySkillCombinations(): array
    {
        $cacheKey = "skill_combinations";
        
        return Cache::remember($cacheKey, 7200, function() {
            // Get jobs with extracted skills
            $jobs = Job::whereNotNull('extracted_skills')
                ->where('created_at', '>=', now()->subDays(90))
                ->whereNotNull('min_salary')
                ->get();
            
            $combinations = [];
            
            foreach ($jobs as $job) {
                $skills = $job->extracted_skills ?? [];
                
                if (count($skills) < 2) continue;
                
                // Generate combinations of 2-3 skills
                for ($i = 0; $i < count($skills); $i++) {
                    for ($j = $i + 1; $j < count($skills); $j++) {
                        $combo = [$skills[$i], $skills[$j]];
                        sort($combo);
                        $key = implode(' + ', $combo);
                        
                        if (!isset($combinations[$key])) {
                            $combinations[$key] = [
                                'skills' => $combo,
                                'count' => 0,
                                'salaries' => [],
                            ];
                        }
                        
                        $combinations[$key]['count']++;
                        $combinations[$key]['salaries'][] = ($job->min_salary + $job->max_salary) / 2;
                    }
                }
            }
            
            // Calculate average salary for each combination
            foreach ($combinations as $key => $data) {
                $combinations[$key]['avg_salary'] = array_sum($data['salaries']) / count($data['salaries']);
                unset($combinations[$key]['salaries']); // Remove raw salary data
            }
            
            // Filter: only combinations appearing in 5+ jobs
            $combinations = array_filter($combinations, fn($c) => $c['count'] >= 5);
            
            // Sort by average salary
            uasort($combinations, fn($a, $b) => $b['avg_salary'] <=> $a['avg_salary']);
            
            return array_slice($combinations, 0, 20); // Top 20 combinations
        });
    }

    /**
     * Predict skill obsolescence
     */
    public function predictSkillObsolescence(string $skill): array
    {
        // Get historical trend data
        $trends = SkillTrend::where('skill_name', $skill)
            ->where('trend_date', '>=', now()->subMonths(12))
            ->orderBy('trend_date')
            ->get();
        
        if ($trends->count() < 6) {
            return [
                'obsolescence_risk' => 'unknown',
                'confidence' => 'low',
                'message' => 'Not enough historical data',
            ];
        }
        
        // Calculate trend trajectory
        $growthRates = $trends->pluck('growth_rate')->toArray();
        $avgGrowthRate = array_sum($growthRates) / count($growthRates);
        
        // Check if consistently declining
        $recentGrowth = array_slice($growthRates, -3); // Last 3 months
        $isConsistentlyDeclining = !empty(array_filter($recentGrowth, fn($g) => $g < -5));
        
        // Calculate obsolescence score
        $obsolescenceScore = 0;
        
        if ($avgGrowthRate < -10) $obsolescenceScore += 40;
        elseif ($avgGrowthRate < -5) $obsolescenceScore += 20;
        
        if ($isConsistentlyDeclining) $obsolescenceScore += 30;
        
        $latestDemand = $trends->last()->demand_score;
        if ($latestDemand < 20) $obsolescenceScore += 30;
        
        // Determine risk level
        $risk = 'low';
        if ($obsolescenceScore >= 70) $risk = 'high';
        elseif ($obsolescenceScore >= 40) $risk = 'medium';
        
        // Predict time to obsolescence
        if ($avgGrowthRate < 0 && $latestDemand > 0) {
            $monthsToObsolescence = ($latestDemand / abs($avgGrowthRate)) * 1.5; // Conservative estimate
        } else {
            $monthsToObsolescence = null;
        }
        
        return [
            'skill' => $skill,
            'obsolescence_risk' => $risk,
            'obsolescence_score' => round($obsolescenceScore, 2),
            'avg_growth_rate' => round($avgGrowthRate, 2),
            'current_demand' => round($latestDemand, 2),
            'months_to_obsolescence' => $monthsToObsolescence ? round($monthsToObsolescence, 0) : null,
            'confidence' => 'high',
        ];
    }

    /**
     * Generate personalized upskilling roadmap
     */
    public function generateUpskillingRoadmap(User $user): array
    {
        $profile = $user->profile;
        
        if (!$profile) {
            return [
                'status' => 'error',
                'message' => 'No profile data available',
            ];
        }
        
        $currentSkills = $profile->skills ?? [];
        
        // Analyze current skills
        $skillAnalysis = [];
        foreach ($currentSkills as $skill) {
            $analysis = $this->analyzeSkillDemand($skill);
            $skillAnalysis[$skill] = $analysis;
        }
        
        // Identify skills to strengthen (declining or obsolete)
        $skillsToStrengthen = array_filter(
            $skillAnalysis,
            fn($s) => in_array($s['trend_status'], ['declining', 'obsolete'])
        );
        
        // Get trending skills in user's field
        $targetRole = $this->getUserPrimaryRole($user);
        $trendingSkills = $this->getRecommendedSkillsForRole($targetRole);
        
        // Filter out skills user already has
        $skillsToLearn = array_filter(
            $trendingSkills,
            fn($s) => !in_array($s['skill'], $currentSkills)
        );
        
        // Prioritize skills by value
        usort($skillsToLearn, fn($a, $b) => $b['value_score'] <=> $a['value_score']);
        
        // Create roadmap phases
        $roadmap = [
            'immediate' => [], // 0-3 months
            'short_term' => [], // 3-6 months
            'long_term' => [], // 6-12 months
        ];
        
        $skillIndex = 0;
        foreach ($skillsToLearn as $skill) {
            $difficulty = $this->estimateSkillDifficulty($skill['skill']);
            
            if ($skillIndex < 3) {
                // Immediate: Top 3 highest value skills
                $roadmap['immediate'][] = array_merge($skill, ['difficulty' => $difficulty]);
            } elseif ($skillIndex < 6) {
                // Short term: Next 3 skills
                $roadmap['short_term'][] = array_merge($skill, ['difficulty' => $difficulty]);
            } else {
                // Long term: Remaining skills
                $roadmap['long_term'][] = array_merge($skill, ['difficulty' => $difficulty]);
            }
            
            $skillIndex++;
            
            if ($skillIndex >= 10) break; // Limit to 10 skills total
        }
        
        return [
            'current_skills_analysis' => $skillAnalysis,
            'skills_to_strengthen' => array_values($skillsToStrengthen),
            'roadmap' => $roadmap,
            'total_skills_recommended' => $skillIndex,
        ];
    }

    /**
     * Get recommended skills for a role
     */
    protected function getRecommendedSkillsForRole(string $role): array
    {
        // Get jobs for this role
        $jobs = Job::where('title', 'like', "%{$role}%")
            ->whereNotNull('extracted_skills')
            ->where('created_at', '>=', now()->subDays(90))
            ->get();
        
        // Count skill frequencies
        $skillCounts = [];
        foreach ($jobs as $job) {
            $skills = $job->extracted_skills ?? [];
            
            foreach ($skills as $skill) {
                if (!isset($skillCounts[$skill])) {
                    $skillCounts[$skill] = 0;
                }
                $skillCounts[$skill]++;
            }
        }
        
        // Filter skills appearing in 5+ jobs
        $skillCounts = array_filter($skillCounts, fn($count) => $count >= 5);
        
        // Get trend data for each skill
        $recommendedSkills = [];
        foreach (array_keys($skillCounts) as $skill) {
            $analysis = $this->analyzeSkillDemand($skill);
            
            // Only recommend emerging, hot, or stable skills
            if (in_array($analysis['trend_status'], ['emerging', 'hot', 'stable'])) {
                $recommendedSkills[] = $analysis;
            }
        }
        
        return $recommendedSkills;
    }

    /**
     * Track skill evolution over time
     */
    public function trackSkillEvolution(string $skill, int $months = 12): array
    {
        $trends = SkillTrend::where('skill_name', $skill)
            ->where('trend_date', '>=', now()->subMonths($months))
            ->orderBy('trend_date')
            ->get();
        
        if ($trends->isEmpty()) {
            return [
                'skill' => $skill,
                'data_points' => 0,
                'message' => 'No historical data available',
            ];
        }
        
        // Format for charting
        $evolution = $trends->map(function($trend) {
            return [
                'date' => $trend->trend_date,
                'demand_score' => $trend->demand_score,
                'growth_rate' => $trend->growth_rate,
                'value_score' => $trend->value_score,
                'trend_status' => $trend->trend_status,
            ];
        })->toArray();
        
        // Calculate summary statistics
        $demandChange = $trends->last()->demand_score - $trends->first()->demand_score;
        $avgGrowthRate = $trends->avg('growth_rate');
        $peakDemand = $trends->max('demand_score');
        $lowestDemand = $trends->min('demand_score');
        
        return [
            'skill' => $skill,
            'data_points' => $trends->count(),
            'evolution' => $evolution,
            'summary' => [
                'demand_change' => round($demandChange, 2),
                'avg_growth_rate' => round($avgGrowthRate, 2),
                'peak_demand' => round($peakDemand, 2),
                'lowest_demand' => round($lowestDemand, 2),
                'current_status' => $trends->last()->trend_status,
            ],
        ];
    }

    /**
     * Store skill trend in database
     */
    protected function storeSkillTrend(string $skill, array $data): void
    {
        SkillTrend::create([
            'skill_name' => $skill,
            'demand_score' => $data['demand_score'],
            'growth_rate' => $data['growth_rate'],
            'salary_premium' => $data['salary_premium'],
            'value_score' => $data['value_score'],
            'trend_status' => $data['trend_status'],
            'trend_velocity' => $data['trend_velocity'],
            'trend_date' => now()->toDateString(),
        ]);
    }

    /**
     * Determine trend status based on metrics
     */
    protected function determineTrendStatus(float $demandScore, float $growthRate): string
    {
        if ($growthRate > 20 && $demandScore > 30) return 'emerging';
        if ($growthRate > 10 && $demandScore > 50) return 'hot';
        if ($growthRate > -5 && $growthRate < 5) return 'stable';
        if ($growthRate < -10) return 'declining';
        if ($demandScore < 10 && $growthRate < -5) return 'obsolete';
        
        return 'stable';
    }

    /**
     * Estimate skill learning difficulty
     */
    protected function estimateSkillDifficulty(string $skill): string
    {
        // Simple heuristic based on skill type
        $advancedSkills = ['Machine Learning', 'Kubernetes', 'AWS', 'System Design', 'Microservices'];
        $intermediateSkills = ['React', 'Node.js', 'Python', 'Docker', 'SQL'];
        
        if (in_array($skill, $advancedSkills)) return 'advanced';
        if (in_array($skill, $intermediateSkills)) return 'intermediate';
        
        return 'beginner';
    }

    /**
     * Helper: Get user's primary role
     */
    protected function getUserPrimaryRole(User $user): string
    {
        $recentJob = $user->applications()
            ->with('job')
            ->latest()
            ->first();
        
        if ($recentJob) {
            return $recentJob->job->title;
        }
        
        $profile = $user->profile;
        if ($profile && !empty($profile->experience)) {
            $latestExp = collect($profile->experience)->sortByDesc('end_date')->first();
            return $latestExp['title'] ?? 'Software Engineer';
        }
        
        return 'Software Engineer';
    }
}
