<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        $title = $this->faker->jobTitle();
        $employmentType = $this->faker->randomElement([
            'full_time',
            'part_time',
            'contract',
            'internship',
            'freelance',
        ]);
        $experienceLevel = $this->faker->randomElement([
            'entry',
            'mid',
            'senior',
            'lead',
            'executive',
        ]);
        $locationType = $this->faker->randomElement(['onsite', 'remote', 'hybrid']);

        $rawSkills = [
            'PHP', 'Laravel', 'Symfony', 'JavaScript', 'TypeScript', 'Vue.js',
            'React', 'Node.js', 'Redis', 'MySQL', 'PostgreSQL', 'Docker',
            'Kubernetes', 'AWS', 'Azure', 'GCP', 'CI/CD', 'REST APIs',
            'GraphQL', 'Testing', 'Data Structures', 'Algorithms', 'Problem Solving',
        ];

        $requiredSkills = $this->faker->randomElements($rawSkills, $this->faker->numberBetween(4, 7));
        $preferredSkills = array_diff(
            $this->faker->randomElements($rawSkills, $this->faker->numberBetween(3, 5)),
            $requiredSkills
        );

        $salaryMin = $this->faker->optional(0.8)->randomFloat(2, 40000, 180000);
        $salaryMax = $salaryMin ? $this->faker->randomFloat(2, $salaryMin, $salaryMin * 1.4) : $this->faker->optional()->randomFloat(2, 60000, 220000);

        $status = $this->faker->boolean(70) ? 'published' : $this->faker->randomElement(['draft', 'closed']);
        $publishedAt = $status === 'draft'
            ? null
            : $this->faker->dateTimeBetween('-3 months', 'now');
        $expiresAt = $publishedAt
            ? (clone $publishedAt)->modify('+' . $this->faker->numberBetween(30, 120) . ' days')
            : $this->faker->optional()->dateTimeBetween('+15 days', '+90 days');

        $applicationMethod = $this->faker->randomElement(['internal', 'external', 'email']);

        $keywordPool = array_unique(array_merge(
            explode(' ', Str::lower($title)),
            array_map(fn ($skill) => Str::lower($skill), $requiredSkills)
        ));

        return [
            'company_id' => Company::factory(),
            'posted_by' => User::factory(),
            'title' => $title,
            'description' => $this->faker->paragraphs(6, true),
            'location' => $locationType === 'remote'
                ? 'Remote'
                : $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'location_type' => $locationType,
            'employment_type' => $employmentType,
            'experience_level' => $experienceLevel,
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax && $salaryMin && $salaryMax < $salaryMin
                ? $salaryMin + $this->faker->randomFloat(2, 1000, 10000)
                : $salaryMax,
            'salary_currency' => $this->faker->randomElement(['USD', 'EUR', 'INR', 'GBP']),
            'salary_period' => $this->faker->randomElement(['yearly', 'monthly']),
            'required_skills' => array_values($requiredSkills),
            'preferred_skills' => array_values($preferredSkills),
            'requirements' => $this->faker->paragraphs(3),
            'responsibilities' => $this->faker->paragraphs(3),
            'benefits' => $this->faker->randomElements([
                'Health insurance',
                '401(k) matching',
                'Remote work stipend',
                'Professional development budget',
                'Flexible hours',
                'Wellness allowance',
                'Paid parental leave',
            ], $this->faker->numberBetween(2, 5)),
            'application_method' => $applicationMethod,
            'external_url' => $applicationMethod === 'external' ? $this->faker->url() : null,
            'application_email' => $applicationMethod === 'email' ? $this->faker->companyEmail() : null,
            'application_instructions' => $this->faker->optional()->sentence(),
            'status' => $status,
            'is_featured' => $this->faker->boolean(15),
            'is_urgent' => $this->faker->boolean(10),
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'filled_at' => $status === 'closed' ? $this->faker->optional()->dateTimeBetween($publishedAt ?? '-1 month', 'now') : null,
            'views_count' => $this->faker->numberBetween(0, 5000),
            'applications_count' => $this->faker->numberBetween(0, 500),
            'saves_count' => $this->faker->numberBetween(0, 1000),
            'search_keywords' => implode(' ', $keywordPool),
            'ai_embeddings' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(function (array $attributes) {
            $publishedAt = $this->faker->dateTimeBetween('-2 months', 'now');

            return [
                'status' => 'published',
                'published_at' => $publishedAt,
                'expires_at' => (clone $publishedAt)->modify('+' . $this->faker->numberBetween(30, 90) . ' days'),
            ];
        });
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
            'expires_at' => null,
        ]);
    }

    public function remote(): self
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'Remote',
            'location_type' => 'remote',
        ]);
    }
}
