<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    protected $model = UserSubscription::class;

    public function definition(): array
    {
        $billingCycle = $this->faker->randomElement(['monthly', 'yearly']);
        $status = $this->faker->randomElement(['active', 'trialing']);
        $periodStart = Carbon::instance($this->faker->dateTimeBetween('-2 months', '-1 week'));
        $periodEnd = (clone $periodStart);
        if ($billingCycle === 'yearly') {
            $periodEnd->addYear();
        } else {
            $periodEnd->addMonth();
        }
        $trialEndsAt = $status === 'trialing'
            ? (clone $periodStart)->addDays($this->faker->numberBetween(7, 21))
            : null;

        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'billing_cycle' => $billingCycle,
            'status' => $status,
            'amount' => $this->faker->randomFloat(2, 9, 199),
            'currency' => $this->faker->randomElement(['USD', 'INR', 'EUR']),
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'trial_ends_at' => $trialEndsAt,
            'canceled_at' => null,
            'applications_used_this_month' => $this->faker->numberBetween(0, 50),
            'ai_credits_used_this_month' => $this->faker->numberBetween(0, 500),
            'assessments_taken_this_month' => $this->faker->numberBetween(0, 10),
            'last_reset_at' => $periodStart->copy()->addDays($this->faker->numberBetween(1, 25)),
        ];
    }

    public function trialing(): self
    {
        return $this->state(function (array $attributes) {
            $start = Carbon::now()->subDays(3);

            return [
                'status' => 'trialing',
                'current_period_start' => $start,
                'current_period_end' => $start->copy()->addDays(27),
                'trial_ends_at' => $start->copy()->addDays(14),
            ];
        });
    }

    public function canceled(): self
    {
        return $this->state(function (array $attributes) {
            $end = Carbon::now()->subDays(5);

            return [
                'status' => 'canceled',
                'canceled_at' => $end,
                'current_period_end' => $end,
            ];
        });
    }

    public function expired(): self
    {
        return $this->state(function (array $attributes) {
            $end = Carbon::now()->subDays(1);

            return [
                'status' => 'expired',
                'current_period_end' => $end,
            ];
        });
    }
}
