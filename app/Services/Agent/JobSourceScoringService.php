<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\DiscoveredJob;
use App\Models\JobMatch;
use App\Models\JobSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job Source Scoring Service
 *
 * Dynamically scores and prioritizes job sources based on:
 * - Success rate (scraping reliability)
 * - Job quality (match scores, freshness)
 * - User engagement (applications, saves)
 * - Data completeness (salary info, descriptions)
 *
 * Used by the autonomous agent to optimize job discovery.
 */
class JobSourceScoringService
{
    /**
     * Score weights for different quality factors.
     */
    protected const WEIGHTS = [
        'success_rate' => 0.20,        // Scraping reliability
        'job_quality' => 0.25,         // Average match score
        'freshness' => 0.15,           // How recent jobs are
        'data_completeness' => 0.15,   // Salary, description quality
        'user_engagement' => 0.15,     // Applications per job
        'volume' => 0.10,              // Jobs per day
    ];

    /**
     * Cache TTL for source scores (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Score all active job sources.
     *
     * @return Collection<int, array>
     */
    public function scoreAllSources(): Collection
    {
        $cacheKey = 'job_source_scores_all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $sources = JobSource::active()->get();

            return $sources->map(function (JobSource $source) {
                return [
                    'source' => $source,
                    'score' => $this->calculateSourceScore($source),
                    'metrics' => $this->getSourceMetrics($source),
                ];
            })->sortByDesc('score')->values();
        });
    }

    /**
     * Get ranked sources for job discovery.
     *
     * @param int $limit
     * @return Collection<int, JobSource>
     */
    public function getRankedSources(int $limit = 10): Collection
    {
        return $this->scoreAllSources()
            ->take($limit)
            ->pluck('source');
    }

    /**
     * Calculate overall score for a job source.
     *
     * @param JobSource $source
     * @return float Score between 0 and 100
     */
    public function calculateSourceScore(JobSource $source): float
    {
        $metrics = $this->getSourceMetrics($source);

        $score = 0;
        $score += $metrics['success_rate_score'] * self::WEIGHTS['success_rate'];
        $score += $metrics['job_quality_score'] * self::WEIGHTS['job_quality'];
        $score += $metrics['freshness_score'] * self::WEIGHTS['freshness'];
        $score += $metrics['data_completeness_score'] * self::WEIGHTS['data_completeness'];
        $score += $metrics['user_engagement_score'] * self::WEIGHTS['user_engagement'];
        $score += $metrics['volume_score'] * self::WEIGHTS['volume'];

        return round($score, 2);
    }

    /**
     * Get detailed metrics for a job source.
     *
     * @param JobSource $source
     * @return array
     */
    public function getSourceMetrics(JobSource $source): array
    {
        $cacheKey = "job_source_metrics_{$source->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source) {
            $recentJobs = DiscoveredJob::where('job_source_id', $source->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            return [
                'success_rate' => $source->success_rate ?? 0,
                'success_rate_score' => $this->normalizeScore($source->success_rate ?? 0, 0, 100),

                'job_quality_score' => $this->calculateJobQualityScore($source, $recentJobs),
                'average_match_score' => $this->getAverageMatchScore($source),

                'freshness_score' => $this->calculateFreshnessScore($source, $recentJobs),
                'average_age_days' => $this->getAverageJobAgeDays($recentJobs),

                'data_completeness_score' => $this->calculateDataCompletenessScore($recentJobs),
                'salary_info_percentage' => $this->getSalaryInfoPercentage($recentJobs),

                'user_engagement_score' => $this->calculateEngagementScore($source),
                'applications_per_job' => $this->getApplicationsPerJob($source),

                'volume_score' => $this->calculateVolumeScore($source),
                'jobs_per_day' => $source->jobs_found_today ?? 0,
                'total_jobs' => $recentJobs->count(),
            ];
        });
    }

    /**
     * Calculate job quality score based on match scores.
     */
    protected function calculateJobQualityScore(JobSource $source, Collection $recentJobs): float
    {
        if ($recentJobs->isEmpty()) {
            return 50.0; // Neutral score for new sources
        }

        $avgMatchScore = $this->getAverageMatchScore($source);

        // Normalize: 50 = average, 70+ = good, 80+ = excellent
        return $this->normalizeScore($avgMatchScore, 0, 100);
    }

    /**
     * Get average match score for jobs from this source.
     */
    protected function getAverageMatchScore(JobSource $source): float
    {
        $avgScore = JobMatch::whereHas('discoveredJob', function ($query) use ($source) {
            $query->where('job_source_id', $source->id);
        })
        ->where('created_at', '>=', now()->subDays(30))
        ->avg('match_score');

        return $avgScore ?? 50.0;
    }

    /**
     * Calculate freshness score based on job posting dates.
     */
    protected function calculateFreshnessScore(JobSource $source, Collection $recentJobs): float
    {
        if ($recentJobs->isEmpty()) {
            return 50.0;
        }

        $avgAgeDays = $this->getAverageJobAgeDays($recentJobs);

        // Fresher is better: 0-3 days = 100, 7 days = 70, 14+ days = 40
        if ($avgAgeDays <= 3) {
            return 100.0;
        } elseif ($avgAgeDays <= 7) {
            return 100.0 - (($avgAgeDays - 3) * 7.5);
        } elseif ($avgAgeDays <= 14) {
            return 70.0 - (($avgAgeDays - 7) * 4.3);
        } else {
            return max(20.0, 40.0 - (($avgAgeDays - 14) * 1.0));
        }
    }

    /**
     * Get average age of jobs in days.
     */
    protected function getAverageJobAgeDays(Collection $jobs): float
    {
        if ($jobs->isEmpty()) {
            return 30.0;
        }

        $totalAgeDays = $jobs->sum(function ($job) {
            return $job->posted_at
                ? now()->diffInDays($job->posted_at)
                : now()->diffInDays($job->created_at);
        });

        return $totalAgeDays / $jobs->count();
    }

    /**
     * Calculate data completeness score.
     */
    protected function calculateDataCompletenessScore(Collection $jobs): float
    {
        if ($jobs->isEmpty()) {
            return 50.0;
        }

        $completenessTotal = $jobs->sum(function ($job) {
            $score = 0;

            // Salary information (30 points)
            if ($job->salary_min && $job->salary_max) {
                $score += 30;
            } elseif ($job->salary_min || $job->salary_max) {
                $score += 15;
            }

            // Description quality (30 points)
            $descLength = strlen($job->description ?? '');
            if ($descLength >= 500) {
                $score += 30;
            } elseif ($descLength >= 200) {
                $score += 20;
            } elseif ($descLength >= 50) {
                $score += 10;
            }

            // Skills extracted (20 points)
            $skillsCount = count($job->extracted_skills ?? []);
            if ($skillsCount >= 5) {
                $score += 20;
            } elseif ($skillsCount >= 3) {
                $score += 15;
            } elseif ($skillsCount >= 1) {
                $score += 10;
            }

            // Location information (10 points)
            if (!empty($job->location)) {
                $score += 10;
            }

            // Company name (10 points)
            if (!empty($job->company_name)) {
                $score += 10;
            }

            return $score;
        });

        return $completenessTotal / $jobs->count();
    }

    /**
     * Get percentage of jobs with salary information.
     */
    protected function getSalaryInfoPercentage(Collection $jobs): float
    {
        if ($jobs->isEmpty()) {
            return 0.0;
        }

        $withSalary = $jobs->filter(function ($job) {
            return $job->salary_min || $job->salary_max;
        })->count();

        return round(($withSalary / $jobs->count()) * 100, 1);
    }

    /**
     * Calculate user engagement score.
     */
    protected function calculateEngagementScore(JobSource $source): float
    {
        $appsPerJob = $this->getApplicationsPerJob($source);

        // Higher engagement = better source
        // 0 apps = 20, 1 app = 50, 3+ apps = 100
        if ($appsPerJob >= 3) {
            return 100.0;
        } elseif ($appsPerJob >= 1) {
            return 50.0 + ($appsPerJob * 16.67);
        } else {
            return 20.0 + ($appsPerJob * 30);
        }
    }

    /**
     * Get average applications per job from this source.
     */
    protected function getApplicationsPerJob(JobSource $source): float
    {
        $result = DB::table('discovered_jobs as dj')
            ->leftJoin('auto_applications as aa', 'dj.id', '=', 'aa.discovered_job_id')
            ->where('dj.job_source_id', $source->id)
            ->where('dj.created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('COUNT(DISTINCT dj.id) as total_jobs'),
                DB::raw('COUNT(aa.id) as total_applications')
            )
            ->first();

        if (!$result || $result->total_jobs == 0) {
            return 0.0;
        }

        return round($result->total_applications / $result->total_jobs, 2);
    }

    /**
     * Calculate volume score based on jobs found.
     */
    protected function calculateVolumeScore(JobSource $source): float
    {
        $jobsPerDay = $source->jobs_found_today ?? 0;

        // More volume = better (up to a point)
        // 0 jobs = 0, 5 jobs = 50, 20+ jobs = 100
        if ($jobsPerDay >= 20) {
            return 100.0;
        } elseif ($jobsPerDay >= 5) {
            return 50.0 + (($jobsPerDay - 5) * 3.33);
        } else {
            return $jobsPerDay * 10;
        }
    }

    /**
     * Normalize a score to 0-100 range.
     */
    protected function normalizeScore(float $value, float $min, float $max): float
    {
        if ($max == $min) {
            return 50.0;
        }

        $normalized = (($value - $min) / ($max - $min)) * 100;

        return max(0, min(100, $normalized));
    }

    /**
     * Update source priority based on score.
     */
    public function updateSourcePriorities(): void
    {
        Log::info('Updating job source priorities');

        $scoredSources = $this->scoreAllSources();

        $rank = 1;
        foreach ($scoredSources as $item) {
            $source = $item['source'];
            $score = $item['score'];

            // Map score to priority (1-10)
            $priority = max(1, min(10, (int) round($score / 10)));

            $source->update(['priority' => $priority]);

            Log::debug("Updated source priority", [
                'source_id' => $source->id,
                'source_name' => $source->name,
                'score' => $score,
                'priority' => $priority,
                'rank' => $rank++,
            ]);
        }

        // Clear cache
        Cache::forget('job_source_scores_all');
    }

    /**
     * Get recommendations for improving source quality.
     */
    public function getSourceRecommendations(JobSource $source): array
    {
        $metrics = $this->getSourceMetrics($source);
        $recommendations = [];

        if ($metrics['success_rate'] < 70) {
            $recommendations[] = [
                'type' => 'reliability',
                'issue' => 'Low scraping success rate',
                'suggestion' => 'Review scraping configuration and selectors',
                'priority' => 'high',
            ];
        }

        if ($metrics['salary_info_percentage'] < 30) {
            $recommendations[] = [
                'type' => 'completeness',
                'issue' => 'Few jobs have salary information',
                'suggestion' => 'Check if salary selectors need updating',
                'priority' => 'medium',
            ];
        }

        if ($metrics['data_completeness_score'] < 50) {
            $recommendations[] = [
                'type' => 'completeness',
                'issue' => 'Job data quality is low',
                'suggestion' => 'Review data extraction patterns',
                'priority' => 'high',
            ];
        }

        if ($metrics['user_engagement_score'] < 40) {
            $recommendations[] = [
                'type' => 'quality',
                'issue' => 'Low user engagement with jobs from this source',
                'suggestion' => 'Verify job relevance and posting freshness',
                'priority' => 'medium',
            ];
        }

        if ($metrics['freshness_score'] < 50) {
            $recommendations[] = [
                'type' => 'freshness',
                'issue' => 'Jobs from this source tend to be older',
                'suggestion' => 'Increase scraping frequency or add date filtering',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }
}
