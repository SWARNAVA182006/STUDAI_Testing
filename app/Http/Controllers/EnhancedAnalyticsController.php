<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EnhancedAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class EnhancedAnalyticsController extends Controller
{
    public function __construct(
        protected EnhancedAnalyticsService $analyticsService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the enhanced analytics dashboard.
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        $dashboardType = $user->account_type === 'employer' ? 'employer' : 'job_seeker';
        
        $data = $this->analyticsService->getDashboardData($user->id, $dashboardType);
        
        return view('analytics.dashboard', [
            'dashboardData' => $data,
            'dashboardType' => $dashboardType,
        ]);
    }

    /**
     * Get job market heatmap data.
     */
    public function heatmap(Request $request): JsonResponse
    {
        $filters = $request->only(['industry', 'category', 'location']);
        
        $data = $this->analyticsService->getJobMarketHeatmap($filters);
        
        return response()->json($data);
    }

    /**
     * Display heatmap visualization page.
     */
    public function heatmapView(Request $request): View
    {
        $filters = $request->only(['industry', 'category']);
        $data = $this->analyticsService->getJobMarketHeatmap($filters);
        
        return view('analytics.heatmap', [
            'heatmapData' => $data,
            'filters' => $filters,
        ]);
    }

    /**
     * Get salary benchmark data.
     */
    public function salaryBenchmark(Request $request): JsonResponse
    {
        $request->validate([
            'job_title' => 'required|string|max:150',
            'location' => 'nullable|string|max:100',
            'experience_level' => 'nullable|string|in:entry,mid,senior,lead,executive',
        ]);
        
        $data = $this->analyticsService->getSalaryBenchmark(
            $request->input('job_title'),
            $request->input('location'),
            $request->input('experience_level')
        );
        
        return response()->json($data);
    }

    /**
     * Display salary benchmark tool.
     */
    public function salaryBenchmarkView(): View
    {
        return view('analytics.salary-benchmark');
    }

    /**
     * Get skills demand forecast.
     */
    public function skillsForecast(Request $request): JsonResponse
    {
        $filters = $request->only(['industry', 'category', 'trend']);
        
        $data = $this->analyticsService->getSkillsDemandForecast($filters);
        
        return response()->json($data);
    }

    /**
     * Display skills forecast page.
     */
    public function skillsForecastView(Request $request): View
    {
        $filters = $request->only(['industry', 'category', 'trend']);
        $data = $this->analyticsService->getSkillsDemandForecast($filters);
        
        return view('analytics.skills-forecast', [
            'forecastData' => $data,
            'filters' => $filters,
        ]);
    }

    /**
     * Get career path visualization data.
     */
    public function careerPath(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getCareerPathVisualization(
            $request->input('start_role'),
            $request->input('industry')
        );
        
        return response()->json($data);
    }

    /**
     * Display career path visualization.
     */
    public function careerPathView(Request $request): View
    {
        $data = $this->analyticsService->getCareerPathVisualization(
            $request->input('start_role'),
            $request->input('industry')
        );
        
        return view('analytics.career-path', [
            'careerPathData' => $data,
            'startRole' => $request->input('start_role'),
            'industry' => $request->input('industry'),
        ]);
    }

    /**
     * Get application funnel analytics.
     */
    public function applicationFunnel(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employerId = $user->account_type === 'employer' ? $user->id : null;
        
        $filters = $request->only(['start_date', 'end_date']);
        
        $data = $this->analyticsService->getApplicationFunnelAnalytics(
            $employerId,
            $request->input('job_id'),
            $filters
        );
        
        return response()->json($data);
    }

    /**
     * Display application funnel page.
     */
    public function applicationFunnelView(Request $request): View
    {
        $user = Auth::user();
        $employerId = $user->account_type === 'employer' ? $user->id : null;
        
        $data = $this->analyticsService->getApplicationFunnelAnalytics(
            $employerId,
            $request->input('job_id'),
            $request->only(['start_date', 'end_date'])
        );
        
        return view('analytics.application-funnel', [
            'funnelData' => $data,
            'jobId' => $request->input('job_id'),
        ]);
    }

    /**
     * Get time-to-hire metrics.
     */
    public function timeToHire(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employerId = $user->account_type === 'employer' ? $user->id : null;
        
        $data = $this->analyticsService->getTimeToHireMetrics(
            $employerId,
            $request->input('industry'),
            $request->only(['start_date', 'end_date'])
        );
        
        return response()->json($data);
    }

    /**
     * Display time-to-hire metrics page.
     */
    public function timeToHireView(Request $request): View
    {
        $user = Auth::user();
        $employerId = $user->account_type === 'employer' ? $user->id : null;
        
        $data = $this->analyticsService->getTimeToHireMetrics(
            $employerId,
            $request->input('industry')
        );
        
        return view('analytics.time-to-hire', [
            'metricsData' => $data,
        ]);
    }

    /**
     * Get source attribution data.
     */
    public function sourceAttribution(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employerId = $user->account_type === 'employer' ? $user->id : null;
        
        $filters = $request->only(['start_date', 'end_date']);
        
        $data = $this->analyticsService->getSourceAttribution($employerId, $filters);
        
        return response()->json($data);
    }

    /**
     * Display source attribution page.
     */
    public function sourceAttributionView(Request $request): View
    {
        $user = Auth::user();
        $employerId = $user->account_type === 'employer' ? $user->id : null;
        
        $data = $this->analyticsService->getSourceAttribution(
            $employerId,
            $request->only(['start_date', 'end_date'])
        );
        
        return view('analytics.source-attribution', [
            'sourceData' => $data,
        ]);
    }

    /**
     * Get competitor salary comparison.
     */
    public function competitorSalary(Request $request): JsonResponse
    {
        $request->validate([
            'job_title' => 'required|string|max:150',
            'industry' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
        ]);
        
        $data = $this->analyticsService->getCompetitorSalaryComparison(
            $request->input('job_title'),
            $request->input('industry'),
            $request->input('location')
        );
        
        return response()->json($data);
    }

    /**
     * Display competitor salary comparison page.
     */
    public function competitorSalaryView(): View
    {
        return view('analytics.competitor-salary');
    }

    /**
     * Get salary trends data.
     */
    public function salaryTrends(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getSalaryTrends(
            $request->input('industry')
        );
        
        return response()->json($data);
    }
}
