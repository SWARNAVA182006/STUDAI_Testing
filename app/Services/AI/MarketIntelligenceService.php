<?php

namespace App\Services\AI;

use App\Models\MarketDataSnapshot;
use App\Models\Job;
use App\Models\Application;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Market Intelligence Service
 * 
 * Analyzes millions of job postings, salary reports, and industry trends
 * to provide real-time market insights and predictions.
 */
class MarketIntelligenceService
{
    use InteractsWithAI;
    /**
     * Analyze overall job market trends
     */
    public function analyzeJobMarket(string $role = null, string $location = null, string $industry = null): array
    {
        $cacheKey = "market_analysis_" . md5($role . $location . $industry);
        
        return Cache::remember($cacheKey, 3600, function() use ($role, $location, $industry) {
            // Gather market data
            $marketData = $this->gatherMarketData($role, $location, $industry);
            
            // Calculate demand/supply metrics
            $demandSupply = $this->calculateDemandSupply($marketData);
            
            // Identify trends
            $trends = $this->identifyTrends($marketData);
            
            // Get AI insights
            $aiInsights = $this->generateAIInsights($marketData, $trends);
            
            // Store snapshot
            $this->storeMarketSnapshot('job_market', $role, $location, $industry, [
                'demand_supply' => $demandSupply,
                'trends' => $trends,
                'sample_size' => $marketData['sample_size'],
            ], $aiInsights);
            
            return [
                'demand_supply' => $demandSupply,
                'trends' => $trends,
                'insights' => $aiInsights,
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Gather raw market data from multiple sources
     */
    protected function gatherMarketData(?string $role, ?string $location, ?string $industry): array
    {
        $query = Job::query()
            ->where('status', 'published')
            ->where('expires_at', '>', now());
        
        if ($role) {
            $query->where(function($q) use ($role) {
                $q->where('title', 'like', "%{$role}%")
                  ->orWhereJsonContains('extracted_skills->roles', $role);
            });
        }
        
        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }
        
        if ($industry) {
            $query->whereHas('company', function($q) use ($industry) {
                $q->where('industry', 'like', "%{$industry}%");
            });
        }
        
        // Get jobs from last 90 days for trend analysis
        $recentJobs = (clone $query)
            ->where('created_at', '>=', now()->subDays(90))
            ->get();
        
        // Get historical data (90-180 days ago)
        $historicalJobs = (clone $query)
            ->whereBetween('created_at', [now()->subDays(180), now()->subDays(90)])
            ->get();
        
        // Calculate metrics
        $currentJobCount = $recentJobs->count();
        $historicalJobCount = $historicalJobs->count();
        
        // Application data
        $applicationStats = Application::whereIn('job_id', $recentJobs->pluck('id'))
            ->select(
                DB::raw('COUNT(*) as total_applications'),
                DB::raw('AVG(CASE WHEN status IN ("interview", "offer", "accepted") THEN 1 ELSE 0 END) as success_rate')
            )
            ->first();
        
        return [
            'sample_size' => $currentJobCount,
            'current_jobs' => $recentJobs,
            'historical_jobs' => $historicalJobs,
            'current_count' => $currentJobCount,
            'historical_count' => $historicalJobCount,
            'total_applications' => $applicationStats->total_applications ?? 0,
            'success_rate' => $applicationStats->success_rate ?? 0,
        ];
    }

    /**
     * Calculate demand/supply metrics
     */
    protected function calculateDemandSupply(array $marketData): array
    {
        $currentCount = $marketData['current_count'];
        $historicalCount = $marketData['historical_count'];
        $applications = $marketData['total_applications'];
        
        // Calculate growth rate
        $growthRate = $historicalCount > 0 
            ? (($currentCount - $historicalCount) / $historicalCount) * 100 
            : 0;
        
        // Calculate supply/demand ratio (applications per job)
        $supplyDemandRatio = $currentCount > 0 
            ? $applications / $currentCount 
            : 0;
        
        // Demand score (0-100)
        $demandScore = min(100, max(0, 50 + ($growthRate * 2))); // Base 50, ±2 points per % change
        
        // Supply score (inverse of applications per job, normalized)
        $avgApplicationsPerJob = 50; // Industry average
        $supplyScore = min(100, max(0, ($supplyDemandRatio / $avgApplicationsPerJob) * 100));
        
        // Market health score
        $marketHealth = ($demandScore + (100 - $supplyScore)) / 2;
        
        return [
            'demand_score' => round($demandScore, 2),
            'supply_score' => round($supplyScore, 2),
            'market_health' => round($marketHealth, 2),
            'growth_rate' => round($growthRate, 2),
            'jobs_available' => $currentCount,
            'applications_per_job' => round($supplyDemandRatio, 2),
            'competition_level' => $this->classifyCompetition($supplyDemandRatio),
        ];
    }

    /**
     * Identify market trends
     */
    protected function identifyTrends(array $marketData): array
    {
        $current = $marketData['current_jobs'];
        $historical = $marketData['historical_jobs'];
        
        // Skill trends
        $skillTrends = $this->analyzeSkillTrends($current, $historical);
        
        // Salary trends
        $salaryTrends = $this->analyzeSalaryTrends($current, $historical);
        
        // Remote work trends
        $remoteWorkTrends = $this->analyzeRemoteWorkTrends($current, $historical);
        
        // Company size trends
        $companySizeTrends = $this->analyzeCompanySizeTrends($current, $historical);
        
        return [
            'skills' => $skillTrends,
            'salaries' => $salaryTrends,
            'remote_work' => $remoteWorkTrends,
            'company_sizes' => $companySizeTrends,
        ];
    }

    /**
     * Analyze skill demand trends
     */
    protected function analyzeSkillTrends($currentJobs, $historicalJobs): array
    {
        // Extract skills from current jobs
        $currentSkills = [];
        foreach ($currentJobs as $job) {
            $skills = $job->extracted_skills['required_skills'] ?? [];
            foreach ($skills as $skill) {
                $currentSkills[$skill] = ($currentSkills[$skill] ?? 0) + 1;
            }
        }
        
        // Extract skills from historical jobs
        $historicalSkills = [];
        foreach ($historicalJobs as $job) {
            $skills = $job->extracted_skills['required_skills'] ?? [];
            foreach ($skills as $skill) {
                $historicalSkills[$skill] = ($historicalSkills[$skill] ?? 0) + 1;
            }
        }
        
        // Calculate trends
        $trends = [];
        foreach ($currentSkills as $skill => $currentCount) {
            $historicalCount = $historicalSkills[$skill] ?? 0;
            $change = $historicalCount > 0 
                ? (($currentCount - $historicalCount) / $historicalCount) * 100 
                : 100; // New skill
            
            $trends[] = [
                'skill' => $skill,
                'current_demand' => $currentCount,
                'change_percentage' => round($change, 2),
                'trend' => $this->classifyTrend($change),
            ];
        }
        
        // Sort by current demand
        usort($trends, fn($a, $b) => $b['current_demand'] <=> $a['current_demand']);
        
        return array_slice($trends, 0, 20); // Top 20 skills
    }

    /**
     * Analyze salary trends
     */
    protected function analyzeSalaryTrends($currentJobs, $historicalJobs): array
    {
        $currentSalaries = $currentJobs->pluck('min_salary', 'max_salary')->filter()->values();
        $historicalSalaries = $historicalJobs->pluck('min_salary', 'max_salary')->filter()->values();
        
        $currentAvg = $currentJobs->avg('min_salary') ?? 0;
        $historicalAvg = $historicalJobs->avg('min_salary') ?? 0;
        
        $change = $historicalAvg > 0 
            ? (($currentAvg - $historicalAvg) / $historicalAvg) * 100 
            : 0;
        
        return [
            'average_salary' => round($currentAvg, 2),
            'median_salary' => $this->calculateMedian($currentJobs->pluck('min_salary')->filter()->toArray()),
            'change_percentage' => round($change, 2),
            'trend' => $this->classifyTrend($change),
            'percentile_25' => $this->calculatePercentile($currentJobs->pluck('min_salary')->filter()->toArray(), 25),
            'percentile_75' => $this->calculatePercentile($currentJobs->pluck('min_salary')->filter()->toArray(), 75),
            'percentile_90' => $this->calculatePercentile($currentJobs->pluck('min_salary')->filter()->toArray(), 90),
        ];
    }

    /**
     * Analyze remote work trends
     */
    protected function analyzeRemoteWorkTrends($currentJobs, $historicalJobs): array
    {
        $currentRemote = $currentJobs->where('is_remote', true)->count();
        $currentTotal = $currentJobs->count();
        
        $historicalRemote = $historicalJobs->where('is_remote', true)->count();
        $historicalTotal = $historicalJobs->count();
        
        $currentPercentage = $currentTotal > 0 ? ($currentRemote / $currentTotal) * 100 : 0;
        $historicalPercentage = $historicalTotal > 0 ? ($historicalRemote / $historicalTotal) * 100 : 0;
        
        $change = $currentPercentage - $historicalPercentage;
        
        return [
            'remote_percentage' => round($currentPercentage, 2),
            'change_percentage' => round($change, 2),
            'trend' => $this->classifyTrend($change),
        ];
    }

    /**
     * Analyze company size trends
     */
    protected function analyzeCompanySizeTrends($currentJobs, $historicalJobs): array
    {
        $currentSizes = $currentJobs->groupBy(fn($job) => $job->company->company_size ?? 'unknown')
            ->map->count();
        
        $historicalSizes = $historicalJobs->groupBy(fn($job) => $job->company->company_size ?? 'unknown')
            ->map->count();
        
        $trends = [];
        foreach ($currentSizes as $size => $count) {
            $historicalCount = $historicalSizes[$size] ?? 0;
            $change = $historicalCount > 0 
                ? (($count - $historicalCount) / $historicalCount) * 100 
                : 100;
            
            $trends[$size] = [
                'current_count' => $count,
                'change_percentage' => round($change, 2),
                'trend' => $this->classifyTrend($change),
            ];
        }
        
        return $trends;
    }

    /**
     * Generate AI insights using GPT-4
     */
    protected function generateAIInsights(array $marketData, array $trends): string
    {
        try {
            $prompt = $this->buildInsightPrompt($marketData, $trends);
            
            return $this->ai(
                $prompt,
                'You are a market intelligence analyst specializing in job market trends and career insights. Provide concise, actionable insights.',
                ['temperature' => 0.7]
            );
            
        } catch (\Exception $e) {
            Log::error('Market Intelligence AI Error: ' . $e->getMessage());
            return 'AI insights temporarily unavailable. Market analysis shows ' . 
                   $this->getFallbackInsight($marketData, $trends);
        }
    }

    /**
     * Build prompt for AI insights
     */
    protected function buildInsightPrompt(array $marketData, array $trends): string
    {
        $demandSupply = $marketData['demand_supply'] ?? [];
        
        return "Analyze this job market data and provide actionable insights:\n\n" .
               "Market Metrics:\n" .
               "- Jobs Available: {$marketData['current_count']}\n" .
               "- Growth Rate: {$demandSupply['growth_rate']}%\n" .
               "- Applications per Job: {$demandSupply['applications_per_job']}\n" .
               "- Market Health Score: {$demandSupply['market_health']}/100\n\n" .
               "Top Trending Skills:\n" .
               $this->formatSkillsForPrompt($trends['skills'] ?? []) . "\n\n" .
               "Salary Trend: {$trends['salaries']['change_percentage']}%\n\n" .
               "Provide: 1) Market outlook, 2) Opportunities, 3) Risks, 4) Recommendations";
    }

    /**
     * Format skills for AI prompt
     */
    protected function formatSkillsForPrompt(array $skills): string
    {
        $formatted = [];
        foreach (array_slice($skills, 0, 10) as $skill) {
            $formatted[] = "- {$skill['skill']}: {$skill['change_percentage']}% change";
        }
        return implode("\n", $formatted);
    }

    /**
     * Get fallback insight when AI fails
     */
    protected function getFallbackInsight(array $marketData, array $trends): string
    {
        $demandSupply = $marketData['demand_supply'] ?? [];
        $growthRate = $demandSupply['growth_rate'] ?? 0;
        
        if ($growthRate > 10) {
            return "strong growth ({$growthRate}%) with high demand. Consider applying soon to capitalize on expanding opportunities.";
        } elseif ($growthRate < -10) {
            return "declining demand ({$growthRate}%). Focus on skill development and consider adjacent roles.";
        } else {
            return "stable market conditions. Focus on standing out through specialized skills and experience.";
        }
    }

    /**
     * Store market snapshot in database
     */
    protected function storeMarketSnapshot(
        string $type,
        ?string $role,
        ?string $location,
        ?string $industry,
        array $metrics,
        string $aiAnalysis
    ): void {
        MarketDataSnapshot::create([
            'snapshot_type' => $type,
            'role' => $role,
            'location' => $location,
            'industry' => $industry,
            'sample_size' => $metrics['sample_size'] ?? 0,
            'metrics' => $metrics,
            'ai_analysis' => $aiAnalysis,
            'snapshot_date' => now()->toDateString(),
            'analyzed_at' => now(),
        ]);
    }

    /**
     * Classify competition level
     */
    protected function classifyCompetition(float $applicationsPerJob): string
    {
        if ($applicationsPerJob < 20) return 'low';
        if ($applicationsPerJob < 50) return 'moderate';
        if ($applicationsPerJob < 100) return 'high';
        return 'very_high';
    }

    /**
     * Classify trend direction
     */
    protected function classifyTrend(float $changePercentage): string
    {
        if ($changePercentage > 20) return 'rapidly_rising';
        if ($changePercentage > 5) return 'rising';
        if ($changePercentage > -5) return 'stable';
        if ($changePercentage > -20) return 'declining';
        return 'rapidly_declining';
    }

    /**
     * Calculate median of array
     */
    protected function calculateMedian(array $values): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        
        return $values[$middle];
    }

    /**
     * Calculate percentile
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        $index = ($percentile / 100) * ($count - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $fraction = $index - floor($index);
        
        return $lower + ($upper - $lower) * $fraction;
    }

    /**
     * Get market overview for dashboard
     */
    public function getMarketOverview(): array
    {
        return [
            'overall_market' => $this->analyzeJobMarket(),
            'top_roles' => $this->getTopRoles(),
            'top_locations' => $this->getTopLocations(),
            'emerging_skills' => $this->getEmergingSkills(),
            'salary_trends' => $this->getSalaryTrendsOverview(),
        ];
    }

    /**
     * Get top roles by demand
     */
    protected function getTopRoles(int $limit = 10): array
    {
        return Job::select('title', DB::raw('COUNT(*) as count'))
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('title')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get top locations by job count
     */
    protected function getTopLocations(int $limit = 10): array
    {
        return Job::select('location', DB::raw('COUNT(*) as count'))
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get emerging skills (fastest growing)
     */
    protected function getEmergingSkills(int $limit = 15): array
    {
        // This would typically query the skill_trends table
        // For now, analyze recent vs. older jobs
        $recent = Job::where('created_at', '>=', now()->subDays(30))->get();
        $older = Job::whereBetween('created_at', [now()->subDays(90), now()->subDays(30)])->get();
        
        $recentSkills = $this->extractAllSkills($recent);
        $olderSkills = $this->extractAllSkills($older);
        
        $emerging = [];
        foreach ($recentSkills as $skill => $count) {
            $oldCount = $olderSkills[$skill] ?? 0;
            $growth = $oldCount > 0 ? (($count - $oldCount) / $oldCount) * 100 : 100;
            
            if ($growth > 20) { // Only skills with >20% growth
                $emerging[] = [
                    'skill' => $skill,
                    'growth_rate' => round($growth, 2),
                    'current_demand' => $count,
                ];
            }
        }
        
        usort($emerging, fn($a, $b) => $b['growth_rate'] <=> $a['growth_rate']);
        
        return array_slice($emerging, 0, $limit);
    }

    /**
     * Extract all skills from jobs
     */
    protected function extractAllSkills($jobs): array
    {
        $skills = [];
        foreach ($jobs as $job) {
            $jobSkills = $job->extracted_skills['required_skills'] ?? [];
            foreach ($jobSkills as $skill) {
                $skills[$skill] = ($skills[$skill] ?? 0) + 1;
            }
        }
        return $skills;
    }

    /**
     * Get salary trends overview
     */
    protected function getSalaryTrendsOverview(): array
    {
        $recent = Job::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('min_salary')
            ->get();
        
        $older = Job::whereBetween('created_at', [now()->subDays(90), now()->subDays(30)])
            ->whereNotNull('min_salary')
            ->get();
        
        $recentAvg = $recent->avg('min_salary') ?? 0;
        $olderAvg = $older->avg('min_salary') ?? 0;
        
        $change = $olderAvg > 0 ? (($recentAvg - $olderAvg) / $olderAvg) * 100 : 0;
        
        return [
            'average_salary' => round($recentAvg, 2),
            'change_percentage' => round($change, 2),
            'trend' => $this->classifyTrend($change),
            'sample_size' => $recent->count(),
        ];
    }
}
