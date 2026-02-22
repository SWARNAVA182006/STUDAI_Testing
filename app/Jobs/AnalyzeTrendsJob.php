<?php

namespace App\Jobs;

use App\Models\Job;
use App\Services\AI\SalaryIntelligenceService;
use App\Services\AI\SkillTrendAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Analyze Trends Job
 * 
 * Runs daily to analyze salary trends, skill trends, and role predictions.
 * Processes market data to identify emerging opportunities and declining roles.
 */
class AnalyzeTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Execute the job.
     */
    public function handle(
        SalaryIntelligenceService $salaryIntelligence,
        SkillTrendAnalysisService $skillTrendAnalysis
    ): void {
        Log::info('AnalyzeTrendsJob: Starting trend analysis');

        try {
            // Analyze salary trends
            $this->analyzeSalaryTrends($salaryIntelligence);
            
            // Analyze skill trends
            $this->analyzeSkillTrends($skillTrendAnalysis);
            
            // Analyze role predictions
            $this->analyzeRolePredictions();
            
            Log::info('AnalyzeTrendsJob: Trend analysis completed successfully');
            
        } catch (Exception $e) {
            Log::error('AnalyzeTrendsJob: Failed to analyze trends', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Analyze salary trends for top roles and locations
     */
    protected function analyzeSalaryTrends(SalaryIntelligenceService $salaryIntelligence): void
    {
        Log::info('AnalyzeTrendsJob: Analyzing salary trends');
        
        // Get top roles (most posted jobs in last 90 days)
        $topRoles = Job::select('title', DB::raw('count(*) as job_count'))
            ->where('created_at', '>=', now()->subDays(90))
            ->where('status', 'published')
            ->whereNotNull('min_salary')
            ->groupBy('title')
            ->orderByDesc('job_count')
            ->limit(50)
            ->get();
        
        foreach ($topRoles as $roleData) {
            $role = $roleData->title;
            
            // Analyze salary trends for this role (global)
            $salaryIntelligence->analyzeSalaryTrends($role, null, null);
            
            // Analyze for top locations
            $topLocations = Job::select('location', DB::raw('count(*) as job_count'))
                ->where('title', 'like', "%{$role}%")
                ->where('created_at', '>=', now()->subDays(90))
                ->whereNotNull('location')
                ->groupBy('location')
                ->orderByDesc('job_count')
                ->limit(10)
                ->get();
            
            foreach ($topLocations as $locationData) {
                $location = $locationData->location;
                
                // Analyze salary trends for role in location
                $salaryIntelligence->analyzeSalaryTrends($role, $location, null);
            }
        }
        
        Log::info('AnalyzeTrendsJob: Salary trend analysis completed', [
            'roles_analyzed' => $topRoles->count(),
        ]);
    }

    /**
     * Analyze skill trends
     */
    protected function analyzeSkillTrends(SkillTrendAnalysisService $skillTrendAnalysis): void
    {
        Log::info('AnalyzeTrendsJob: Analyzing skill trends');
        
        // Get all unique skills from job postings
        $jobs = Job::whereNotNull('extracted_skills')
            ->where('created_at', '>=', now()->subDays(90))
            ->get();
        
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
        
        // Filter skills appearing in 5+ jobs (minimum threshold)
        $skillCounts = array_filter($skillCounts, fn($count) => $count >= 5);
        
        // Sort by frequency
        arsort($skillCounts);
        
        // Analyze top 100 skills
        $skillsAnalyzed = 0;
        foreach (array_slice(array_keys($skillCounts), 0, 100) as $skill) {
            try {
                $skillTrendAnalysis->analyzeSkillDemand($skill);
                $skillsAnalyzed++;
            } catch (Exception $e) {
                Log::warning('AnalyzeTrendsJob: Failed to analyze skill trend', [
                    'skill' => $skill,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('AnalyzeTrendsJob: Skill trend analysis completed', [
            'skills_analyzed' => $skillsAnalyzed,
        ]);
    }

    /**
     * Analyze role predictions
     */
    protected function analyzeRolePredictions(): void
    {
        Log::info('AnalyzeTrendsJob: Analyzing role predictions');
        
        // Get job posting trends by role
        $currentMonth = Job::select('title', DB::raw('count(*) as current_count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', 'published')
            ->groupBy('title')
            ->get()
            ->keyBy('title');
        
        $lastMonth = Job::select('title', DB::raw('count(*) as last_count'))
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->where('status', 'published')
            ->groupBy('title')
            ->get()
            ->keyBy('title');
        
        $threeMonthsAgo = Job::select('title', DB::raw('count(*) as old_count'))
            ->whereBetween('created_at', [now()->subDays(120), now()->subDays(90)])
            ->where('status', 'published')
            ->groupBy('title')
            ->get()
            ->keyBy('title');
        
        $rolePredictions = [];
        
        foreach ($currentMonth as $role => $data) {
            $currentCount = $data->current_count;
            $lastCount = $lastMonth->get($role)?->last_count ?? 0;
            $oldCount = $threeMonthsAgo->get($role)?->old_count ?? 0;
            
            // Calculate growth rates
            $momGrowth = $lastCount > 0 ? (($currentCount - $lastCount) / $lastCount) * 100 : 0;
            $qoqGrowth = $oldCount > 0 ? (($currentCount - $oldCount) / $oldCount) * 100 : 0;
            
            // Calculate current demand score (0-100)
            $demandScore = min(100, ($currentCount / 50) * 100); // 50 jobs = 100 score
            
            // Predict future demand (simple linear projection)
            $predicted3m = $currentCount * (1 + ($momGrowth / 100));
            $predicted6m = $currentCount * (1 + ($momGrowth * 2 / 100));
            $predicted12m = $currentCount * (1 + ($qoqGrowth / 100));
            
            // Determine role status
            $roleStatus = 'stable';
            if ($momGrowth > 20 && $demandScore > 30) $roleStatus = 'emerging';
            elseif ($momGrowth > 10 && $demandScore > 50) $roleStatus = 'growing';
            elseif ($momGrowth < -10) $roleStatus = 'declining';
            elseif ($demandScore < 10 && $momGrowth < -5) $roleStatus = 'obsolete';
            
            // Calculate emergence score (for new/emerging roles)
            $emergenceScore = max(0, min(100, $momGrowth + ($demandScore / 2)));
            
            // Calculate stability score
            $volatility = abs($momGrowth - $qoqGrowth);
            $stabilityScore = max(0, 100 - $volatility);
            
            $rolePredictions[] = [
                'role_title' => $role,
                'current_demand_score' => round($demandScore, 2),
                'predicted_demand_3m' => round($predicted3m, 0),
                'predicted_demand_6m' => round($predicted6m, 0),
                'predicted_demand_12m' => round($predicted12m, 0),
                'predicted_salary_change' => round($qoqGrowth, 2), // Proxy for salary change
                'role_status' => $roleStatus,
                'emergence_score' => round($emergenceScore, 2),
                'stability_score' => round($stabilityScore, 2),
                'ai_rationale' => $this->generateRoleRationale($roleStatus, $momGrowth, $demandScore),
                'key_drivers' => $this->identifyKeyDrivers($role),
            ];
        }
        
        // Store role predictions in database
        foreach ($rolePredictions as $prediction) {
            DB::table('role_predictions')->insert(array_merge($prediction, [
                'prediction_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
        
        Log::info('AnalyzeTrendsJob: Role prediction analysis completed', [
            'roles_predicted' => count($rolePredictions),
        ]);
    }

    /**
     * Generate AI rationale for role status
     */
    protected function generateRoleRationale(string $status, float $growth, float $demand): string
    {
        switch ($status) {
            case 'emerging':
                return "This role is showing strong growth ({$growth}% MoM) with healthy demand ({$demand} score). It's an emerging opportunity worth exploring.";
            
            case 'growing':
                return "Steady growth trajectory ({$growth}% MoM) with solid market demand ({$demand} score). Good prospects for career development.";
            
            case 'declining':
                return "Demand is declining ({$growth}% MoM). Consider transitioning to related roles or upskilling to stay competitive.";
            
            case 'obsolete':
                return "Low demand ({$demand} score) and negative growth ({$growth}% MoM). This role may be phasing out of the market.";
            
            default:
                return "Stable market position with consistent demand. Safe career choice with moderate growth potential.";
        }
    }

    /**
     * Identify key drivers for role demand
     */
    protected function identifyKeyDrivers(string $role): array
    {
        // Get jobs for this role
        $jobs = Job::where('title', 'like', "%{$role}%")
            ->whereNotNull('extracted_skills')
            ->where('created_at', '>=', now()->subDays(90))
            ->limit(100)
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
        
        // Get top 5 skills
        arsort($skillCounts);
        return array_slice(array_keys($skillCounts), 0, 5);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('AnalyzeTrendsJob: Job failed after all retries', [
            'error' => $exception->getMessage(),
        ]);
    }
}
