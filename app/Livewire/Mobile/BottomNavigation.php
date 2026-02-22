<?php

declare(strict_types=1);

namespace App\Livewire\Mobile;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BottomNavigation extends Component
{
    public string $activeRoute = '';
    public int $unreadNotifications = 0;
    public int $pendingApplications = 0;
    
    protected $listeners = [
        'notification-received' => 'updateNotificationCount',
        'application-updated' => 'updateApplicationCount',
    ];
    
    public function mount(?string $active = null): void
    {
        $this->activeRoute = $active ?? request()->route()?->getName() ?? 'home';
        $this->loadCounts();
    }
    
    public function loadCounts(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->unreadNotifications = $user->unreadNotifications()->count();
            $this->pendingApplications = $user->jobApplications()
                ->whereIn('status', ['submitted', 'under_review', 'interview_scheduled'])
                ->count();
        }
    }
    
    public function updateNotificationCount(): void
    {
        $this->loadCounts();
    }
    
    public function updateApplicationCount(): void
    {
        $this->loadCounts();
    }
    
    public function getNavItems(): array
    {
        return [
            [
                'name' => 'Home',
                'route' => 'home',
                'icon' => 'home',
                'badge' => null,
            ],
            [
                'name' => 'Jobs',
                'route' => 'mobile.swipe',
                'icon' => 'briefcase',
                'badge' => null,
            ],
            [
                'name' => 'Saved',
                'route' => 'jobs.saved',
                'icon' => 'heart',
                'badge' => null,
            ],
            [
                'name' => 'Applications',
                'route' => 'applications.index',
                'icon' => 'document-text',
                'badge' => $this->pendingApplications > 0 ? $this->pendingApplications : null,
            ],
            [
                'name' => 'Profile',
                'route' => 'profile.show',
                'icon' => 'user-circle',
                'badge' => $this->unreadNotifications > 0 ? $this->unreadNotifications : null,
            ],
        ];
    }
    
    public function isActive(string $route): bool
    {
        return str_starts_with($this->activeRoute, $route) || $this->activeRoute === $route;
    }
    
    public function render()
    {
        return view('livewire.mobile.bottom-navigation', [
            'navItems' => $this->getNavItems(),
        ]);
    }
}
