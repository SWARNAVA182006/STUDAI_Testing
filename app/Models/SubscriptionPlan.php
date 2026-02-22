<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'razorpay_plan_id',
        'payu_plan_id',
        'price',
        'currency',
        'billing_period',
        'features',
        'ai_credits',
        'applications_limit',
        'job_alerts_limit',
        'priority_support',
        'api_access',
        'api_calls_limit',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'ai_credits' => 'integer',
        'applications_limit' => 'integer',
        'job_alerts_limit' => 'integer',
        'api_calls_limit' => 'integer',
        'priority_support' => 'boolean',
        'api_access' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
    ];

    // Legacy column names compatibility
    public function getApplicationsPerMonthAttribute()
    {
        return $this->applications_limit;
    }

    public function getAiCreditsPerMonthAttribute()
    {
        return $this->ai_credits;
    }

    public function getBillingCycleAttribute()
    {
        return $this->billing_period;
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }
}
