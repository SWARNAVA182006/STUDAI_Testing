<?php

namespace App\Filament\Pages;

use App\Models\UserSubscription;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AIUsageMonitoring extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected static \UnitEnum|string|null $navigationGroup = 'Analytics & Reports';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.a-i-usage-monitoring';

    protected static ?string $title = 'AI Usage Monitoring';

    protected static ?string $navigationLabel = 'AI Usage';

    public function getViewData(): array
    {
        return [
            'totalCreditsUsed' => $this->getTotalCreditsUsed(),
            'creditsUsedThisMonth' => $this->getCreditsUsedThisMonth(),
            'averageCreditsPerUser' => $this->getAverageCreditsPerUser(),
            'topAIUsers' => $this->getTopAIUsers(),
            'usageBySubscription' => $this->getUsageBySubscription(),
            'dailyUsageTrend' => $this->getDailyUsageTrend(),
            'usersNearLimit' => $this->getUsersNearLimit(),
            'applicationUsageStats' => $this->getApplicationUsageStats(),
        ];
    }

    protected function getTotalCreditsUsed(): array
    {
        $total = UserSubscription::sum('ai_credits_used_this_month');
        $lastMonth = 0; // Requires historical tracking
        $growth = $lastMonth > 0 ? (($total - $lastMonth) / $lastMonth) * 100 : 0;

        return [
            'total' => number_format($total),
            'thisMonth' => number_format($total),
            'lastMonth' => number_format($lastMonth),
            'growth' => round($growth, 1),
        ];
    }

    protected function getCreditsUsedThisMonth(): int
    {
        return UserSubscription::whereMonth('current_period_start', Carbon::now()->month)
            ->whereYear('current_period_start', Carbon::now()->year)
            ->sum('ai_credits_used_this_month');
    }

    protected function getAverageCreditsPerUser(): array
    {
        $activeSubscriptions = UserSubscription::where('status', 'active')->count();
        $totalCredits = UserSubscription::where('status', 'active')->sum('ai_credits_used_this_month');
        $average = $activeSubscriptions > 0 ? $totalCredits / $activeSubscriptions : 0;

        return [
            'average' => round($average, 2),
            'activeUsers' => $activeSubscriptions,
            'totalCredits' => number_format($totalCredits),
        ];
    }

    protected function getTopAIUsers(): array
    {
        return UserSubscription::with(['user', 'subscriptionPlan'])
            ->where('ai_credits_used_this_month', '>', 0)
            ->orderByDesc('ai_credits_used_this_month')
            ->limit(10)
            ->get()
            ->map(function($subscription) {
                $limit = $subscription->subscriptionPlan->ai_credits ?? 0;
                $used = $subscription->ai_credits_used_this_month;
                $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;

                return [
                    'name' => $subscription->user->name ?? 'Unknown',
                    'email' => $subscription->user->email ?? 'N/A',
                    'plan' => $subscription->subscriptionPlan->name ?? 'Unknown',
                    'used' => number_format($used),
                    'limit' => number_format($limit),
                    'percentage' => round($percentage, 1),
                ];
            })
            ->toArray();
    }

    protected function getUsageBySubscription(): array
    {
        return UserSubscription::with('subscriptionPlan')
            ->where('status', 'active')
            ->get()
            ->groupBy('subscription_plan_id')
            ->map(function($subscriptions) {
                $totalUsed = $subscriptions->sum('ai_credits_used_this_month');
                $totalLimit = $subscriptions->sum(fn($sub) => $sub->subscriptionPlan->ai_credits ?? 0);
                $percentage = $totalLimit > 0 ? ($totalUsed / $totalLimit) * 100 : 0;

                return [
                    'plan' => $subscriptions->first()->subscriptionPlan->name ?? 'Unknown',
                    'users' => $subscriptions->count(),
                    'totalUsed' => number_format($totalUsed),
                    'totalLimit' => number_format($totalLimit),
                    'percentage' => round($percentage, 1),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getDailyUsageTrend(): array
    {
        // Placeholder - requires daily usage logging
        // This would need a separate ai_usage_logs table to track daily consumption
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            // Simulate data - in production, query ai_usage_logs table
            $usage = rand(100, 1000);
            $trendData[] = [
                'date' => $date->format('M d'),
                'usage' => $usage,
            ];
        }
        return $trendData;
    }

    protected function getUsersNearLimit(): array
    {
        return UserSubscription::with(['user', 'subscriptionPlan'])
            ->where('status', 'active')
            ->get()
            ->filter(function($subscription) {
                $limit = $subscription->subscriptionPlan->ai_credits ?? 0;
                $used = $subscription->ai_credits_used_this_month;
                $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
                return $percentage >= 80; // 80% or more of limit used
            })
            ->sortByDesc(function($subscription) {
                $limit = $subscription->subscriptionPlan->ai_credits ?? 0;
                $used = $subscription->ai_credits_used_this_month;
                return $limit > 0 ? ($used / $limit) * 100 : 0;
            })
            ->take(20)
            ->map(function($subscription) {
                $limit = $subscription->subscriptionPlan->ai_credits ?? 0;
                $used = $subscription->ai_credits_used_this_month;
                $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
                $remaining = max(0, $limit - $used);

                return [
                    'name' => $subscription->user->name ?? 'Unknown',
                    'email' => $subscription->user->email ?? 'N/A',
                    'plan' => $subscription->subscriptionPlan->name ?? 'Unknown',
                    'used' => number_format($used),
                    'limit' => number_format($limit),
                    'remaining' => number_format($remaining),
                    'percentage' => round($percentage, 1),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getApplicationUsageStats(): array
    {
        $totalApplications = UserSubscription::sum('applications_used_this_month');
        $totalLimit = UserSubscription::with('subscriptionPlan')
            ->where('status', 'active')
            ->get()
            ->sum(fn($sub) => $sub->subscriptionPlan->applications_limit ?? 0);
        $percentage = $totalLimit > 0 ? ($totalApplications / $totalLimit) * 100 : 0;

        $usersOverLimit = UserSubscription::with('subscriptionPlan')
            ->where('status', 'active')
            ->get()
            ->filter(function($subscription) {
                $limit = $subscription->subscriptionPlan->applications_limit ?? 0;
                $used = $subscription->applications_used_this_month;
                return $limit > 0 && $used >= $limit;
            })
            ->count();

        return [
            'totalApplications' => number_format($totalApplications),
            'totalLimit' => number_format($totalLimit),
            'percentage' => round($percentage, 1),
            'usersOverLimit' => $usersOverLimit,
        ];
    }
}
