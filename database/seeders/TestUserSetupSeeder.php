<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;

class TestUserSetupSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('email', 'user@user.com')->first();
        
        if (!$user) {
            $this->command->error('User user@user.com not found!');
            return;
        }

        // Create Profile
        if (!$user->profile) {
            Profile::create([
                'user_id' => $user->id,
                'headline' => 'Aspiring Developer',
                'summary' => 'I am a passionate student looking for opportunities.',
                'current_location' => 'Remote',
                'skills' => ['PHP', 'Laravel', 'JavaScript'],
                'profile_completeness' => 80,
            ]);
            $this->command->info('Profile created for user@user.com');
        } else {
            $this->command->info('Profile already exists.');
        }

        // Create Subscription
        if (!$user->subscription) {
            $plan = SubscriptionPlan::where('slug', 'pro')->first() ?? SubscriptionPlan::first();
            
            UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'applications_used_this_month' => 0,
                'ai_credits_used_this_month' => 0,
            ]);
            $this->command->info('Subscription created for user@user.com');
        } else {
            $this->command->info('Subscription already exists.');
        }
    }
}
