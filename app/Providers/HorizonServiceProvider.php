<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Configure Horizon notification settings
        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('admin@studaipath.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#horizon');

        // Add dark mode toggle
        Horizon::night();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     * Only users with 'admin' role can access the Horizon dashboard.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user): bool {
            // Allow access for admin users
            if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
                return true;
            }

            // Fallback: Check for specific admin emails
            return in_array($user->email, [
                // Add admin emails here
                // 'admin@studaipath.com',
            ]);
        });
    }
}
