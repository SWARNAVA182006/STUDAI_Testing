<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserActivityTracking extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Analytics & Reports';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.user-activity-tracking';

    protected static ?string $title = 'User Activity Tracking';

    protected static ?string $navigationLabel = 'User Activity';

    public function getViewData(): array
    {
        return [
            'userStats' => $this->getUserStats(),
            'recentLogins' => $this->getRecentLogins(),
            'activeUsersToday' => $this->getActiveUsersToday(),
            'userGrowth' => $this->getUserGrowth(),
            'usersByType' => $this->getUsersByType(),
            'topActiveUsers' => $this->getTopActiveUsers(),
            'inactiveUsers' => $this->getInactiveUsers(),
            'newUsersThisWeek' => $this->getNewUsersThisWeek(),
        ];
    }

    protected function getUserStats(): array
    {
        $total = User::count();
        $thisMonth = User::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        $lastMonth = User::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();
        $growth = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;

        return [
            'total' => $total,
            'thisMonth' => $thisMonth,
            'lastMonth' => $lastMonth,
            'growth' => round($growth, 1),
        ];
    }

    protected function getRecentLogins(): array
    {
        // Placeholder - requires login_logs table
        // return DB::table('login_logs')
        //     ->join('users', 'login_logs.user_id', '=', 'users.id')
        //     ->select('users.name', 'users.email', 'login_logs.ip_address', 'login_logs.user_agent', 'login_logs.created_at')
        //     ->orderByDesc('login_logs.created_at')
        //     ->limit(20)
        //     ->get()
        //     ->toArray();

        return User::latest()
            ->limit(20)
            ->get(['id', 'name', 'email', 'created_at', 'updated_at'])
            ->map(fn($user) => [
                'name' => $user->name,
                'email' => $user->email,
                'ip_address' => 'N/A',
                'user_agent' => 'Login logs not implemented',
                'created_at' => $user->updated_at->format('d M Y, H:i'),
            ])
            ->toArray();
    }

    protected function getActiveUsersToday(): int
    {
        // Placeholder - requires activity tracking
        // return User::whereHas('activityLogs', function($query) {
        //     $query->whereDate('created_at', Carbon::today());
        // })->count();

        return User::whereDate('updated_at', Carbon::today())->count();
    }

    protected function getUserGrowth(): array
    {
        $growthData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = User::whereDate('created_at', $date->toDateString())->count();
            $growthData[] = [
                'date' => $date->format('M d'),
                'count' => $count,
            ];
        }
        return $growthData;
    }

    protected function getUsersByType(): array
    {
        return User::select('account_type', DB::raw('COUNT(*) as count'))
            ->groupBy('account_type')
            ->get()
            ->map(fn($item) => [
                'type' => ucfirst($item->account_type ?? 'Unknown'),
                'count' => $item->count,
            ])
            ->toArray();
    }

    protected function getTopActiveUsers(): array
    {
        // Placeholder - requires activity logs
        // return User::withCount('activityLogs')
        //     ->orderByDesc('activity_logs_count')
        //     ->limit(10)
        //     ->get()
        //     ->map(fn($user) => [
        //         'name' => $user->name,
        //         'email' => $user->email,
        //         'actions' => $user->activity_logs_count,
        //         'last_active' => $user->updated_at->diffForHumans(),
        //     ])
        //     ->toArray();

        return User::orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn($user) => [
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => ucfirst($user->account_type ?? 'Unknown'),
                'last_active' => $user->updated_at->diffForHumans(),
            ])
            ->toArray();
    }

    protected function getInactiveUsers(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        return User::where('updated_at', '<', $thirtyDaysAgo)
            ->orderBy('updated_at', 'asc')
            ->limit(20)
            ->get()
            ->map(fn($user) => [
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => ucfirst($user->account_type ?? 'Unknown'),
                'last_active' => $user->updated_at->diffForHumans(),
                'days_inactive' => $user->updated_at->diffInDays(Carbon::now()),
            ])
            ->toArray();
    }

    protected function getNewUsersThisWeek(): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        return User::where('created_at', '>=', $weekStart)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($user) => [
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => ucfirst($user->account_type ?? 'Unknown'),
                'created_at' => $user->created_at->format('d M Y, H:i'),
                'days_ago' => $user->created_at->diffForHumans(),
            ])
            ->toArray();
    }
}
