<?php

namespace App\Services\AI;

use App\Models\SalaryTrend;
use App\Models\Job;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Carbon\Carbon;

/**
 * Salary Intelligence Service
 * 
 * Tracks salary trends, calculates percentile rankings, predicts compensation
 * movements, and compares across cities/companies.
 */
class SalaryIntelligenceService
{
    /**
     * Analyze salary trends for specific role/location
     */
    public function analyzeSalaryTrends(string $role, ?string $location = null, ?int $experienceYears = null): array
    {
        $cacheKey = "salary_trends_{$role}_" . ($location ?? 'all') . "_" . ($experienceYears ?? 'all');
        
        return Cache::remember($cacheKey, 3600, function() use ($role, $location, $experienceYears) {
            // Gather salary data
            $salaryData = $this->gatherSalaryData($role, $location, $experienceYears);
            
            // Calculate statistics
            $statistics = $this->calculateSalaryStatistics($salaryData);
            
            // Calculate trends
            $trends = $this->calculateSalaryTrend($role, $location);
            
            // Generate predictions
            $predictions = $this->predictSalaryMovement($role, $location, $statistics, $trends);
            
            // Store trend data
            $this->storeSalaryTrend($role, $location, $experienceYears, $statistics, $trends, $predictions);
            
            return [
                'statistics' => $statistics,
                'trends' => $trends,
                'predictions' => $predictions,
                'sample_size' => count($salaryData),
                'last_updated' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Gather salary data from job postings
     */
    protected function gatherSalaryData(string $role, ?string $location, ?int $experienceYears): array
    {
        $query = Job::query()
            ->where('title', 'like', "%{$role}%")
            ->whereNotNull('min_salary')
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(90));
        
        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }
        
        if ($experienceYears !== null) {
            // Filter by experience range (±1 year)
            $query->where(function($q) use ($experienceYears) {
                $q->whereBetween('min_experience_years', [
                    max(0, $experienceYears - 1),
                    $experienceYears + 1
                ]);
            });
        }
        
        return $query->get()->map(function($job) {
            return [
                'min_salary' => $job->min_salary,
                'max_salary' => $job->max_salary,
                'avg_salary' => ($job->min_salary + $job->max_salary) / 2,
                'company_size' => $job->company->company_size ?? 'unknown',
                'is_remote' => $job->is_remote,
                'posted_at' => $job->created_at,
            ];
        })->toArray();
    }

    /**
     * Calculate salary statistics
     */
    protected function calculateSalaryStatistics(array $salaryData): array
    {
        if (empty($salaryData)) {
            return [
                'min' => 0,
                'max' => 0,
                'median' => 0,
                'average' => 0,
                'percentile_25' => 0,
                'percentile_75' => 0,
                'percentile_90' => 0,
            ];
        }
        
        $salaries = array_column($salaryData, 'avg_salary');
        sort($salaries);
        
        return [
            'min' => min($salaries),
            'max' => max($salaries),
            'median' => $this->calculateMedian($salaries),
            'average' => array_sum($salaries) / count($salaries),
            'percentile_25' => $this->calculatePercentile($salaries, 25),
            'percentile_75' => $this->calculatePercentile($salaries, 75),
            'percentile_90' => $this->calculatePercentile($salaries, 90),
        ];
    }

    /**
     * Calculate salary trend (month-over-month, year-over-year)
     */
    protected function calculateSalaryTrend(string $role, ?string $location): array
    {
        // Get current month data
        $currentMonth = Job::where('title', 'like', "%{$role}%")
            ->when($location, fn($q) => $q->where('location', 'like', "%{$location}%"))
            ->whereNotNull('min_salary')
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('min_salary') ?? 0;
        
        // Get last month data
        $lastMonth = Job::where('title', 'like', "%{$role}%")
            ->when($location, fn($q) => $q->where('location', 'like', "%{$location}%"))
            ->whereNotNull('min_salary')
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->avg('min_salary') ?? 0;
        
        // Get last year data
        $lastYear = Job::where('title', 'like', "%{$role}%")
            ->when($location, fn($q) => $q->where('location', 'like', "%{$location}%"))
            ->whereNotNull('min_salary')
            ->whereBetween('created_at', [now()->subYear(), now()->subYear()->addDays(30)])
            ->avg('min_salary') ?? 0;
        
        // Calculate changes
        $momChange = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;
        $yoyChange = $lastYear > 0 ? (($currentMonth - $lastYear) / $lastYear) * 100 : 0;
        
        // Determine trend direction
        $direction = 'stable';
        if ($momChange > 2) $direction = 'rising';
        elseif ($momChange < -2) $direction = 'falling';
        
        return [
            'current_avg' => round($currentMonth, 2),
            'month_over_month_change' => round($momChange, 2),
            'year_over_year_change' => round($yoyChange, 2),
            'direction' => $direction,
        ];
    }

    /**
     * Predict future salary movements
     */
    protected function predictSalaryMovement(string $role, ?string $location, array $statistics, array $trends): array
    {
        // Get historical trend
        $historicalTrends = SalaryTrend::where('role', $role)
            ->when($location, fn($q) => $q->where('location', $location))
            ->where('trend_date', '>=', now()->subMonths(12))
            ->orderBy('trend_date')
            ->get();
        
        if ($historicalTrends->count() < 3) {
            // Not enough data for accurate prediction
            return [
                'predicted_6m' => 0,
                'predicted_12m' => 0,
                'confidence' => 'low',
            ];
        }
        
        // Calculate average monthly change
        $changes = [];
        for ($i = 1; $i < $historicalTrends->count(); $i++) {
            $prev = $historicalTrends[$i - 1]->average_salary;
            $curr = $historicalTrends[$i]->average_salary;
            
            if ($prev > 0) {
                $changes[] = (($curr - $prev) / $prev) * 100;
            }
        }
        
        $avgMonthlyChange = !empty($changes) ? array_sum($changes) / count($changes) : 0;
        
        // Apply momentum (recent trends have more weight)
        $recentChange = $trends['month_over_month_change'];
        $momentum = ($avgMonthlyChange * 0.3) + ($recentChange * 0.7);
        
        // Predict future values
        $currentAvg = $statistics['average'];
        $predicted6m = $currentAvg * (1 + ($momentum * 6) / 100);
        $predicted12m = $currentAvg * (1 + ($momentum * 12) / 100);
        
        // Calculate confidence based on data consistency
        $variance = $this->calculateVariance($changes);
        $confidence = $variance < 5 ? 'high' : ($variance < 15 ? 'medium' : 'low');
        
        return [
            'predicted_6m' => round(($predicted6m - $currentAvg) / $currentAvg * 100, 2),
            'predicted_12m' => round(($predicted12m - $currentAvg) / $currentAvg * 100, 2),
            'confidence' => $confidence,
            'momentum' => round($momentum, 2),
        ];
    }

    /**
     * Store salary trend in database
     */
    protected function storeSalaryTrend(
        string $role,
        ?string $location,
        ?int $experienceYears,
        array $statistics,
        array $trends,
        array $predictions
    ): void {
        SalaryTrend::create([
            'role' => $role,
            'location' => $location ?? 'Global',
            'experience_years' => $experienceYears,
            'min_salary' => $statistics['min'],
            'max_salary' => $statistics['max'],
            'median_salary' => $statistics['median'],
            'average_salary' => $statistics['average'],
            'percentile_25' => $statistics['percentile_25'],
            'percentile_75' => $statistics['percentile_75'],
            'percentile_90' => $statistics['percentile_90'],
            'month_over_month_change' => $trends['month_over_month_change'],
            'year_over_year_change' => $trends['year_over_year_change'],
            'trend_direction' => $trends['direction'],
            'predicted_change_6m' => $predictions['predicted_6m'],
            'predicted_change_12m' => $predictions['predicted_12m'],
            'trend_date' => now()->toDateString(),
        ]);
    }

    /**
     * Calculate user's salary percentile
     */
    public function calculateUserSalaryPercentile(User $user): array
    {
        $profile = $user->profile;
        
        if (!$profile || !$profile->current_salary) {
            return [
                'percentile' => null,
                'status' => 'no_data',
                'message' => 'No salary data available',
            ];
        }
        
        // Determine user's role
        $role = $this->getUserPrimaryRole($user);
        $location = $profile->location ?? null;
        $experienceYears = $this->calculateExperienceYears($profile);
        
        // Get salary trend for user's role
        $trend = SalaryTrend::where('role', 'like', "%{$role}%")
            ->when($location, fn($q) => $q->where('location', 'like', "%{$location}%"))
            ->latest('trend_date')
            ->first();
        
        if (!$trend) {
            // No trend data, analyze on the fly
            $trendData = $this->analyzeSalaryTrends($role, $location, $experienceYears);
            $statistics = $trendData['statistics'];
        } else {
            $statistics = [
                'percentile_25' => $trend->percentile_25,
                'median' => $trend->median_salary,
                'percentile_75' => $trend->percentile_75,
                'percentile_90' => $trend->percentile_90,
                'average' => $trend->average_salary,
            ];
        }
        
        // Calculate user's percentile
        $userSalary = $profile->current_salary;
        $percentile = $this->interpolatePercentile($userSalary, $statistics);
        
        // Determine status
        $status = 'fair';
        if ($percentile >= 75) $status = 'excellent';
        elseif ($percentile >= 50) $status = 'good';
        elseif ($percentile >= 25) $status = 'fair';
        else $status = 'below_market';
        
        // Calculate difference from median
        $diffFromMedian = $statistics['median'] > 0 
            ? (($userSalary - $statistics['median']) / $statistics['median']) * 100 
            : 0;
        
        return [
            'percentile' => round($percentile, 1),
            'status' => $status,
            'current_salary' => $userSalary,
            'market_median' => $statistics['median'],
            'market_average' => $statistics['average'],
            'diff_from_median' => round($diffFromMedian, 1),
            'percentile_25' => $statistics['percentile_25'],
            'percentile_75' => $statistics['percentile_75'],
            'percentile_90' => $statistics['percentile_90'],
        ];
    }

    /**
     * Compare salaries across cities
     */
    public function compareSalariesAcrossCities(string $role, array $cities): array
    {
        $comparisons = [];
        
        foreach ($cities as $city) {
            $data = $this->analyzeSalaryTrends($role, $city);
            
            $comparisons[$city] = [
                'average' => $data['statistics']['average'],
                'median' => $data['statistics']['median'],
                'percentile_75' => $data['statistics']['percentile_75'],
                'trend' => $data['trends']['direction'],
                'yoy_change' => $data['trends']['year_over_year_change'],
                'sample_size' => $data['sample_size'],
            ];
        }
        
        // Sort by average salary
        uasort($comparisons, fn($a, $b) => $b['average'] <=> $a['average']);
        
        return $comparisons;
    }

    /**
     * Compare salaries across companies
     */
    public function compareSalariesAcrossCompanies(string $role, array $companyIds = []): array
    {
        $query = Job::where('title', 'like', "%{$role}%")
            ->whereNotNull('min_salary')
            ->where('created_at', '>=', now()->subDays(90));
        
        if (!empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }
        
        $jobs = $query->with('company')->get();
        
        $companyData = [];
        
        foreach ($jobs as $job) {
            $companyName = $job->company->name ?? 'Unknown';
            
            if (!isset($companyData[$companyName])) {
                $companyData[$companyName] = [
                    'salaries' => [],
                    'company_size' => $job->company->company_size ?? 'unknown',
                    'is_verified' => $job->company->is_verified ?? false,
                ];
            }
            
            $companyData[$companyName]['salaries'][] = ($job->min_salary + $job->max_salary) / 2;
        }
        
        // Calculate statistics for each company
        $comparisons = [];
        foreach ($companyData as $company => $data) {
            $salaries = $data['salaries'];
            
            $comparisons[$company] = [
                'average' => array_sum($salaries) / count($salaries),
                'median' => $this->calculateMedian($salaries),
                'min' => min($salaries),
                'max' => max($salaries),
                'company_size' => $data['company_size'],
                'is_verified' => $data['is_verified'],
                'job_count' => count($salaries),
            ];
        }
        
        // Sort by average
        uasort($comparisons, fn($a, $b) => $b['average'] <=> $a['average']);
        
        return $comparisons;
    }

    /**
     * Generate salary negotiation insights
     */
    public function generateNegotiationInsights(User $user, float $offeredSalary, string $role): array
    {
        $userPercentile = $this->calculateUserSalaryPercentile($user);
        $trendData = $this->analyzeSalaryTrends($role, $user->profile->location ?? null);
        
        $marketMedian = $trendData['statistics']['median'];
        $marketP75 = $trendData['statistics']['percentile_75'];
        $marketP90 = $trendData['statistics']['percentile_90'];
        
        // Calculate offer strength
        $offerPercentile = $this->interpolatePercentile($offeredSalary, $trendData['statistics']);
        
        // Determine recommendation
        $recommendation = 'negotiate';
        $targetSalary = $marketP75; // Default target: 75th percentile
        
        if ($offerPercentile >= 75) {
            $recommendation = 'accept';
            $targetSalary = $offeredSalary;
        } elseif ($offerPercentile >= 50) {
            $recommendation = 'consider';
            $targetSalary = $marketP75;
        }
        
        // Calculate negotiation range
        $minAcceptable = $marketMedian;
        $idealTarget = $marketP75;
        $stretchGoal = $marketP90;
        
        // Generate talking points
        $talkingPoints = $this->generateNegotiationTalkingPoints(
            $offeredSalary,
            $trendData,
            $userPercentile,
            $user
        );
        
        return [
            'offer_percentile' => round($offerPercentile, 1),
            'recommendation' => $recommendation,
            'target_salary' => round($targetSalary, 2),
            'min_acceptable' => round($minAcceptable, 2),
            'ideal_target' => round($idealTarget, 2),
            'stretch_goal' => round($stretchGoal, 2),
            'market_data' => [
                'median' => $marketMedian,
                'p75' => $marketP75,
                'p90' => $marketP90,
            ],
            'talking_points' => $talkingPoints,
        ];
    }

    /**
     * Generate negotiation talking points
     */
    protected function generateNegotiationTalkingPoints(
        float $offeredSalary,
        array $trendData,
        array $userPercentile,
        User $user
    ): array {
        $points = [];
        
        // Market data point
        $marketMedian = $trendData['statistics']['median'];
        if ($offeredSalary < $marketMedian) {
            $diff = (($marketMedian - $offeredSalary) / $offeredSalary) * 100;
            $points[] = [
                'category' => 'market_data',
                'point' => "The offered salary is " . round($diff, 1) . "% below the market median of ₹" . number_format($marketMedian, 0),
                'strength' => 'high',
            ];
        }
        
        // Growth trend point
        if ($trendData['trends']['year_over_year_change'] > 5) {
            $points[] = [
                'category' => 'market_trend',
                'point' => "Salaries for this role have increased by " . round($trendData['trends']['year_over_year_change'], 1) . "% year-over-year",
                'strength' => 'medium',
            ];
        }
        
        // Experience point
        $profile = $user->profile;
        if ($profile) {
            $experienceYears = $this->calculateExperienceYears($profile);
            if ($experienceYears >= 5) {
                $points[] = [
                    'category' => 'experience',
                    'point' => "With {$experienceYears} years of experience, I bring proven expertise that justifies above-median compensation",
                    'strength' => 'high',
                ];
            }
        }
        
        // Skills point
        if ($userPercentile['percentile'] && $userPercentile['percentile'] >= 75) {
            $points[] = [
                'category' => 'skills',
                'point' => "My skill set places me in the top 25% of candidates in the market",
                'strength' => 'high',
            ];
        }
        
        return $points;
    }

    /**
     * Helper: Calculate median
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
     * Helper: Calculate percentile
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
     * Helper: Interpolate user's percentile from statistics
     */
    protected function interpolatePercentile(float $salary, array $statistics): float
    {
        if ($salary <= $statistics['percentile_25']) {
            // Below 25th percentile
            $range = $statistics['percentile_25'] - $statistics['min'];
            if ($range > 0) {
                $position = $salary - $statistics['min'];
                return ($position / $range) * 25;
            }
            return 0;
        }
        
        if ($salary <= $statistics['median']) {
            // Between 25th and 50th
            $range = $statistics['median'] - $statistics['percentile_25'];
            if ($range > 0) {
                $position = $salary - $statistics['percentile_25'];
                return 25 + (($position / $range) * 25);
            }
            return 25;
        }
        
        if ($salary <= $statistics['percentile_75']) {
            // Between 50th and 75th
            $range = $statistics['percentile_75'] - $statistics['median'];
            if ($range > 0) {
                $position = $salary - $statistics['median'];
                return 50 + (($position / $range) * 25);
            }
            return 50;
        }
        
        if ($salary <= $statistics['percentile_90']) {
            // Between 75th and 90th
            $range = $statistics['percentile_90'] - $statistics['percentile_75'];
            if ($range > 0) {
                $position = $salary - $statistics['percentile_75'];
                return 75 + (($position / $range) * 15);
            }
            return 75;
        }
        
        // Above 90th percentile
        return min(99, 90 + (($salary - $statistics['percentile_90']) / $statistics['percentile_90']) * 10);
    }

    /**
     * Helper: Calculate variance
     */
    protected function calculateVariance(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return sqrt($variance / count($values));
    }

    /**
     * Helper: Get user's primary role
     */
    protected function getUserPrimaryRole(User $user): string
    {
        // Try to get from recent applications
        $recentJob = $user->applications()
            ->with('job')
            ->latest()
            ->first();
        
        if ($recentJob) {
            return $recentJob->job->title;
        }
        
        // Try to get from profile experience
        $profile = $user->profile;
        if ($profile && !empty($profile->experience)) {
            $latestExp = collect($profile->experience)->sortByDesc('end_date')->first();
            return $latestExp['title'] ?? 'Software Engineer';
        }
        
        return 'Software Engineer'; // Default
    }

    /**
     * Helper: Calculate experience years from profile
     */
    protected function calculateExperienceYears(?Profile $profile): int
    {
        if (!$profile || empty($profile->experience)) return 0;
        
        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $start = Carbon::parse($exp['start_date'] ?? now());
            $end = isset($exp['end_date']) ? Carbon::parse($exp['end_date']) : now();
            $totalMonths += $start->diffInMonths($end);
        }
        
        return (int)($totalMonths / 12);
    }
}
