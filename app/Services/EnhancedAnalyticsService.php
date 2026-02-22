<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Job;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\JobMarketHeatmap;
use App\Models\SalaryBenchmark;
use App\Models\SkillDemandForecast;
use App\Models\CareerPathNode;
use App\Models\CareerPathEdge;
use App\Models\ApplicationFunnel;
use App\Models\TimeToHireMetric;
use App\Models\SourceAttribution;
use App\Models\CompetitorSalaryData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Enhanced Analytics Service
 * 
 * Provides comprehensive analytics including:
 * - Interactive job market heatmaps
 * - Real-time salary benchmarking
 * - Skills demand forecasting
 * - Career path visualization
 * - Application funnel analytics
 * - Time-to-hire metrics
 * - Source attribution tracking
 * - Competitor salary comparison
 */
class EnhancedAnalyticsService
{
    /**
     * Get job market heatmap data.
     */
    public function getJobMarketHeatmap(array $filters = []): array
    {
        $cacheKey = 'heatmap_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 1800, function () use ($filters) {
            // Get from precomputed heatmap data if available
            $query = JobMarketHeatmap::query()
                ->where('period_date', '>=', now()->subDays(30))
                ->ofType('daily');
            
            if (!empty($filters['industry'])) {
                $query->forIndustry($filters['industry']);
            }
            
            if (!empty($filters['category'])) {
                $query->forCategory($filters['category']);
            }
            
            $heatmapData = $query->get();
            
            // If no precomputed data, generate from jobs
            if ($heatmapData->isEmpty()) {
                $heatmapData = $this->generateHeatmapFromJobs($filters);
            }
            
            return [
                'points' => $heatmapData->map(fn($h) => $h->toMapData())->toArray(),
                'summary' => $this->getHeatmapSummary($heatmapData),
                'top_locations' => $this->getTopLocations($heatmapData, 10),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Generate heatmap data from live job data.
     */
    protected function generateHeatmapFromJobs(array $filters): Collection
    {
        $query = Job::query()
            ->where('status', 'published')
            ->whereNotNull('location')
            ->select(
                'location',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('AVG(min_salary) as avg_salary'),
                DB::raw('COUNT(DISTINCT company_id) as company_count')
            )
            ->groupBy('location');
        
        if (!empty($filters['industry'])) {
            $query->whereHas('company', function ($q) use ($filters) {
                $q->where('industry', 'like', "%{$filters['industry']}%");
            });
        }
        
        $jobs = $query->limit(100)->get();
        
        return $jobs->map(function ($job) {
            $coords = $this->getLocationCoordinates($job->location);
            
            return (object) [
                'location' => $job->location,
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'job_count' => $job->job_count,
                'avg_salary' => $job->avg_salary ?? 0,
                'demand_score' => min(100, $job->job_count / 10),
                'competition_score' => min(100, $job->company_count * 5),
                'growth_rate' => 0,
            ];
        })->filter(fn($item) => $item->latitude && $item->longitude);
    }

    /**
     * Get approximate coordinates for a location.
     */
    protected function getLocationCoordinates(string $location): array
    {
        // Common US cities coordinates (simplified - in production use geocoding API)
        $cities = [
            'new york' => ['lat' => 40.7128, 'lng' => -74.0060],
            'los angeles' => ['lat' => 34.0522, 'lng' => -118.2437],
            'chicago' => ['lat' => 41.8781, 'lng' => -87.6298],
            'houston' => ['lat' => 29.7604, 'lng' => -95.3698],
            'phoenix' => ['lat' => 33.4484, 'lng' => -112.0740],
            'philadelphia' => ['lat' => 39.9526, 'lng' => -75.1652],
            'san antonio' => ['lat' => 29.4241, 'lng' => -98.4936],
            'san diego' => ['lat' => 32.7157, 'lng' => -117.1611],
            'dallas' => ['lat' => 32.7767, 'lng' => -96.7970],
            'san jose' => ['lat' => 37.3382, 'lng' => -121.8863],
            'austin' => ['lat' => 30.2672, 'lng' => -97.7431],
            'san francisco' => ['lat' => 37.7749, 'lng' => -122.4194],
            'seattle' => ['lat' => 47.6062, 'lng' => -122.3321],
            'denver' => ['lat' => 39.7392, 'lng' => -104.9903],
            'boston' => ['lat' => 42.3601, 'lng' => -71.0589],
            'atlanta' => ['lat' => 33.7490, 'lng' => -84.3880],
            'miami' => ['lat' => 25.7617, 'lng' => -80.1918],
            'remote' => ['lat' => 39.8283, 'lng' => -98.5795], // Center of US
        ];
        
        $locationLower = strtolower($location);
        
        foreach ($cities as $city => $coords) {
            if (str_contains($locationLower, $city)) {
                return $coords;
            }
        }
        
        // Default to center of US for unknown locations
        return ['lat' => 39.8283, 'lng' => -98.5795];
    }

    /**
     * Get heatmap summary statistics.
     */
    protected function getHeatmapSummary(Collection $data): array
    {
        return [
            'total_locations' => $data->count(),
            'total_jobs' => $data->sum('job_count'),
            'avg_salary' => round($data->avg('avg_salary') ?? 0, 2),
            'highest_demand_location' => $data->sortByDesc('demand_score')->first()?->location ?? 'N/A',
            'lowest_competition_location' => $data->sortBy('competition_score')->first()?->location ?? 'N/A',
        ];
    }

    /**
     * Get top locations by job count.
     */
    protected function getTopLocations(Collection $data, int $limit): array
    {
        return $data->sortByDesc('job_count')
            ->take($limit)
            ->map(fn($h) => [
                'location' => $h->location,
                'job_count' => $h->job_count,
                'avg_salary' => $h->avg_salary,
                'demand_score' => $h->demand_score,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get real-time salary benchmark.
     */
    public function getSalaryBenchmark(string $jobTitle, ?string $location = null, ?string $experienceLevel = null): array
    {
        $cacheKey = "salary_bench_{$jobTitle}_{$location}_{$experienceLevel}";
        
        return Cache::remember($cacheKey, 3600, function () use ($jobTitle, $location, $experienceLevel) {
            // Try precomputed benchmarks first
            $benchmark = SalaryBenchmark::forTitle($jobTitle)
                ->when($location, fn($q) => $q->forLocation($location))
                ->when($experienceLevel, fn($q) => $q->forExperience($experienceLevel))
                ->orderByDesc('period_date')
                ->first();
            
            if ($benchmark) {
                return [
                    'found' => true,
                    'job_title' => $benchmark->job_title,
                    'location' => $benchmark->location,
                    'experience_level' => $benchmark->experience_level,
                    'min_salary' => $benchmark->min_salary,
                    'max_salary' => $benchmark->max_salary,
                    'median_salary' => $benchmark->median_salary,
                    'percentile_25' => $benchmark->percentile_25,
                    'percentile_75' => $benchmark->percentile_75,
                    'percentile_90' => $benchmark->percentile_90,
                    'yoy_change' => $benchmark->yoy_change,
                    'sample_size' => $benchmark->sample_size,
                    'benefits' => $benchmark->benefits_data,
                    'bonus' => $benchmark->bonus_data,
                ];
            }
            
            // Calculate from live job data
            return $this->calculateSalaryBenchmark($jobTitle, $location, $experienceLevel);
        });
    }

    /**
     * Calculate salary benchmark from live job data.
     */
    protected function calculateSalaryBenchmark(string $jobTitle, ?string $location, ?string $experienceLevel): array
    {
        $query = Job::query()
            ->where('status', 'published')
            ->where('title', 'like', "%{$jobTitle}%")
            ->whereNotNull('min_salary');
        
        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }
        
        if ($experienceLevel) {
            $query->where('experience_level', $experienceLevel);
        }
        
        $salaries = $query->pluck('min_salary')->sort()->values();
        
        if ($salaries->isEmpty()) {
            return [
                'found' => false,
                'message' => 'No salary data available for this criteria',
                'job_title' => $jobTitle,
                'location' => $location,
                'experience_level' => $experienceLevel,
            ];
        }
        
        return [
            'found' => true,
            'job_title' => $jobTitle,
            'location' => $location,
            'experience_level' => $experienceLevel,
            'min_salary' => $salaries->min(),
            'max_salary' => $salaries->max(),
            'median_salary' => $this->calculateMedian($salaries->toArray()),
            'percentile_25' => $this->calculatePercentile($salaries->toArray(), 25),
            'percentile_75' => $this->calculatePercentile($salaries->toArray(), 75),
            'percentile_90' => $this->calculatePercentile($salaries->toArray(), 90),
            'sample_size' => $salaries->count(),
            'yoy_change' => null,
            'calculated_from' => 'live_data',
        ];
    }

    /**
     * Get skills demand forecast.
     */
    public function getSkillsDemandForecast(array $filters = []): array
    {
        $cacheKey = 'skills_forecast_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = SkillDemandForecast::query()
                ->orderByDesc('current_demand')
                ->orderByDesc('forecast_date');
            
            if (!empty($filters['industry'])) {
                $query->forIndustry($filters['industry']);
            }
            
            if (!empty($filters['category'])) {
                $query->ofCategory($filters['category']);
            }
            
            if (!empty($filters['trend'])) {
                $query->where('trend_direction', $filters['trend']);
            }
            
            $forecasts = $query->limit(50)->get();
            
            // If no precomputed data, generate from jobs
            if ($forecasts->isEmpty()) {
                $forecasts = $this->generateSkillsForecast($filters);
            }
            
            return [
                'skills' => $forecasts->map(fn($f) => $f->getForecastSummary())->toArray(),
                'rising_skills' => $forecasts->where('trend_direction', 'rising')->take(10)->values()->toArray(),
                'declining_skills' => $forecasts->where('trend_direction', 'falling')->take(10)->values()->toArray(),
                'stable_skills' => $forecasts->where('trend_direction', 'stable')->take(10)->values()->toArray(),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Generate skills forecast from job data.
     */
    protected function generateSkillsForecast(array $filters): Collection
    {
        // Get recent jobs
        $recentJobs = Job::where('status', 'published')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('extracted_skills')
            ->limit(500)
            ->get();
        
        // Get historical jobs
        $historicalJobs = Job::where('status', 'published')
            ->whereBetween('created_at', [now()->subDays(90), now()->subDays(30)])
            ->whereNotNull('extracted_skills')
            ->limit(500)
            ->get();
        
        // Extract skills
        $currentSkills = [];
        $historicalSkills = [];
        
        foreach ($recentJobs as $job) {
            $skills = $job->extracted_skills['required_skills'] ?? [];
            foreach ($skills as $skill) {
                $currentSkills[$skill] = ($currentSkills[$skill] ?? 0) + 1;
            }
        }
        
        foreach ($historicalJobs as $job) {
            $skills = $job->extracted_skills['required_skills'] ?? [];
            foreach ($skills as $skill) {
                $historicalSkills[$skill] = ($historicalSkills[$skill] ?? 0) + 1;
            }
        }
        
        // Calculate trends
        $forecasts = collect();
        foreach ($currentSkills as $skill => $count) {
            $historicalCount = $historicalSkills[$skill] ?? 0;
            $growth = $historicalCount > 0 
                ? (($count - $historicalCount) / $historicalCount) * 100 
                : 100;
            
            $trend = match (true) {
                $growth >= 20 => 'rising',
                $growth <= -20 => 'falling',
                default => 'stable',
            };
            
            $forecasts->push((object) [
                'skill_name' => $skill,
                'current_demand' => $count,
                'trend_direction' => $trend,
                'growth_rate_30d' => round($growth, 2),
                'growth_rate_90d' => round($growth * 3, 2),
                'predicted_demand_30d' => round($count * (1 + $growth / 100), 0),
                'predicted_demand_90d' => round($count * (1 + $growth * 3 / 100), 0),
                'confidence_score' => min(100, max(20, 50 + $count)),
                'avg_salary_premium' => null,
            ]);
        }
        
        return $forecasts->sortByDesc('current_demand');
    }

    /**
     * Get career path visualization data.
     */
    public function getCareerPathVisualization(?string $startRole = null, ?string $industry = null): array
    {
        $cacheKey = "career_path_{$startRole}_{$industry}";
        
        return Cache::remember($cacheKey, 7200, function () use ($startRole, $industry) {
            $query = CareerPathNode::query()
                ->with(['outgoingEdges.toNode', 'incomingEdges.fromNode']);
            
            if ($industry) {
                $query->forIndustry($industry);
            }
            
            if ($startRole) {
                $query->where('job_title', 'like', "%{$startRole}%");
            }
            
            $nodes = $query->orderBy('level_rank')->limit(50)->get();
            
            if ($nodes->isEmpty()) {
                // Generate sample career paths
                return $this->generateSampleCareerPaths($startRole, $industry);
            }
            
            $edges = CareerPathEdge::whereIn('from_node_id', $nodes->pluck('id'))
                ->orWhereIn('to_node_id', $nodes->pluck('id'))
                ->get();
            
            return [
                'nodes' => $nodes->map(fn($n) => $n->toGraphNode())->toArray(),
                'edges' => $edges->map(fn($e) => $e->toGraphEdge())->toArray(),
                'levels' => [
                    1 => 'Entry Level',
                    2 => 'Junior',
                    3 => 'Mid-Level',
                    4 => 'Senior',
                    5 => 'Lead/Manager',
                    6 => 'Director/Executive',
                ],
            ];
        });
    }

    /**
     * Generate sample career paths for demonstration.
     */
    protected function generateSampleCareerPaths(?string $role, ?string $industry): array
    {
        // Tech career path example
        $techPath = [
            ['id' => 1, 'label' => 'Junior Developer', 'level' => 1, 'salary' => 60000],
            ['id' => 2, 'label' => 'Mid Developer', 'level' => 2, 'salary' => 80000],
            ['id' => 3, 'label' => 'Senior Developer', 'level' => 3, 'salary' => 120000],
            ['id' => 4, 'label' => 'Tech Lead', 'level' => 4, 'salary' => 150000],
            ['id' => 5, 'label' => 'Engineering Manager', 'level' => 5, 'salary' => 180000],
            ['id' => 6, 'label' => 'VP Engineering', 'level' => 6, 'salary' => 250000],
            ['id' => 7, 'label' => 'Principal Engineer', 'level' => 4, 'salary' => 200000],
            ['id' => 8, 'label' => 'Staff Engineer', 'level' => 5, 'salary' => 220000],
            ['id' => 9, 'label' => 'CTO', 'level' => 6, 'salary' => 350000],
        ];
        
        $edges = [
            ['from' => 1, 'to' => 2, 'value' => 85, 'label' => '2-3 years'],
            ['from' => 2, 'to' => 3, 'value' => 75, 'label' => '3-4 years'],
            ['from' => 3, 'to' => 4, 'value' => 50, 'label' => '2-3 years'],
            ['from' => 3, 'to' => 7, 'value' => 30, 'label' => '3-4 years'],
            ['from' => 4, 'to' => 5, 'value' => 40, 'label' => '2-4 years'],
            ['from' => 4, 'to' => 8, 'value' => 20, 'label' => '3-5 years'],
            ['from' => 5, 'to' => 6, 'value' => 25, 'label' => '4-6 years'],
            ['from' => 7, 'to' => 8, 'value' => 35, 'label' => '2-3 years'],
            ['from' => 8, 'to' => 9, 'value' => 10, 'label' => '5+ years'],
            ['from' => 6, 'to' => 9, 'value' => 15, 'label' => '3-5 years'],
        ];
        
        return [
            'nodes' => $techPath,
            'edges' => $edges,
            'levels' => [
                1 => 'Entry Level',
                2 => 'Mid-Level',
                3 => 'Senior',
                4 => 'Lead',
                5 => 'Manager',
                6 => 'Executive',
            ],
            'sample_data' => true,
        ];
    }

    /**
     * Get application funnel analytics.
     */
    public function getApplicationFunnelAnalytics(?int $employerId = null, ?int $jobId = null, array $filters = []): array
    {
        $cacheKey = "funnel_{$employerId}_{$jobId}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 1800, function () use ($employerId, $jobId, $filters) {
            // Try precomputed data first
            $query = ApplicationFunnel::query();
            
            if ($employerId) {
                $query->forEmployer($employerId);
            }
            
            if ($jobId) {
                $query->forJob($jobId);
            }
            
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query->inPeriod($filters['start_date'], $filters['end_date']);
            }
            
            $funnelData = $query->orderByDesc('period_date')->first();
            
            if ($funnelData) {
                return [
                    'funnel' => $funnelData->getFunnelData(),
                    'metrics' => [
                        'overall_conversion' => $funnelData->overall_conversion_rate,
                        'views' => $funnelData->views_count,
                        'applications' => $funnelData->applications_count,
                        'interviews' => $funnelData->interview_count,
                        'offers' => $funnelData->offer_count,
                        'hires' => $funnelData->hired_count,
                    ],
                    'dropoff_analysis' => $this->analyzeFunnelDropoffs($funnelData),
                ];
            }
            
            // Calculate from live data
            return $this->calculateFunnelFromApplications($employerId, $jobId, $filters);
        });
    }

    /**
     * Calculate funnel from live application data.
     */
    protected function calculateFunnelFromApplications(?int $employerId, ?int $jobId, array $filters): array
    {
        $query = Application::query();
        
        if ($jobId) {
            $query->where('job_id', $jobId);
        } elseif ($employerId) {
            $query->whereHas('job', fn($q) => $q->where('employer_id', $employerId));
        }
        
        $applications = $query->get();
        
        $stages = [
            'submitted' => $applications->where('status', 'submitted')->count(),
            'viewed' => $applications->where('status', 'viewed')->count(),
            'screening' => $applications->whereIn('status', ['shortlisted', 'screening'])->count(),
            'interview' => $applications->whereIn('status', ['interview', 'interviewed'])->count(),
            'offer' => $applications->where('status', 'offered')->count(),
            'hired' => $applications->where('status', 'accepted')->count(),
        ];
        
        $total = $applications->count();
        
        return [
            'funnel' => [
                ['stage' => 'Applications', 'count' => $stages['submitted'] + $stages['viewed'], 'rate' => 100],
                ['stage' => 'Screening', 'count' => $stages['screening'], 'rate' => $total > 0 ? round($stages['screening'] / $total * 100, 1) : 0],
                ['stage' => 'Interview', 'count' => $stages['interview'], 'rate' => $total > 0 ? round($stages['interview'] / $total * 100, 1) : 0],
                ['stage' => 'Offer', 'count' => $stages['offer'], 'rate' => $total > 0 ? round($stages['offer'] / $total * 100, 1) : 0],
                ['stage' => 'Hired', 'count' => $stages['hired'], 'rate' => $total > 0 ? round($stages['hired'] / $total * 100, 1) : 0],
            ],
            'metrics' => [
                'total_applications' => $total,
                'overall_conversion' => $total > 0 ? round($stages['hired'] / $total * 100, 2) : 0,
            ],
            'calculated_from' => 'live_data',
        ];
    }

    /**
     * Analyze funnel dropoffs.
     */
    protected function analyzeFunnelDropoffs(ApplicationFunnel $funnel): array
    {
        return [
            'biggest_dropoff' => $this->findBiggestDropoff($funnel),
            'recommendations' => $this->getFunnelRecommendations($funnel),
        ];
    }

    /**
     * Find the stage with biggest dropoff.
     */
    protected function findBiggestDropoff(ApplicationFunnel $funnel): array
    {
        $rates = [
            'view_to_apply' => 100 - $funnel->view_to_apply_rate,
            'apply_to_screen' => 100 - $funnel->apply_to_screen_rate,
            'screen_to_interview' => 100 - $funnel->screen_to_interview_rate,
            'interview_to_offer' => 100 - $funnel->interview_to_offer_rate,
            'offer_to_hire' => 100 - $funnel->offer_to_hire_rate,
        ];
        
        $maxDropoff = array_keys($rates, max($rates))[0];
        
        return [
            'stage' => str_replace('_', ' ', $maxDropoff),
            'dropoff_rate' => $rates[$maxDropoff],
        ];
    }

    /**
     * Get recommendations for funnel improvement.
     */
    protected function getFunnelRecommendations(ApplicationFunnel $funnel): array
    {
        $recommendations = [];
        
        if ($funnel->view_to_apply_rate < 5) {
            $recommendations[] = 'Low application rate - consider improving job description or simplifying application process';
        }
        
        if ($funnel->apply_to_screen_rate < 30) {
            $recommendations[] = 'Many applicants not passing screening - refine job requirements or use AI-powered matching';
        }
        
        if ($funnel->interview_to_offer_rate < 20) {
            $recommendations[] = 'Low interview-to-offer conversion - review interview process or candidate evaluation criteria';
        }
        
        if ($funnel->offer_to_hire_rate < 70) {
            $recommendations[] = 'Offers being declined - consider competitive salary analysis and benefits review';
        }
        
        return $recommendations;
    }

    /**
     * Get time-to-hire metrics.
     */
    public function getTimeToHireMetrics(?int $employerId = null, ?string $industry = null, array $filters = []): array
    {
        $cacheKey = "tth_{$employerId}_{$industry}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 3600, function () use ($employerId, $industry, $filters) {
            $query = TimeToHireMetric::query();
            
            if ($employerId) {
                $query->forEmployer($employerId);
            }
            
            if ($industry) {
                $query->forIndustry($industry);
            }
            
            $metrics = $query->orderByDesc('period_date')->first();
            
            if ($metrics) {
                return [
                    'avg_days_to_hire' => $metrics->avg_days_to_hire,
                    'median_days_to_hire' => $metrics->median_days_to_hire,
                    'min_days' => $metrics->min_days_to_hire,
                    'max_days' => $metrics->max_days_to_hire,
                    'sample_size' => $metrics->sample_size,
                    'stage_breakdown' => $metrics->stage_breakdown,
                    'benchmarks' => $this->getTimeToHireBenchmarks($industry),
                ];
            }
            
            // Calculate from live data
            return $this->calculateTimeToHire($employerId, $industry);
        });
    }

    /**
     * Calculate time-to-hire from applications.
     */
    protected function calculateTimeToHire(?int $employerId, ?string $industry): array
    {
        $query = Application::query()
            ->where('status', 'accepted')
            ->whereNotNull('created_at');
        
        if ($employerId) {
            $query->whereHas('job', fn($q) => $q->where('employer_id', $employerId));
        }
        
        $hiredApps = $query->limit(100)->get();
        
        if ($hiredApps->isEmpty()) {
            return [
                'avg_days_to_hire' => null,
                'message' => 'No hire data available',
            ];
        }
        
        $daysToHire = $hiredApps->map(function ($app) {
            return $app->updated_at->diffInDays($app->created_at);
        });
        
        return [
            'avg_days_to_hire' => round($daysToHire->avg(), 1),
            'median_days_to_hire' => $this->calculateMedian($daysToHire->toArray()),
            'min_days' => $daysToHire->min(),
            'max_days' => $daysToHire->max(),
            'sample_size' => $daysToHire->count(),
            'calculated_from' => 'live_data',
        ];
    }

    /**
     * Get time-to-hire benchmarks by industry.
     */
    protected function getTimeToHireBenchmarks(?string $industry): array
    {
        // Industry benchmarks (approximate)
        $benchmarks = [
            'technology' => ['avg' => 42, 'median' => 38],
            'healthcare' => ['avg' => 49, 'median' => 45],
            'finance' => ['avg' => 45, 'median' => 40],
            'retail' => ['avg' => 25, 'median' => 21],
            'manufacturing' => ['avg' => 35, 'median' => 32],
            'default' => ['avg' => 40, 'median' => 36],
        ];
        
        return $benchmarks[strtolower($industry ?? 'default')] ?? $benchmarks['default'];
    }

    /**
     * Get source attribution data.
     */
    public function getSourceAttribution(?int $employerId = null, array $filters = []): array
    {
        $cacheKey = "source_attr_{$employerId}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 1800, function () use ($employerId, $filters) {
            $query = SourceAttribution::query();
            
            if ($employerId) {
                $query->forEmployer($employerId);
            }
            
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query->inPeriod($filters['start_date'], $filters['end_date']);
            }
            
            $sources = $query->orderByDesc('applications_count')->get();
            
            if ($sources->isEmpty()) {
                return $this->generateSampleSourceData();
            }
            
            return [
                'sources' => $sources->map(fn($s) => $s->getRoiMetrics())->toArray(),
                'by_category' => $this->groupSourcesByCategory($sources),
                'top_performer' => $sources->sortByDesc('quality_score')->first()?->getRoiMetrics(),
                'recommendations' => $this->getSourceRecommendations($sources),
            ];
        });
    }

    /**
     * Generate sample source attribution data.
     */
    protected function generateSampleSourceData(): array
    {
        return [
            'sources' => [
                ['source' => 'LinkedIn', 'category' => 'job_board', 'applications' => 150, 'hires' => 12, 'cost_per_hire' => 1500, 'quality_score' => 85, 'roi' => 120],
                ['source' => 'Indeed', 'category' => 'job_board', 'applications' => 300, 'hires' => 15, 'cost_per_hire' => 800, 'quality_score' => 72, 'roi' => 180],
                ['source' => 'Referral', 'category' => 'referral', 'applications' => 50, 'hires' => 10, 'cost_per_hire' => 500, 'quality_score' => 92, 'roi' => 250],
                ['source' => 'Direct', 'category' => 'direct', 'applications' => 80, 'hires' => 8, 'cost_per_hire' => 0, 'quality_score' => 88, 'roi' => 999],
                ['source' => 'Career Fair', 'category' => 'event', 'applications' => 40, 'hires' => 3, 'cost_per_hire' => 2000, 'quality_score' => 65, 'roi' => 45],
            ],
            'sample_data' => true,
        ];
    }

    /**
     * Group sources by category.
     */
    protected function groupSourcesByCategory(Collection $sources): array
    {
        return $sources->groupBy('source_category')
            ->map(fn($group) => [
                'total_applications' => $group->sum('applications_count'),
                'total_hires' => $group->sum('hires_count'),
                'avg_quality' => round($group->avg('quality_score'), 1),
            ])
            ->toArray();
    }

    /**
     * Get source optimization recommendations.
     */
    protected function getSourceRecommendations(Collection $sources): array
    {
        $recommendations = [];
        
        $topQuality = $sources->sortByDesc('quality_score')->first();
        if ($topQuality && $topQuality->quality_score > 80) {
            $recommendations[] = "Increase investment in {$topQuality->source_name} - highest quality candidates";
        }
        
        $lowRoi = $sources->filter(fn($s) => $s->cost_per_hire > 2000 && $s->hires_count < 5);
        foreach ($lowRoi as $source) {
            $recommendations[] = "Consider reducing spend on {$source->source_name} - high cost per hire";
        }
        
        return $recommendations;
    }

    /**
     * Get competitor salary comparison.
     */
    public function getCompetitorSalaryComparison(string $jobTitle, ?string $industry = null, ?string $location = null): array
    {
        $cacheKey = "competitor_salary_{$jobTitle}_{$industry}_{$location}";
        
        return Cache::remember($cacheKey, 7200, function () use ($jobTitle, $industry, $location) {
            $query = CompetitorSalaryData::forTitle($jobTitle);
            
            if ($industry) {
                $query->forIndustry($industry);
            }
            
            if ($location) {
                $query->forLocation($location);
            }
            
            $competitors = $query->orderByDesc('data_date')->limit(20)->get();
            
            if ($competitors->isEmpty()) {
                return $this->generateCompetitorComparison($jobTitle, $industry, $location);
            }
            
            return [
                'job_title' => $jobTitle,
                'market_median' => round($competitors->avg('median_salary'), 0),
                'market_range' => [
                    'min' => $competitors->min('percentile_25'),
                    'max' => $competitors->max('percentile_75'),
                ],
                'by_company_size' => $competitors->groupBy('company_size')
                    ->map(fn($g) => round($g->avg('median_salary'), 0))
                    ->toArray(),
                'competitors' => $competitors->map(fn($c) => $c->getComparisonData())->toArray(),
            ];
        });
    }

    /**
     * Generate competitor salary comparison from available data.
     */
    protected function generateCompetitorComparison(string $jobTitle, ?string $industry, ?string $location): array
    {
        $benchmark = $this->getSalaryBenchmark($jobTitle, $location, null);
        
        if (!$benchmark['found']) {
            return [
                'job_title' => $jobTitle,
                'message' => 'Insufficient data for competitor comparison',
            ];
        }
        
        return [
            'job_title' => $jobTitle,
            'market_median' => $benchmark['median_salary'],
            'market_range' => [
                'min' => $benchmark['percentile_25'] ?? $benchmark['min_salary'],
                'max' => $benchmark['percentile_75'] ?? $benchmark['max_salary'],
            ],
            'by_company_size' => [
                'startup' => round($benchmark['median_salary'] * 0.85, 0),
                'small' => round($benchmark['median_salary'] * 0.92, 0),
                'medium' => round($benchmark['median_salary'], 0),
                'large' => round($benchmark['median_salary'] * 1.1, 0),
                'enterprise' => round($benchmark['median_salary'] * 1.2, 0),
            ],
            'generated_from' => 'benchmark_data',
        ];
    }

    /**
     * Calculate median of array.
     */
    protected function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2) {
            return $values[$middle];
        }
        
        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Calculate percentile of array.
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower == $upper) {
            return $values[$lower];
        }
        
        return $values[$lower] + ($values[$upper] - $values[$lower]) * ($index - $lower);
    }

    /**
     * Get comprehensive analytics dashboard data.
     */
    public function getDashboardData(?int $userId = null, string $dashboardType = 'default'): array
    {
        return [
            'heatmap' => $this->getJobMarketHeatmap(),
            'skills_forecast' => $this->getSkillsDemandForecast(['limit' => 15]),
            'salary_trends' => $this->getSalaryTrends(),
            'career_paths' => $this->getCareerPathVisualization(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get salary trends over time.
     */
    public function getSalaryTrends(?string $industry = null): array
    {
        $cacheKey = "salary_trends_{$industry}";
        
        return Cache::remember($cacheKey, 3600, function () use ($industry) {
            $months = [];
            $salaries = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $avgSalary = Job::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->when($industry, fn($q) => $q->whereHas('company', fn($c) => $c->where('industry', $industry)))
                    ->whereNotNull('min_salary')
                    ->avg('min_salary');
                
                $salaries[] = round($avgSalary ?? 0, 0);
            }
            
            return [
                'labels' => $months,
                'data' => $salaries,
                'trend' => $this->calculateTrendDirection($salaries),
            ];
        });
    }

    /**
     * Calculate trend direction from array of values.
     */
    protected function calculateTrendDirection(array $values): string
    {
        if (count($values) < 2) {
            return 'stable';
        }
        
        $recent = array_slice($values, -3);
        $earlier = array_slice($values, 0, 3);
        
        $recentAvg = array_sum($recent) / count($recent);
        $earlierAvg = array_sum($earlier) / count($earlier);
        
        if ($earlierAvg == 0) {
            return 'stable';
        }
        
        $change = (($recentAvg - $earlierAvg) / $earlierAvg) * 100;
        
        return match (true) {
            $change >= 5 => 'rising',
            $change <= -5 => 'falling',
            default => 'stable',
        };
    }
}
