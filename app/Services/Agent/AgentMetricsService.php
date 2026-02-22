<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\AgentAuditLog;
use App\Models\AgentConfiguration;
use App\Models\ApplicationActivityLog;
use App\Models\AutoApplication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates and surfaces agent performance metrics.
 *
 * Provides data for the agent dashboard including success rates,
 * response times, application counts, and trend analysis.
 */
class AgentMetricsService
{
    /**
     * Get a comprehensive metrics snapshot for a user's agent.
     *
     * @return array{
     *   today: array,
     *   this_week: array,
     *   this_month: array,
     *   success_rate: float,
     *   avg_match_score: float,
     *   top_sources: array,
     *   activity_trend: array
     * }
     */
    public function getMetrics(int $userId): array
    {
        return Cache::remember("agent_metrics:{$userId}", now()->addMinutes(5), function () use ($userId) {
            return [
                'today'         => $this->periodMetrics($userId, now()->startOfDay()),
                'this_week'     => $this->periodMetrics($userId, now()->startOfWeek()),
                'this_month'    => $this->periodMetrics($userId, now()->startOfMonth()),
                'success_rate'  => $this->successRate($userId),
                'avg_match_score' => $this->averageMatchScore($userId),
                'top_sources'   => $this->topSources($userId),
                'activity_trend' => $this->activityTrend($userId, 30),
                'configuration' => $this->configurationSummary($userId),
            ];
        });
    }

    /**
     * Metrics for a specific time period.
     */
    public function periodMetrics(int $userId, Carbon $since): array
    {
        $applications = AutoApplication::where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = "pending_review" THEN 1 ELSE 0 END) as pending,
                AVG(match_score) as avg_score
            ')
            ->first();

        return [
            'total_applications'  => (int) ($applications->total ?? 0),
            'submitted'           => (int) ($applications->submitted ?? 0),
            'approved'            => (int) ($applications->approved ?? 0),
            'rejected'            => (int) ($applications->rejected ?? 0),
            'pending_review'      => (int) ($applications->pending ?? 0),
            'average_match_score' => round((float) ($applications->avg_score ?? 0), 1),
        ];
    }

    /**
     * Overall success rate (submitted / total).
     */
    public function successRate(int $userId): float
    {
        $total = AutoApplication::where('user_id', $userId)->count();

        if ($total === 0) {
            return 0.0;
        }

        $submitted = AutoApplication::where('user_id', $userId)
            ->where('status', 'submitted')
            ->count();

        return round(($submitted / $total) * 100, 1);
    }

    /**
     * Average match score of submitted applications.
     */
    public function averageMatchScore(int $userId): float
    {
        return round(
            (float) AutoApplication::where('user_id', $userId)
                ->whereNotNull('match_score')
                ->avg('match_score') ?? 0,
            1
        );
    }

    /**
     * Top job sources by application count.
     */
    public function topSources(int $userId, int $limit = 5): array
    {
        return AutoApplication::where('user_id', $userId)
            ->join('discovered_jobs', 'auto_applications.discovered_job_id', '=', 'discovered_jobs.id')
            ->select('discovered_jobs.source', DB::raw('COUNT(*) as count'))
            ->groupBy('discovered_jobs.source')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source,
                'count'  => $row->count,
            ])
            ->toArray();
    }

    /**
     * Daily activity trend for N days.
     */
    public function activityTrend(int $userId, int $days = 30): array
    {
        return AutoApplication::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date'  => $row->date,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    /**
     * Configuration health summary.
     */
    public function configurationSummary(int $userId): array
    {
        $config = AgentConfiguration::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return ['status' => 'inactive'];
        }

        return [
            'status'                  => 'active',
            'daily_limit'             => $config->daily_application_limit,
            'applications_today'      => $config->getApplicationsToday(),
            'remaining_today'         => $config->getRemainingApplicationsToday(),
            'match_threshold'         => $config->match_threshold_percentage,
            'aggressiveness'          => $config->application_aggressiveness,
            'approval_threshold'      => $config->approval_threshold ?? 80,
            'last_run_at'             => $config->last_run_at?->toIso8601String(),
            'next_scheduled_run_at'   => $config->next_run_at?->toIso8601String(),
        ];
    }

    /**
     * Invalidate cached metrics for a user.
     */
    public function invalidate(int $userId): void
    {
        Cache::forget("agent_metrics:{$userId}");
    }
}
