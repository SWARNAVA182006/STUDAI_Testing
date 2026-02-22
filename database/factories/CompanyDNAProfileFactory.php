<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyDNAProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyDNAProfile>
 */
class CompanyDNAProfileFactory extends Factory
{
    protected $model = CompanyDNAProfile::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'mission_statement' => $this->faker->sentence(12),
            'vision_statement' => $this->faker->sentence(10),
            'core_values' => ['Innovation', 'Integrity', 'Collaboration', 'Excellence'],
            'cultural_dna' => [
                'work_environment' => 'collaborative',
                'decision_style' => 'data-driven',
                'communication' => 'open',
            ],
            'success_traits' => [
                'technical_excellence',
                'team_collaboration',
                'problem_solving',
                'adaptability',
            ],
            'work_style_preferences' => [
                'remote_friendly' => true,
                'flexible_hours' => true,
                'meetings_per_week' => 3,
            ],
            'communication_patterns' => [
                'primary_channel' => 'slack',
                'documentation' => 'high',
                'feedback_frequency' => 'weekly',
            ],
            'decision_making_style' => [
                'approach' => 'consensus',
                'speed' => 'moderate',
                'data_reliance' => 'high',
            ],
            'company_size_category' => $this->faker->randomElement(['startup', 'small', 'medium', 'large', 'enterprise']),
            'growth_stage' => $this->faker->randomElement(['seed', 'early', 'growth', 'mature']),
            'industry_vertical' => $this->faker->randomElement(['Technology', 'Finance', 'Healthcare', 'E-commerce']),
            'employee_count' => $this->faker->numberBetween(10, 5000),
            'avg_tenure_months' => $this->faker->randomFloat(1, 12, 72),
            'retention_rate_1yr' => $this->faker->randomFloat(2, 0.6, 0.98),
            'promotion_rate' => $this->faker->randomFloat(2, 0.05, 0.30),
            'dna_completeness_score' => $this->faker->numberBetween(60, 100),
            'data_quality_score' => $this->faker->numberBetween(50, 100),
            'analysis_confidence' => $this->faker->randomFloat(2, 0.7, 0.99),
            'last_analyzed_at' => now(),
            'total_employees_analyzed' => $this->faker->numberBetween(5, 200),
            'total_hires_analyzed' => $this->faker->numberBetween(3, 100),
            'ai_analysis_summary' => [
                'key_findings' => ['Strong engineering culture', 'High retention in senior roles'],
                'recommendations' => ['Invest in middle management training'],
            ],
        ];
    }

    /**
     * DNA profile that needs re-analysis (stale).
     */
    public function stale(): self
    {
        return $this->state(fn (array $attributes) => [
            'last_analyzed_at' => now()->subDays(90),
            'dna_completeness_score' => 40,
        ]);
    }
}
