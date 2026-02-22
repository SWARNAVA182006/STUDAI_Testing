<?php

namespace App\Filament\Pages;

use App\Models\PaymentTransaction;
use App\Models\UserSubscription;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueAnalytics extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Analytics & Reports';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.revenue-analytics';

    protected static ?string $title = 'Revenue Analytics';

    protected static ?string $navigationLabel = 'Revenue Analytics';

    public function getViewData(): array
    {
        return [
            'totalRevenue' => $this->getTotalRevenue(),
            'monthlyRevenue' => $this->getMonthlyRevenue(),
            'averageOrderValue' => $this->getAverageOrderValue(),
            'subscriptionRevenue' => $this->getSubscriptionRevenue(),
            'revenueByGateway' => $this->getRevenueByGateway(),
            'revenueByPlan' => $this->getRevenueByPlan(),
            'topPayingCustomers' => $this->getTopPayingCustomers(),
            'monthlyComparison' => $this->getMonthlyComparison(),
        ];
    }

    protected function getTotalRevenue(): array
    {
        $total = PaymentTransaction::where('status', 'success')->sum('amount') / 100;
        $lastMonth = PaymentTransaction::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('amount') / 100;
        $thisMonth = PaymentTransaction::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount') / 100;

        $growth = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;

        return [
            'total' => number_format($total, 2),
            'thisMonth' => number_format($thisMonth, 2),
            'lastMonth' => number_format($lastMonth, 2),
            'growth' => round($growth, 1),
        ];
    }

    protected function getMonthlyRevenue(): array
    {
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $revenue = PaymentTransaction::where('status', 'success')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount') / 100;
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue,
            ];
        }
        return $monthlyData;
    }

    protected function getAverageOrderValue(): array
    {
        $total = PaymentTransaction::where('status', 'success')->sum('amount') / 100;
        $count = PaymentTransaction::where('status', 'success')->count();
        $average = $count > 0 ? $total / $count : 0;

        $lastMonthTotal = PaymentTransaction::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('amount') / 100;
        $lastMonthCount = PaymentTransaction::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();
        $lastMonthAverage = $lastMonthCount > 0 ? $lastMonthTotal / $lastMonthCount : 0;

        $growth = $lastMonthAverage > 0 ? (($average - $lastMonthAverage) / $lastMonthAverage) * 100 : 0;

        return [
            'average' => number_format($average, 2),
            'lastMonthAverage' => number_format($lastMonthAverage, 2),
            'growth' => round($growth, 1),
        ];
    }

    protected function getSubscriptionRevenue(): array
    {
        $activeSubscriptions = UserSubscription::where('status', 'active')->count();
        $trialSubscriptions = UserSubscription::where('status', 'trialing')->count();
        $canceledThisMonth = UserSubscription::where('status', 'canceled')
            ->whereMonth('canceled_at', Carbon::now()->month)
            ->whereYear('canceled_at', Carbon::now()->year)
            ->count();
        $newThisMonth = UserSubscription::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return [
            'active' => $activeSubscriptions,
            'trial' => $trialSubscriptions,
            'canceledThisMonth' => $canceledThisMonth,
            'newThisMonth' => $newThisMonth,
        ];
    }

    protected function getRevenueByGateway(): array
    {
        return PaymentTransaction::where('status', 'success')
            ->select('payment_gateway', DB::raw('SUM(amount) / 100 as total'))
            ->groupBy('payment_gateway')
            ->get()
            ->map(fn($item) => [
                'gateway' => ucfirst($item->payment_gateway ?? 'Unknown'),
                'total' => number_format($item->total, 2),
                'percentage' => 0, // Will be calculated in view
            ])
            ->toArray();
    }

    protected function getRevenueByPlan(): array
    {
        return UserSubscription::with('subscriptionPlan')
            ->where('status', 'active')
            ->get()
            ->groupBy('subscription_plan_id')
            ->map(fn($subscriptions) => [
                'plan' => $subscriptions->first()->subscriptionPlan->name ?? 'Unknown',
                'count' => $subscriptions->count(),
                'total' => $subscriptions->sum(fn($sub) => $sub->subscriptionPlan->price ?? 0),
            ])
            ->values()
            ->toArray();
    }

    protected function getTopPayingCustomers(): array
    {
        return PaymentTransaction::with('user')
            ->where('status', 'success')
            ->select('user_id', DB::raw('SUM(amount) / 100 as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => $item->user->name ?? 'Unknown',
                'email' => $item->user->email ?? 'N/A',
                'total' => number_format($item->total, 2),
            ])
            ->toArray();
    }

    protected function getMonthlyComparison(): array
    {
        $thisMonth = PaymentTransaction::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount') / 100;
        $lastMonth = PaymentTransaction::where('status', 'success')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('amount') / 100;

        return [
            'thisMonth' => number_format($thisMonth, 2),
            'lastMonth' => number_format($lastMonth, 2),
            'difference' => number_format($thisMonth - $lastMonth, 2),
            'percentageChange' => $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0,
        ];
    }
}
