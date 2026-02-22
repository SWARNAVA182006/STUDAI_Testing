<?php

namespace Database\Factories;

use App\Models\JobSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscoveredJob>
 */
class DiscoveredJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_source_id' => JobSource::factory(),
            'external_id' => $this->faker->uuid(),
            'url' => $this->faker->url(),
            'title' => $this->faker->jobTitle(),
            'company_name' => $this->faker->company(),
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => $this->faker->paragraphs(2, true),
            'extracted_skills' => json_encode($this->faker->randomElements([
                'PHP', 'Laravel', 'MySQL', 'JavaScript', 'Vue.js', 'React', 
                'Node.js', 'Python', 'Docker', 'AWS', 'Git', 'REST API'
            ], rand(3, 6))),
            'location' => $this->faker->randomElement(['Bangalore', 'Mumbai', 'Delhi', 'Pune', 'Hyderabad', 'Remote']),
            'is_remote' => $this->faker->boolean(30),
            'work_arrangement' => $this->faker->randomElement(['onsite', 'remote', 'hybrid']),
            'salary_min' => $this->faker->numberBetween(600000, 1200000),
            'salary_max' => $this->faker->numberBetween(1200000, 2500000),
            'salary_period' => 'yearly',
            'salary_currency' => 'INR',
            'employment_type' => $this->faker->randomElement(['full-time', 'part-time', 'contract']),
            'experience_level' => $this->faker->randomElement(['entry', 'mid', 'senior']),
            'applicant_count' => $this->faker->numberBetween(0, 100),
            'posted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+60 days'),
            'is_processed' => false,
            'is_duplicate' => false,
            'ats_score' => $this->faker->numberBetween(60, 100),
        ];
    }

    /**
     * Indicate that the job is remote.
     */
    public function remote(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'Remote',
            'is_remote' => true,
            'work_arrangement' => 'remote',
        ]);
    }

    /**
     * Indicate that the job has been processed.
     */
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_processed' => true,
        ]);
    }

    /**
     * Indicate that the job is a duplicate.
     */
    public function duplicate(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_duplicate' => true,
            'duplicate_of_id' => 1,
        ]);
    }
}
