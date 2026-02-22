<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Models\UserSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Calculate statistics
        $totalUsers = User::count();
        $usersThisMonth = User::whereMonth('created_at', now()->month)->count();
        $usersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)->count();
        $usersGrowth = $usersLastMonth > 0 ? (($usersThisMonth - $usersLastMonth) / $usersLastMonth * 100) : 100;

        $activeJobs = Job::where('status', 'active')->count();
        $jobsThisMonth = Job::whereMonth('created_at', now()->month)->count();
        $jobsLastMonth = Job::whereMonth('created_at', now()->subMonth()->month)->count();
        $jobsGrowth = $jobsLastMonth > 0 ? (($jobsThisMonth - $jobsLastMonth) / $jobsLastMonth * 100) : 100;

        $verifiedCompanies = Company::where('is_verified', true)->count();
        $totalCompanies = Company::count();
        $companiesThisMonth = Company::whereMonth('created_at', now()->month)->count();

        $activeSubscriptions = UserSubscription::where('status', 'active')->count();
        $subscriptionsThisMonth = UserSubscription::whereMonth('created_at', now()->month)->count();
        $subscriptionsLastMonth = UserSubscription::whereMonth('created_at', now()->subMonth()->month)->count();
        $subscriptionsGrowth = $subscriptionsLastMonth > 0 ? (($subscriptionsThisMonth - $subscriptionsLastMonth) / $subscriptionsLastMonth * 100) : 100;

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description($usersThisMonth . ' new this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 4, 10, 5, 12, 4, $usersThisMonth])
                ->color($usersGrowth > 0 ? 'success' : ($usersGrowth < 0 ? 'danger' : 'gray'))
                ->icon('heroicon-o-users')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.users.index')),

            Stat::make('Active Jobs', number_format($activeJobs))
                ->description($jobsThisMonth . ' posted this month')
                ->descriptionIcon($jobsGrowth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([12, 8, 14, 10, 18, 11, $jobsThisMonth])
                ->color($jobsGrowth > 0 ? 'success' : ($jobsGrowth < 0 ? 'danger' : 'gray'))
                ->icon('heroicon-o-briefcase')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.jobs.index')),

            Stat::make('Verified Companies', number_format($verifiedCompanies) . ' / ' . number_format($totalCompanies))
                ->description($companiesThisMonth . ' new this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([5, 3, 8, 4, 10, 6, $companiesThisMonth])
                ->color('primary')
                ->icon('heroicon-o-building-office-2')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.companies.index')),

            Stat::make('Active Subscriptions', number_format($activeSubscriptions))
                ->description($subscriptionsThisMonth . ' new this month')
                ->descriptionIcon($subscriptionsGrowth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([8, 6, 12, 7, 15, 9, $subscriptionsThisMonth])
                ->color($subscriptionsGrowth > 0 ? 'success' : ($subscriptionsGrowth < 0 ? 'danger' : 'gray'))
                ->icon('heroicon-o-credit-card')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url(route('filament.admin.resources.user-subscriptions.index')),
        ];
    }
}
