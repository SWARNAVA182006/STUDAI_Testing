<?php

namespace App\Observers;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class CompanyObserver
{
    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // Clear company cache
        Cache::forget("company_profile:{$company->id}");
        
        // Clear related job listings
        Cache::flush(); // Or use pattern matching with Redis
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        Cache::forget("company_profile:{$company->id}");
    }
}
