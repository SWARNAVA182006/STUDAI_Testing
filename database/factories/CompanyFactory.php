<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        $industries = [
            'Software & Technology',
            'Fintech',
            'Healthcare',
            'E-commerce',
            'Education',
            'Consulting',
            'Manufacturing',
            'Media & Entertainment',
            'Logistics & Supply Chain',
        ];

        $companySizes = [
            '1-10 employees',
            '11-50 employees',
            '51-200 employees',
            '201-500 employees',
            '501-1000 employees',
            '1000+ employees',
        ];

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numerify('####'),
            'logo' => $this->faker->optional()->imageUrl(300, 300, 'business', true, Str::slug($name)),
            'website' => $this->faker->optional()->url(),
            'industry' => $this->faker->randomElement($industries),
            'company_size' => $this->faker->randomElement($companySizes),
            'founded_year' => $this->faker->numberBetween(1975, (int) now()->year),
            'headquarters' => $this->faker->city() . ', ' . $this->faker->country(),
            'description' => $this->faker->paragraphs(3, true),
            'is_verified' => $this->faker->boolean(35),
            'is_featured' => $this->faker->boolean(20),
        ];
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function featured(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
