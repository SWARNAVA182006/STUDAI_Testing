<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'employer']);
    }

    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        // Get total counts
        $totalJobs = Job::where('company_id', $company->id)->count();
        $activeJobs = Job::where('company_id', $company->id)
            ->where('status', 'published')
            ->where('expires_at', '>', now())
            ->count();
        
        $totalApplications = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->count();

        $newApplications = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->where('status', 'pending')
        ->where('created_at', '>=', now()->subDays(7))
        ->count();

        // Get application status breakdown
        $applicationsByStatus = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->select('status', DB::raw('count(*) as count'))
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

        // Get recent applications (last 10)
        $recentApplications = Application::with(['job', 'user.profile'])
            ->whereHas('job', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->latest()
            ->take(10)
            ->get();

        // Get jobs with application counts
        $jobsWithApplications = Job::where('company_id', $company->id)
            ->withCount(['applications as total_applications'])
            ->withCount(['applications as new_applications' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            }])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get weekly application trend (last 4 weeks)
        $weeklyTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $startDate = now()->subWeeks($i + 1)->startOfWeek();
            $endDate = now()->subWeeks($i)->endOfWeek();
            
            $count = Application::whereHas('job', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

            $weeklyTrend[] = [
                'week' => $startDate->format('M d'),
                'count' => $count
            ];
        }

        // Get top performing jobs
        $topJobs = Job::where('company_id', $company->id)
            ->withCount('applications')
            ->orderBy('applications_count', 'desc')
            ->take(5)
            ->get();

        return view('employer.dashboard.index', compact(
            'company',
            'totalJobs',
            'activeJobs',
            'totalApplications',
            'newApplications',
            'applicationsByStatus',
            'recentApplications',
            'jobsWithApplications',
            'weeklyTrend',
            'topJobs'
        ));
    }

    public function analytics()
    {
        $user = auth()->user();
        $company = $user->company;

        // Monthly application trend (last 12 months)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $count = Application::whereHas('job', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->count();

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'applications' => $count
            ];
        }

        // Applications by job type
        $applicationsByJobType = Job::where('company_id', $company->id)
            ->join('applications', 'jobs.id', '=', 'applications.job_id')
            ->select('jobs.job_type', DB::raw('count(*) as count'))
            ->groupBy('jobs.job_type')
            ->pluck('count', 'job_type')
            ->toArray();

        // Average time to hire (pending to hired status)
        $averageTimeToHire = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })
        ->where('status', 'hired')
        ->whereNotNull('updated_at')
        ->get()
        ->avg(function ($application) {
            return $application->created_at->diffInDays($application->updated_at);
        });

        // Conversion rates
        $totalApps = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->count();

        $shortlistedApps = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->where('status', 'shortlisted')->count();

        $hiredApps = Application::whereHas('job', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->where('status', 'hired')->count();

        $conversionRates = [
            'shortlist_rate' => $totalApps > 0 ? round(($shortlistedApps / $totalApps) * 100, 1) : 0,
            'hire_rate' => $totalApps > 0 ? round(($hiredApps / $totalApps) * 100, 1) : 0,
        ];

        return view('employer.dashboard.analytics', compact(
            'monthlyData',
            'applicationsByJobType',
            'averageTimeToHire',
            'conversionRates',
            'totalApps',
            'shortlistedApps',
            'hiredApps'
        ));
    }
}
