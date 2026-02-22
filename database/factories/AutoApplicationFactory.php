<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\DiscoveredJob;
use App\Models\JobMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutoApplication>
 */
class AutoApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_match_id' => JobMatch::factory(),
            'discovered_job_id' => DiscoveredJob::factory(),
            'resume_url' => $this->faker->url(),
            'cover_letter' => $this->faker->paragraphs(3, true),
            'custom_fields' => json_encode([
                'years_experience' => $this->faker->numberBetween(2, 10),
                'current_ctc' => $this->faker->numberBetween(600000, 2000000),
                'expected_ctc' => $this->faker->numberBetween(800000, 2500000),
                'notice_period' => $this->faker->randomElement(['Immediate', '15 days', '30 days', '60 days']),
            ]),
            'screening_answers' => json_encode([]),
            'status' => $this->faker->randomElement(['pending', 'submitted', 'viewed', 'shortlisted', 'rejected']),
            'status_history' => json_encode([
                [
                    'status' => 'pending',
                    'timestamp' => now()->subDays(2)->toIso8601String(),
                ],
            ]),
            'quality_score' => $this->faker->numberBetween(60, 100),
            'submitted_at' => null,
            'follow_up_at' => null,
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the application has been submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
            'status_history' => json_encode([
                [
                    'status' => 'pending',
                    'timestamp' => now()->subDays(2)->toIso8601String(),
                ],
                [
                    'status' => 'submitted',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]),
        ]);
    }

    /**
     * Indicate that the application is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'submitted_at' => null,
        ]);
    }

    /**
     * Indicate that the application has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'status_history' => json_encode([
                [
                    'status' => 'pending',
                    'timestamp' => now()->subDays(3)->toIso8601String(),
                ],
                [
                    'status' => 'submitted',
                    'timestamp' => now()->subDays(2)->toIso8601String(),
                ],
                [
                    'status' => 'rejected',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]),
        ]);
    }
}
