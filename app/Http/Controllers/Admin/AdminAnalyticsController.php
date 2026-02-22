<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Models\UserSubscription;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;
    
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->middleware('auth:admin');
        $this->analyticsService = $analyticsService;
    }
    
    /**
     * Main analytics dashboard
     */
    public function dashboard()
    {
        $revenueMetrics = $this->analyticsService->getRevenueMetrics();
        $subscriptionMetrics = $this->analyticsService->getSubscriptionMetrics();
        $userMetrics = $this->analyticsService->getUserMetrics();
        $applicationMetrics = $this->analyticsService->getApplicationMetrics();
        $jobMetrics = $this->analyticsService->getJobMetrics();
        $platformHealth = $this->analyticsService->getPlatformHealth();
        
        return view('admin.analytics.dashboard', compact(
            'revenueMetrics',
            'subscriptionMetrics',
            'userMetrics',
            'applicationMetrics',
            'jobMetrics',
            'platformHealth'
        ));
    }
    
    /**
     * Revenue analytics page
     */
    public function revenue()
    {
        $metrics = $this->analyticsService->getRevenueMetrics();
        $revenueChart = $this->analyticsService->getRevenueChart();
        
        // Payment gateway breakdown
        $gatewayStats = PaymentTransaction::completed()
            ->select('payment_gateway', 
                DB::raw('count(*) as total_transactions'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('avg(amount) as avg_amount'))
            ->groupBy('payment_gateway')
            ->get();
        
        // Revenue by plan
        $revenueByPlan = UserSubscription::where('status', 'active')
            ->select('subscription_plan_id', DB::raw('sum(amount) as total'))
            ->groupBy('subscription_plan_id')
            ->with('subscriptionPlan:id,name,price_monthly,price_yearly')
            ->get()
            ->map(function ($item) {
                return [
                    'plan' => $item->subscriptionPlan->name,
                    'revenue' => $item->total,
                    'subscribers' => UserSubscription::where('subscription_plan_id', $item->subscription_plan_id)
                        ->where('status', 'active')
                        ->count(),
                ];
            });
        
        // Failed payments
        $failedPayments = PaymentTransaction::where('status', 'failed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $failedAmount = PaymentTransaction::where('status', 'failed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        // Refunded payments
        $refundedPayments = PaymentTransaction::where('status', 'refunded')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $refundedAmount = PaymentTransaction::where('status', 'refunded')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        return view('admin.analytics.revenue', compact(
            'metrics',
            'revenueChart',
            'gatewayStats',
            'revenueByPlan',
            'failedPayments',
            'failedAmount',
            'refundedPayments',
            'refundedAmount'
        ));
    }
    
    /**
     * Subscription analytics
     */
    public function subscriptions()
    {
        $metrics = $this->analyticsService->getSubscriptionMetrics();
        
        // Cohort analysis - retention by signup month
        $cohortData = $this->getCohortRetention();
        
        // Subscription lifecycle analysis
        $lifecycleData = UserSubscription::select(
            DB::raw('TIMESTAMPDIFF(MONTH, created_at, COALESCE(canceled_at, NOW())) as months_active'),
            DB::raw('count(*) as count')
        )
            ->groupBy('months_active')
            ->orderBy('months_active')
            ->get();
        
        // Upcoming renewals (next 30 days)
        $upcomingRenewals = UserSubscription::where('status', 'active')
            ->whereBetween('current_period_end', [now(), now()->addDays(30)])
            ->with(['user:id,name,email', 'subscriptionPlan:id,name,price_monthly,price_yearly'])
            ->orderBy('current_period_end')
            ->paginate(20);
        
        // Trial conversions
        $totalTrials = UserSubscription::where('status', 'trialing')->count();
        $convertedTrials = UserSubscription::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->count();
        
        $trialConversionRate = $totalTrials > 0 
            ? ($convertedTrials / $totalTrials) * 100 
            : 0;
        
        return view('admin.analytics.subscriptions', compact(
            'metrics',
            'cohortData',
            'lifecycleData',
            'upcomingRenewals',
            'totalTrials',
            'convertedTrials',
            'trialConversionRate'
        ));
    }
    
    /**
     * User analytics
     */
    public function users()
    {
        $metrics = $this->analyticsService->getUserMetrics();
        $growthChart = $this->analyticsService->getUserGrowthChart();
        
        // User activity distribution
        $activityData = [
            'today' => User::where('last_login_at', '>=', now()->subDay())->count(),
            'this_week' => User::where('last_login_at', '>=', now()->subWeek())->count(),
            'this_month' => User::where('last_login_at', '>=', now()->subMonth())->count(),
            'inactive' => User::where('last_login_at', '<', now()->subMonth())
                ->orWhereNull('last_login_at')
                ->count(),
        ];
        
        // User engagement metrics
        $engagementData = [
            'avg_applications_per_user' => Application::count() / max(1, User::where('account_type', 'job_seeker')->count()),
            'avg_jobs_per_employer' => Job::count() / max(1, User::where('account_type', 'employer')->count()),
            'users_with_complete_profile' => User::where('account_type', 'job_seeker')
                ->whereNotNull('profile_completed_at')
                ->count(),
        ];
        
        // Top users by activity (most applications)
        $topJobSeekers = User::where('account_type', 'job_seeker')
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->take(10)
            ->get(['id', 'name', 'email', 'created_at']);
        
        // Top employers by job postings
        $topEmployers = User::where('account_type', 'employer')
            ->withCount('jobs')
            ->orderByDesc('jobs_count')
            ->take(10)
            ->get(['id', 'name', 'email', 'company_name', 'created_at']);
        
        return view('admin.analytics.users', compact(
            'metrics',
            'growthChart',
            'activityData',
            'engagementData',
            'topJobSeekers',
            'topEmployers'
        ));
    }
    
    /**
     * Application & job analytics
     */
    public function applications()
    {
        $applicationMetrics = $this->analyticsService->getApplicationMetrics();
        $jobMetrics = $this->analyticsService->getJobMetrics();
        $topCompanies = $this->analyticsService->getTopCompanies();
        
        // Application funnel
        $funnelData = [
            'draft' => Application::where('status', 'draft')->count(),
            'submitted' => Application::where('status', 'submitted')->count(),
            'viewed' => Application::where('status', 'viewed')->count(),
            'shortlisted' => Application::where('status', 'shortlisted')->count(),
            'interviewed' => Application::where('status', 'interviewed')->count(),
            'offered' => Application::where('status', 'offered')->count(),
            'rejected' => Application::where('status', 'rejected')->count(),
        ];
        
        // Jobs by category
        $jobsByCategory = Job::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->take(10)
            ->pluck('count', 'category')
            ->toArray();
        
        // Most popular skills (from job requirements)
        $skillsData = Job::where('status', 'published')
            ->get()
            ->pluck('required_skills')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(20)
            ->toArray();
        
        // Application response time (employer avg time to view/respond)
        $avgResponseTime = Application::whereNotNull('viewed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, viewed_at)) as avg_hours')
            ->first()
            ->avg_hours ?? 0;
        
        return view('admin.analytics.applications', compact(
            'applicationMetrics',
            'jobMetrics',
            'topCompanies',
            'funnelData',
            'jobsByCategory',
            'skillsData',
            'avgResponseTime'
        ));
    }
    
    /**
     * Churn analysis
     */
    public function churn()
    {
        $metrics = $this->analyticsService->getSubscriptionMetrics();
        
        // Churn trend (last 12 months)
        $churnTrend = [];
        $retentionTrend = [];
        $labels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            // Active subscriptions at start of month
            $activeStart = UserSubscription::where('status', 'active')
                ->where('created_at', '<', $date->startOfMonth())
                ->count();
            
            // Cancellations during month
            $canceled = UserSubscription::whereNotNull('canceled_at')
                ->whereMonth('canceled_at', $date->month)
                ->whereYear('canceled_at', $date->year)
                ->count();
            
            $monthChurn = $activeStart > 0 ? ($canceled / $activeStart) * 100 : 0;
            $churnTrend[] = round($monthChurn, 2);
            $retentionTrend[] = round(100 - $monthChurn, 2);
        }
        
        // Churn reasons (from cancellation feedback if available)
        // This would require a cancellation_reasons table or feedback column
        $churnReasons = [
            'Too expensive' => 15,
            'Not using enough' => 25,
            'Missing features' => 10,
            'Found alternative' => 20,
            'Other' => 30,
        ];
        
        // At-risk subscriptions (high usage, close to limits)
        $atRiskSubscriptions = UserSubscription::where('status', 'active')
            ->where(function ($query) {
                $query->whereRaw('applications_used_this_month >= (applications_limit * 0.9)')
                    ->orWhereRaw('ai_credits_used_this_month >= (ai_credits_limit * 0.9)');
            })
            ->with(['user:id,name,email', 'subscriptionPlan:id,name'])
            ->paginate(20);
        
        return view('admin.analytics.churn', compact(
            'metrics',
            'churnTrend',
            'retentionTrend',
            'labels',
            'churnReasons',
            'atRiskSubscriptions'
        ));
    }
    
    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $type = $request->input('type', 'revenue');
        $format = $request->input('format', 'csv');
        
        switch ($type) {
            case 'revenue':
                $data = $this->exportRevenue();
                $filename = 'revenue_report_' . now()->format('Y-m-d');
                break;
            
            case 'subscriptions':
                $data = $this->exportSubscriptions();
                $filename = 'subscriptions_report_' . now()->format('Y-m-d');
                break;
            
            case 'users':
                $data = $this->exportUsers();
                $filename = 'users_report_' . now()->format('Y-m-d');
                break;
            
            default:
                abort(400, 'Invalid export type');
        }
        
        if ($format === 'csv') {
            return $this->downloadCsv($data, $filename);
        }
        
        return response()->json($data);
    }
    
    /**
     * Get cohort retention data
     */
    protected function getCohortRetention(): array
    {
        $cohorts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $cohortMonth = now()->subMonths($i);
            $monthKey = $cohortMonth->format('M Y');
            
            // Users who subscribed in this month
            $cohortUsers = UserSubscription::whereMonth('created_at', $cohortMonth->month)
                ->whereYear('created_at', $cohortMonth->year)
                ->pluck('user_id');
            
            if ($cohortUsers->isEmpty()) {
                continue;
            }
            
            $cohortSize = $cohortUsers->count();
            $retentionData = [$cohortSize]; // Month 0 (100% retention)
            
            // Calculate retention for subsequent months
            for ($j = 1; $j <= min($i, 11); $j++) {
                $checkMonth = $cohortMonth->copy()->addMonths($j);
                
                $retained = UserSubscription::whereIn('user_id', $cohortUsers)
                    ->where('status', 'active')
                    ->where('current_period_end', '>=', $checkMonth->endOfMonth())
                    ->count();
                
                $retentionData[] = $retained;
            }
            
            $cohorts[$monthKey] = [
                'size' => $cohortSize,
                'retention' => $retentionData,
            ];
        }
        
        return $cohorts;
    }
    
    /**
     * Export revenue data
     */
    protected function exportRevenue(): array
    {
        return PaymentTransaction::completed()
            ->with(['user:id,name,email', 'userSubscription.subscriptionPlan:id,name'])
            ->get(['id', 'user_id', 'user_subscription_id', 'amount', 'payment_gateway', 'paid_at'])
            ->map(function ($transaction) {
                return [
                    'Transaction ID' => $transaction->id,
                    'User' => $transaction->user->name,
                    'Email' => $transaction->user->email,
                    'Plan' => $transaction->userSubscription->subscriptionPlan->name ?? 'N/A',
                    'Amount' => $transaction->amount,
                    'Gateway' => $transaction->payment_gateway,
                    'Date' => $transaction->paid_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }
    
    /**
     * Export subscriptions data
     */
    protected function exportSubscriptions(): array
    {
        return UserSubscription::with(['user:id,name,email', 'subscriptionPlan:id,name'])
            ->get()
            ->map(function ($subscription) {
                return [
                    'ID' => $subscription->id,
                    'User' => $subscription->user->name,
                    'Email' => $subscription->user->email,
                    'Plan' => $subscription->subscriptionPlan->name,
                    'Status' => $subscription->status,
                    'Billing Cycle' => $subscription->billing_cycle,
                    'Amount' => $subscription->amount,
                    'Started' => $subscription->current_period_start->format('Y-m-d'),
                    'Ends' => $subscription->current_period_end->format('Y-m-d'),
                    'Applications Used' => $subscription->applications_used_this_month,
                    'AI Credits Used' => $subscription->ai_credits_used_this_month,
                ];
            })
            ->toArray();
    }
    
    /**
     * Export users data
     */
    protected function exportUsers(): array
    {
        return User::withCount(['applications', 'jobs'])
            ->with('subscription.subscriptionPlan:id,name')
            ->get()
            ->map(function ($user) {
                return [
                    'ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Account Type' => $user->account_type,
                    'Plan' => $user->subscription->subscriptionPlan->name ?? 'Free',
                    'Applications' => $user->applications_count,
                    'Jobs Posted' => $user->jobs_count,
                    'Joined' => $user->created_at->format('Y-m-d'),
                    'Last Login' => $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
                ];
            })
            ->toArray();
    }
    
    /**
     * Download CSV
     */
    protected function downloadCsv(array $data, string $filename)
    {
        if (empty($data)) {
            abort(404, 'No data to export');
        }
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];
        
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write header row
            fputcsv($file, array_keys($data[0]));
            
            // Write data rows
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
