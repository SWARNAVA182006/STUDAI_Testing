<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Console\Command;

class AssignProSubscription extends Command
{
    protected $signature = 'user:assign-pro {email?}';
    protected $description = 'Assign Pro subscription to a user';

    public function handle(): int
    {
        $email = $this->argument('email') ?? 'admin@studaipath.com';
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }
        
        $plan = SubscriptionPlan::where('slug', 'pro')->first();
        
        if (!$plan) {
            $this->error('Pro plan not found. Run db:seed --class=SubscriptionPlanSeeder first.');
            return 1;
        }
        
        UserSubscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(),
                'applications_used_this_month' => 0,
                'ai_credits_used_this_month' => 0,
            ]
        );
        
        $this->info("Pro subscription assigned to {$user->name} ({$user->email})");
        $this->info("Valid until: " . now()->addYear()->toDateString());
        
        return 0;
    }
}
