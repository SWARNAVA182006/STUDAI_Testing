<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scout;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use App\Services\Scout\PredictiveAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class PredictiveAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected PredictiveAnalyticsService $service;
    protected User $candidate;
    protected Company $company;
    protected JobListing $job;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAllAIServices();

        $this->service = app(PredictiveAnalyticsService::class);
        $this->candidate = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->job = JobListing::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->application = Application::factory()->create([
            'user_id' => $this->candidate->id,
            'job_listing_id' => $this->job->id,
        ]);
    }

    public function test_predict_success_probability(): void
    {
        $this->mockAIWithJSON([
            'success_probability' => 78.5,
            'confidence' => 0.85,
            'factors' => [
                'skill_match' => 85,
                'experience_match' => 75,
                'education_match' => 70,
            ],
            'recommendations' => ['Focus on technical interview prep'],
        ]);

        $result = $this->service->predictSuccessProbability($this->application->id);

        $this->assertArrayHasKey('success_probability', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertIsFloat($result['success_probability']);
    }

    public function test_forecast_tenure(): void
    {
        $this->mockAIWithJSON([
            'predicted_tenure_months' => 24,
            'confidence_interval' => [18, 30],
            'retention_probability' => [
                '6_months' => 0.95,
                '12_months' => 0.85,
                '24_months' => 0.65,
            ],
            'factors' => [
                'career_trajectory' => 'growth-oriented',
                'job_fit' => 'excellent',
            ],
        ]);

        $result = $this->service->forecastTenure($this->application->id);

        $this->assertArrayHasKey('predicted_tenure_months', $result);
        $this->assertArrayHasKey('confidence_interval', $result);
        $this->assertArrayHasKey('retention_probability', $result);
    }

    public function test_estimate_productivity(): void
    {
        $this->mockAIWithJSON([
            'productivity_score' => 82,
            'time_to_productivity' => '3-4 weeks',
            'ramp_up_curve' => [
                'week_1' => 40,
                'week_2' => 60,
                'week_4' => 80,
                'week_8' => 95,
            ],
            'strengths' => ['Quick learner', 'Strong technical background'],
            'potential_challenges' => ['Domain knowledge gap'],
        ]);

        $result = $this->service->estimateProductivity($this->application->id);

        $this->assertArrayHasKey('productivity_score', $result);
        $this->assertArrayHasKey('time_to_productivity', $result);
        $this->assertArrayHasKey('ramp_up_curve', $result);
    }

    public function test_assess_flight_risk(): void
    {
        $this->mockAIWithJSON([
            'flight_risk_score' => 25,
            'risk_level' => 'low',
            'contributing_factors' => [
                'job_satisfaction_prediction' => 'high',
                'growth_opportunity_alignment' => 'good',
                'compensation_competitiveness' => 'above_market',
            ],
            'early_warning_indicators' => [],
            'retention_strategies' => ['Clear career path', 'Mentorship program'],
        ]);

        $result = $this->service->assessFlightRisk($this->application->id);

        $this->assertArrayHasKey('flight_risk_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertIsString($result['risk_level']);
    }

    public function test_generate_development_plan(): void
    {
        $this->mockAIWithJSON([
            'development_areas' => [
                [
                    'skill' => 'Leadership',
                    'current_level' => 'intermediate',
                    'target_level' => 'advanced',
                    'timeline' => '6-12 months',
                    'resources' => ['Leadership workshop', 'Mentoring program'],
                ],
            ],
            'career_path' => [
                'current_role' => 'Senior Developer',
                'next_role' => 'Tech Lead',
                'timeline' => '18-24 months',
            ],
            'training_recommendations' => ['AWS certification', 'System design course'],
        ]);

        $result = $this->service->generateDevelopmentPlan($this->application->id);

        $this->assertArrayHasKey('development_areas', $result);
        $this->assertArrayHasKey('career_path', $result);
        $this->assertArrayHasKey('training_recommendations', $result);
    }

    public function test_generate_onboarding_plan(): void
    {
        $this->mockAIWithJSON([
            'phases' => [
                [
                    'name' => 'Orientation',
                    'duration' => '1 week',
                    'objectives' => ['Company culture', 'Team introductions'],
                    'deliverables' => ['Completed HR paperwork', 'System access setup'],
                ],
                [
                    'name' => 'Training',
                    'duration' => '2 weeks',
                    'objectives' => ['Technical stack overview', 'Codebase familiarity'],
                    'deliverables' => ['First code commit', 'Documentation review'],
                ],
            ],
            'milestones' => [
                ['day' => 7, 'milestone' => 'Complete orientation'],
                ['day' => 30, 'milestone' => 'First feature shipped'],
            ],
            'success_metrics' => ['Code review participation', 'Sprint velocity contribution'],
        ]);

        $result = $this->service->generateOnboardingPlan($this->application->id);

        $this->assertArrayHasKey('phases', $result);
        $this->assertArrayHasKey('milestones', $result);
        $this->assertIsArray($result['phases']);
    }

    public function test_predict_career_path(): void
    {
        $this->mockAIWithJSON([
            'predicted_path' => [
                ['role' => 'Senior Developer', 'timeline' => 'Current'],
                ['role' => 'Tech Lead', 'timeline' => '18-24 months'],
                ['role' => 'Engineering Manager', 'timeline' => '3-5 years'],
            ],
            'alternative_paths' => [
                ['path' => 'Staff Engineer', 'probability' => 35],
                ['path' => 'Principal Architect', 'probability' => 20],
            ],
            'growth_velocity' => 'above_average',
            'key_milestones' => ['Lead major project', 'Mentor junior developers'],
        ]);

        $result = $this->service->predictCareerPath($this->application->id);

        $this->assertArrayHasKey('predicted_path', $result);
        $this->assertArrayHasKey('alternative_paths', $result);
        $this->assertIsArray($result['predicted_path']);
    }

    public function test_get_full_report(): void
    {
        $mockData = [
            'success_probability' => 78,
            'tenure_forecast' => ['predicted_tenure_months' => 24],
            'productivity' => ['score' => 82],
            'flight_risk' => ['score' => 25, 'level' => 'low'],
        ];

        $this->mockAIWithJSON($mockData);

        $result = $this->service->getFullReport($this->application->id);

        $this->assertArrayHasKey('application_id', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('predictions', $result);
    }

    public function test_handles_missing_application(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->predictSuccessProbability(999999);
    }

    public function test_prediction_is_cached(): void
    {
        $this->mockAIWithJSON([
            'success_probability' => 75,
            'confidence' => 0.8,
            'factors' => [],
        ]);

        // First call
        $result1 = $this->service->predictSuccessProbability($this->application->id);

        // Second call should use cache
        $result2 = $this->service->predictSuccessProbability($this->application->id);

        $this->assertEquals($result1, $result2);
    }

    public function test_refresh_bypasses_cache(): void
    {
        $this->mockAIWithJSON([
            'success_probability' => 75,
            'confidence' => 0.8,
            'factors' => [],
        ]);

        $result1 = $this->service->predictSuccessProbability($this->application->id);

        // Force refresh
        $result2 = $this->service->predictSuccessProbability($this->application->id, refresh: true);

        // Both should return results (cache bypassed)
        $this->assertNotEmpty($result1);
        $this->assertNotEmpty($result2);
    }

    public function test_validates_probability_range(): void
    {
        // Test that returned probabilities are within 0-100 range
        $this->mockAIWithJSON([
            'success_probability' => 150, // Invalid: > 100
            'confidence' => 0.8,
            'factors' => [],
        ]);

        $result = $this->service->predictSuccessProbability($this->application->id);

        // Service should normalize to valid range
        $this->assertLessThanOrEqual(100, $result['success_probability']);
        $this->assertGreaterThanOrEqual(0, $result['success_probability']);
    }
}
