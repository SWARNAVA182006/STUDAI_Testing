<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word() . ' Plan';
        $billingPeriod = $this->faker->randomElement(['monthly', 'yearly']);

        $featurePool = [
            'advanced-ats-tracking',
            'priority-support',
            'ai-resume-review',
            'mock-interview-coaching',
            'market-intelligence',
            'autonomous-job-matching',
            'unlimited-saved-jobs',
            'team-collaboration',
            'api-access',
        ];

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(12),
            'razorpay_plan_id' => 'plan_' . Str::random(12),
            'payu_plan_id' => 'PAYU_' . Str::upper(Str::random(8)),
            'price' => $this->faker->randomFloat(2, 9, 199),
            'currency' => $this->faker->randomElement(['USD', 'INR', 'EUR']),
            'billing_period' => $billingPeriod,
            'features' => $this->faker->randomElements($featurePool, $this->faker->numberBetween(3, 6)),
            'ai_credits' => $this->faker->numberBetween(50, 1000),
            'applications_limit' => $this->faker->optional()->numberBetween(5, 100),
            'job_alerts_limit' => $this->faker->numberBetween(5, 50),
            'priority_support' => $this->faker->boolean(40),
            'api_access' => $this->faker->boolean(20),
            'api_calls_limit' => $this->faker->optional()->numberBetween(1000, 50000),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(25),
            'sort_order' => $this->faker->unique()->numberBetween(1, 50),
        ];
    }

    public function featured(): self
    {
        return $this->state(fn () => [
            'is_featured' => true,
            'priority_support' => true,
        ]);
    }

    public function annual(): self
    {
        return $this->state(fn () => [
            'billing_period' => 'yearly',
        ]);
    }
}
