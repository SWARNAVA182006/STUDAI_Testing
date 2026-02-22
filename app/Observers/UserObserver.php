<?php

namespace App\Observers;

use App\Models\User;
use App\Services\CacheService;

class UserObserver
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Clear user-related caches
        $this->cacheService->clearUserCaches($user->id);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Clear user-related caches
        $this->cacheService->clearUserCaches($user->id);
    }
}
