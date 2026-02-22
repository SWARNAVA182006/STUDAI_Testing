<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;

class WarmUpCache extends Command
{
    protected $signature = 'cache:warmup';
    protected $description = 'Warm up application cache with frequently accessed data';

    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    public function handle()
    {
        $this->info('Starting cache warmup...');

        // Warm up popular jobs
        $this->info('Warming up popular jobs...');
        $this->cacheService->warmUpPopularJobs();
        $this->info('✓ Popular jobs cached');

        // Cache subscription plans
        $this->info('Caching subscription plans...');
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)->get();
        foreach ($plans as $plan) {
            cache()->put("subscription_plan:{$plan->id}", $plan, 3600);
        }
        $this->info('✓ Subscription plans cached');

        // Cache popular companies
        $this->info('Caching popular companies...');
        $companies = \App\Models\Company::where('verified', true)
            ->withCount('jobs')
            ->orderByDesc('jobs_count')
            ->limit(100)
            ->get();
        
        foreach ($companies as $company) {
            cache()->put("company_profile:{$company->id}", $company, 3600);
        }
        $this->info('✓ Popular companies cached');

        $this->info('Cache warmup completed!');
        
        return 0;
    }
}
