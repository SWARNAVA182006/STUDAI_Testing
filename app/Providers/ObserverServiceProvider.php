<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Company;
use App\Models\Interview;
use App\Models\Job;
use App\Models\User;
use App\Observers\ApplicationObserver;
use App\Observers\CompanyObserver;
use App\Observers\InterviewObserver;
use App\Observers\JobObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers
        Application::observe(ApplicationObserver::class);
        Company::observe(CompanyObserver::class);
        Interview::observe(InterviewObserver::class);
        Job::observe(JobObserver::class);
        User::observe(UserObserver::class);

        // Prevent lazy loading in development (helps catch N+1 queries)
        Model::preventLazyLoading(app()->environment('local'));
        
        // Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes(app()->environment('local'));
        
        // Prevent accessing missing attributes
        Model::preventAccessingMissingAttributes(app()->environment('local'));
    }
}
