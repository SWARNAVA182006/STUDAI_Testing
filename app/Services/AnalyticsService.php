<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use App\Models\Application;
use App\Models\Job;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    /**
     * Get revenue metrics (MRR, ARR, etc.)
     */
    public function getRevenueMetrics(): array
    {
        return Cache::remember('analytics_revenue_metrics', 3600, function () {
            // Monthly Recurring Revenue (MRR)
            $mrr = UserSubscription::where('status', 'active')
                ->where('billing_cycle', 'monthly')
                ->sum('amount');
            
            // Annual subscriptions converted to monthly
            $annualMrr = UserSubscription::where('status', 'active')
                ->where('billing_cycle', 'yearly')
                ->sum(DB::raw('amount / 12'));
            
            $totalMrr = $mrr + $annualMrr;
            
            // Annual Recurring Revenue (ARR)
            $arr = $totalMrr * 12;
            
            // Total revenue this month
            $monthlyRevenue = PaymentTransaction::completed()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount');
            
            // Total revenue last month
            $lastMonthRevenue = PaymentTransaction::completed()
                ->whereMonth('paid_at', now()->subMonth()->month)
                ->whereYear('paid_at', now()->subMonth()->year)
                ->sum('amount');
            
            // Revenue growth
            $revenueGrowth = $lastMonthRevenue > 0 
                ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
                : 0;
            
            // Average Revenue Per User (ARPU)
            $activeSubscriptions = UserSubscription::where('status', 'active')->count();
            $arpu = $activeSubscriptions > 0 ? $totalMrr / $activeSubscriptions : 0;
            
            // Lifetime Value (LTV) - simplified calculation
            $avgSubscriptionMonths = 12; // Assume 1 year average
            $ltv = $arpu * $avgSubscriptionMonths;
            
            return [
                'mrr' => round($totalMrr, 2),
                'arr' => round($arr, 2),
                'monthly_revenue' => round($monthlyRevenue, 2),
                'last_month_revenue' => round($lastMonthRevenue, 2),
                'revenue_growth' => round($revenueGrowth, 2),
                'arpu' => round($arpu, 2),
                'ltv' => round($ltv, 2),
            ];
        });
    }
    
    /**
     * Get subscription metrics
     */
    public function getSubscriptionMetrics(): array
    {
        return Cache::remember('analytics_subscription_metrics', 3600, function () {
            $totalSubscriptions = UserSubscription::count();
            $activeSubscriptions = UserSubscription::where('status', 'active')->count();
            $trialingSubscriptions = UserSubscription::where('status', 'trialing')->count();
            $canceledSubscriptions = UserSubscription::where('status', 'canceled')->count();
            $expiredSubscriptions = UserSubscription::where('status', 'expired')->count();
            
            // New subscriptions this month
            $newThisMonth = UserSubscription::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            // Cancellations this month
            $canceledThisMonth = UserSubscription::whereNotNull('canceled_at')
                ->whereMonth('canceled_at', now()->month)
                ->whereYear('canceled_at', now()->year)
                ->count();
            
            // Churn rate (canceled / total active at start of month)
            $activeStartOfMonth = UserSubscription::where('status', 'active')
                ->where('created_at', '<', now()->startOfMonth())
                ->count();
            
            $churnRate = $activeStartOfMonth > 0 
                ? ($canceledThisMonth / $activeStartOfMonth) * 100 
                : 0;
            
            // Retention rate
            $retentionRate = 100 - $churnRate;
            
            // Plan distribution
            $planDistribution = UserSubscription::where('status', 'active')
                ->select('subscription_plan_id', DB::raw('count(*) as count'))
                ->groupBy('subscription_plan_id')
                ->with('subscriptionPlan:id,name')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->subscriptionPlan->name => $item->count];
                })
                ->toArray();
            
            return [
                'total' => $totalSubscriptions,
                'active' => $activeSubscriptions,
                'trialing' => $trialingSubscriptions,
                'canceled' => $canceledSubscriptions,
                'expired' => $expiredSubscriptions,
                'new_this_month' => $newThisMonth,
                'canceled_this_month' => $canceledThisMonth,
                'churn_rate' => round($churnRate, 2),
                'retention_rate' => round($retentionRate, 2),
                'plan_distribution' => $planDistribution,
            ];
        });
    }
    
    /**
     * Get user metrics
     */
    public function getUserMetrics(): array
    {
        return Cache::remember('analytics_user_metrics', 3600, function () {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $jobSeekers = User::where('account_type', 'job_seeker')->count();
            $employers = User::where('account_type', 'employer')->count();
            
            // New users this month
            $newUsersThisMonth = User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            // New users last month
            $newUsersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();
            
            // User growth rate
            $userGrowth = $newUsersLastMonth > 0 
                ? (($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100 
                : 0;
            
            // Users who logged in this week
            $activeThisWeek = User::where('last_login_at', '>=', now()->subWeek())->count();
            
            // Conversion rate (users with paid subscriptions / total users)
            $paidUsers = UserSubscription::where('status', 'active')
                ->whereHas('subscriptionPlan', function ($query) {
                    $query->where('price_monthly', '>', 0);
                })
                ->count();
            
            $conversionRate = $totalUsers > 0 ? ($paidUsers / $totalUsers) * 100 : 0;
            
            return [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'job_seekers' => $jobSeekers,
                'employers' => $employers,
                'new_this_month' => $newUsersThisMonth,
                'new_last_month' => $newUsersLastMonth,
                'user_growth' => round($userGrowth, 2),
                'active_this_week' => $activeThisWeek,
                'paid_users' => $paidUsers,
                'conversion_rate' => round($conversionRate, 2),
            ];
        });
    }
    
    /**
     * Get application metrics
     */
    public function getApplicationMetrics(): array
    {
        return Cache::remember('analytics_application_metrics', 3600, function () {
            $totalApplications = Application::count();
            
            // Applications this month
            $thisMonth = Application::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            // Applications by status
            $byStatus = Application::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Average match score
            $avgMatchScore = Application::whereNotNull('match_score')->avg('match_score');
            
            // Success rate (offered / total submitted)
            $submitted = Application::whereIn('status', ['submitted', 'viewed', 'shortlisted', 'interviewed', 'offered', 'rejected'])->count();
            $offered = Application::where('status', 'offered')->count();
            $successRate = $submitted > 0 ? ($offered / $submitted) * 100 : 0;
            
            return [
                'total' => $totalApplications,
                'this_month' => $thisMonth,
                'by_status' => $byStatus,
                'avg_match_score' => round($avgMatchScore ?? 0, 1),
                'success_rate' => round($successRate, 2),
            ];
        });
    }
    
    /**
     * Get job metrics
     */
    public function getJobMetrics(): array
    {
        return Cache::remember('analytics_job_metrics', 3600, function () {
            $totalJobs = Job::count();
            $activeJobs = Job::where('status', 'published')->count();
            
            // Jobs posted this month
            $thisMonth = Job::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            // Jobs by employment type
            $byType = Job::select('employment_type', DB::raw('count(*) as count'))
                ->groupBy('employment_type')
                ->pluck('count', 'employment_type')
                ->toArray();
            
            // Jobs by work mode
            $byWorkMode = Job::select('work_mode', DB::raw('count(*) as count'))
                ->groupBy('work_mode')
                ->pluck('count', 'work_mode')
                ->toArray();
            
            // Average applications per job
            $avgApplications = Job::withCount('applications')->avg('applications_count');
            
            return [
                'total' => $totalJobs,
                'active' => $activeJobs,
                'this_month' => $thisMonth,
                'by_employment_type' => $byType,
                'by_work_mode' => $byWorkMode,
                'avg_applications_per_job' => round($avgApplications ?? 0, 1),
            ];
        });
    }
    
    /**
     * Get revenue chart data (last 12 months)
     */
    public function getRevenueChart(): array
    {
        return Cache::remember('analytics_revenue_chart', 3600, function () {
            $months = [];
            $revenue = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthRevenue = PaymentTransaction::completed()
                    ->whereMonth('paid_at', $date->month)
                    ->whereYear('paid_at', $date->year)
                    ->sum('amount');
                
                $revenue[] = round($monthRevenue, 2);
            }
            
            return [
                'labels' => $months,
                'data' => $revenue,
            ];
        });
    }
    
    /**
     * Get user growth chart data (last 12 months)
     */
    public function getUserGrowthChart(): array
    {
        return Cache::remember('analytics_user_growth_chart', 3600, function () {
            $months = [];
            $users = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = $date->format('M Y');
                
                $monthUsers = User::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count();
                
                $users[] = $monthUsers;
            }
            
            return [
                'labels' => $months,
                'data' => $users,
            ];
        });
    }
    
    /**
     * Get top performing companies
     */
    public function getTopCompanies(int $limit = 10): array
    {
        return Cache::remember("analytics_top_companies_{$limit}", 3600, function () use ($limit) {
            return Company::withCount(['jobs', 'jobs as active_jobs_count' => function ($query) {
                    $query->where('status', 'published');
                }])
                ->orderByDesc('active_jobs_count')
                ->take($limit)
                ->get(['id', 'name', 'logo'])
                ->toArray();
        });
    }
    
    /**
     * Get platform health metrics
     */
    public function getPlatformHealth(): array
    {
        return [
            'jobs_posted_today' => Job::whereDate('created_at', today())->count(),
            'applications_today' => Application::whereDate('created_at', today())->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'payments_today' => PaymentTransaction::completed()
                ->whereDate('paid_at', today())
                ->count(),
            'revenue_today' => PaymentTransaction::completed()
                ->whereDate('paid_at', today())
                ->sum('amount'),
        ];
    }
}
