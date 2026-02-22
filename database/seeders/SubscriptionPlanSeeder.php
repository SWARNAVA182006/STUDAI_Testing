<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for getting started with basic job search features',
                'price' => 0.00,
                'currency' => 'INR',
                'billing_period' => 'monthly',
                'razorpay_plan_id' => null,
                'payu_plan_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'ai_credits' => 10,
                'applications_limit' => 5,
                'job_alerts_limit' => 10,
                'priority_support' => false,
                'api_access' => false,
                'api_calls_limit' => 0,
                'features' => [
                    'ai_resume_review' => false,
                    'ai_interview_prep' => false,
                    'ai_cover_letter' => false,
                    'one_click_apply' => false,
                    'advanced_search' => false,
                    'job_alerts' => true,
                    'profile_visibility' => 'basic',
                    'support_level' => 'community',
                    'recommended_for' => 'Job seekers exploring opportunities',
                ],
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Essential tools for active job seekers with AI-powered assistance',
                'price' => 499.00,
                'currency' => 'INR',
                'billing_period' => 'monthly',
                'razorpay_plan_id' => null,
                'payu_plan_id' => null,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'ai_credits' => 100,
                'applications_limit' => 50,
                'job_alerts_limit' => 50,
                'priority_support' => false,
                'api_access' => false,
                'api_calls_limit' => 0,
                'features' => [
                    'ai_resume_review' => true,
                    'ai_interview_prep' => true,
                    'ai_cover_letter' => true,
                    'one_click_apply' => true,
                    'advanced_search' => true,
                    'job_alerts' => true,
                    'profile_visibility' => 'enhanced',
                    'support_level' => 'email',
                    'savings_text' => 'Save 17% with annual billing',
                    'recommended_for' => 'Active job seekers applying to multiple positions',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Unlimited access with premium AI features for serious career growth',
                'price' => 999.00,
                'currency' => 'INR',
                'billing_period' => 'monthly',
                'razorpay_plan_id' => null,
                'payu_plan_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'ai_credits' => -1, // -1 = unlimited
                'applications_limit' => null, // null = unlimited
                'job_alerts_limit' => null,
                'priority_support' => true,
                'api_access' => true,
                'api_calls_limit' => 10000,
                'features' => [
                    'ai_resume_review' => true,
                    'ai_interview_prep' => true,
                    'ai_cover_letter' => true,
                    'one_click_apply' => true,
                    'advanced_search' => true,
                    'job_alerts' => true,
                    'profile_visibility' => 'premium',
                    'support_level' => 'priority',
                    'ai_career_coaching' => true,
                    'ai_salary_insights' => true,
                    'ai_skill_gap_analysis' => true,
                    'resume_variants' => true,
                    'application_tracking' => 'advanced',
                    'savings_text' => 'Save 25% with annual billing',
                    'recommended_for' => 'Professionals seeking comprehensive career support',
                ],
            ],
            [
                'name' => 'Basic Annual',
                'slug' => 'basic-annual',
                'description' => 'All Basic features with 17% savings on annual billing',
                'price' => 4990.00, // ₹499 × 12 - 17% = ₹4,990 (saves ₹998)
                'currency' => 'INR',
                'billing_period' => 'yearly',
                'razorpay_plan_id' => null,
                'payu_plan_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'ai_credits' => 1200, // 100 × 12 months
                'applications_limit' => 600, // 50 × 12 months
                'job_alerts_limit' => 600,
                'priority_support' => false,
                'api_access' => false,
                'api_calls_limit' => 0,
                'features' => [
                    'ai_resume_review' => true,
                    'ai_interview_prep' => true,
                    'ai_cover_letter' => true,
                    'one_click_apply' => true,
                    'advanced_search' => true,
                    'job_alerts' => true,
                    'profile_visibility' => 'enhanced',
                    'support_level' => 'email',
                    'savings_text' => 'Save ₹998 compared to monthly billing',
                    'savings_percentage' => 17,
                    'recommended_for' => 'Job seekers committing to long-term search',
                ],
            ],
            [
                'name' => 'Pro Annual',
                'slug' => 'pro-annual',
                'description' => 'All Pro features with 25% savings on annual billing',
                'price' => 8990.00, // ₹999 × 12 - 25% = ₹8,990 (saves ₹2,998)
                'currency' => 'INR',
                'billing_period' => 'yearly',
                'razorpay_plan_id' => null,
                'payu_plan_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
                'ai_credits' => -1, // unlimited
                'applications_limit' => null, // unlimited
                'job_alerts_limit' => null,
                'priority_support' => true,
                'api_access' => true,
                'api_calls_limit' => 120000, // 10000 × 12 months
                'features' => [
                    'ai_resume_review' => true,
                    'ai_interview_prep' => true,
                    'ai_cover_letter' => true,
                    'one_click_apply' => true,
                    'advanced_search' => true,
                    'job_alerts' => true,
                    'profile_visibility' => 'premium',
                    'support_level' => 'priority',
                    'ai_career_coaching' => true,
                    'ai_salary_insights' => true,
                    'ai_skill_gap_analysis' => true,
                    'resume_variants' => true,
                    'application_tracking' => 'advanced',
                    'savings_text' => 'Save ₹2,998 compared to monthly billing',
                    'savings_percentage' => 25,
                    'recommended_for' => 'Professionals investing in career advancement',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
        $this->command->info('Created plans: Free, Basic, Pro, Basic Annual, Pro Annual');
    }
}
