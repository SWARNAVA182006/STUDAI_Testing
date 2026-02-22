<?php

namespace Tests\Feature\Services\AI\Scout;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Services\AI\Scout\PredictiveAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PredictiveAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected PredictiveAnalyticsService $service;
    protected User $jobSeeker;
    protected Company $company;
    protected Job $job;
    protected Application $application;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PredictiveAnalyticsService::class);

        // Create test data
        $this->jobSeeker = User::factory()->create([
            'account_type' => 'job_seeker',
            'name' => 'Test Candidate',
            'email' => 'candidate@test.com',
        ]);

        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'industry' => 'Technology',
        ]);

        $this->job = Job::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Senior Software Engineer',
            'description' => 'We are looking for an experienced software engineer...',
            'requirements' => json_encode([
                'skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL'],
                'experience_years' => 5,
                'education' => 'Bachelor\'s Degree',
            ]),
            'status' => 'published',
        ]);

        $this->application = Application::factory()->create([
            'user_id' => $this->jobSeeker->id,
            'job_id' => $this->job->id,
            'status' => 'under_review',
        ]);

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test success probability prediction.
     *
     * @return void
     */
    public function test_predict_success_probability_returns_valid_data(): void
    {
        $result = $this->service->predictSuccessProbability($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success_probability', $result);
        $this->assertArrayHasKey('confidence_score', $result);
        $this->assertArrayHasKey('success_category', $result);
        $this->assertArrayHasKey('factor_scores', $result);
        $this->assertArrayHasKey('key_strengths', $result);
        $this->assertArrayHasKey('key_concerns', $result);
        $this->assertArrayHasKey('recommendation', $result);

        // Validate data types and ranges
        $this->assertIsFloat($result['success_probability']);
        $this->assertGreaterThanOrEqual(0, $result['success_probability']);
        $this->assertLessThanOrEqual(100, $result['success_probability']);

        $this->assertIsFloat($result['confidence_score']);
        $this->assertGreaterThanOrEqual(0, $result['confidence_score']);
        $this->assertLessThanOrEqual(100, $result['confidence_score']);

        $this->assertIsArray($result['factor_scores']);
        $this->assertIsArray($result['key_strengths']);
        $this->assertIsArray($result['key_concerns']);
    }

    /**
     * Test success probability caching.
     *
     * @return void
     */
    public function test_success_probability_is_cached(): void
    {
        // First call - should compute
        $result1 = $this->service->predictSuccessProbability($this->application);

        // Second call - should use cache
        $result2 = $this->service->predictSuccessProbability($this->application);

        // Results should be identical
        $this->assertEquals($result1, $result2);

        // Verify cache exists
        $cacheKey = "success_prediction_{$this->application->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * Test force refresh bypasses cache.
     *
     * @return void
     */
    public function test_force_refresh_bypasses_cache(): void
    {
        // First call - populate cache
        $result1 = $this->service->predictSuccessProbability($this->application);

        // Second call with force refresh
        $result2 = $this->service->predictSuccessProbability($this->application, ['force_refresh' => true]);

        // Both should have data but may vary slightly due to recalculation
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertArrayHasKey('success_probability', $result1);
        $this->assertArrayHasKey('success_probability', $result2);
    }

    /**
     * Test tenure forecast prediction.
     *
     * @return void
     */
    public function test_forecast_tenure_returns_valid_data(): void
    {
        $result = $this->service->forecastTenure($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('predicted_tenure_months', $result);
        $this->assertArrayHasKey('predicted_tenure_years', $result);
        $this->assertArrayHasKey('tenure_range', $result);
        $this->assertArrayHasKey('player_type', $result);
        $this->assertArrayHasKey('confidence_level', $result);
        $this->assertArrayHasKey('flight_risk_score', $result);
        $this->assertArrayHasKey('is_flight_risk', $result);
        $this->assertArrayHasKey('retention_factors', $result);
        $this->assertArrayHasKey('risk_indicators', $result);

        // Validate ranges
        $this->assertIsInt($result['predicted_tenure_months']);
        $this->assertGreaterThan(0, $result['predicted_tenure_months']);

        $this->assertIsFloat($result['flight_risk_score']);
        $this->assertGreaterThanOrEqual(0, $result['flight_risk_score']);
        $this->assertLessThanOrEqual(100, $result['flight_risk_score']);

        $this->assertIsBool($result['is_flight_risk']);
    }

    /**
     * Test productivity estimation.
     *
     * @return void
     */
    public function test_estimate_time_to_productivity_returns_valid_data(): void
    {
        $result = $this->service->estimateTimeToProductivity($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('estimated_weeks', $result);
        $this->assertArrayHasKey('estimated_months', $result);
        $this->assertArrayHasKey('productivity_category', $result);
        $this->assertArrayHasKey('productivity_milestones', $result);
        $this->assertArrayHasKey('learning_curve_factors', $result);
        $this->assertArrayHasKey('support_requirements', $result);

        // Validate data
        $this->assertIsInt($result['estimated_weeks']);
        $this->assertGreaterThan(0, $result['estimated_weeks']);

        $this->assertIsArray($result['productivity_milestones']);
        $this->assertNotEmpty($result['productivity_milestones']);
    }

    /**
     * Test productivity estimation with custom role complexity.
     *
     * @return void
     */
    public function test_estimate_productivity_with_custom_role_complexity(): void
    {
        // Test with high complexity
        $resultHigh = $this->service->estimateTimeToProductivity(
            $this->application,
            'high'
        );

        // Test with low complexity
        $resultLow = $this->service->estimateTimeToProductivity(
            $this->application,
            'low'
        );

        // High complexity should take longer
        $this->assertGreaterThan(
            $resultLow['estimated_weeks'],
            $resultHigh['estimated_weeks']
        );
    }

    /**
     * Test flight risk identification.
     *
     * @return void
     */
    public function test_identify_flight_risks_returns_valid_data(): void
    {
        $result = $this->service->identifyFlightRisks($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('risk_category', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('mitigation_strategies', $result);

        // Validate ranges
        $this->assertIsFloat($result['risk_score']);
        $this->assertGreaterThanOrEqual(0, $result['risk_score']);
        $this->assertLessThanOrEqual(100, $result['risk_score']);

        // Validate risk level
        $this->assertContains($result['risk_level'], ['low', 'medium', 'high', 'critical']);
    }

    /**
     * Test development needs prediction.
     *
     * @return void
     */
    public function test_predict_development_needs_returns_valid_data(): void
    {
        $result = $this->service->predictDevelopmentNeeds($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('skill_gaps', $result);
        $this->assertArrayHasKey('training_recommendations', $result);
        $this->assertArrayHasKey('development_timeline', $result);

        // Validate structure
        $this->assertIsArray($result['skill_gaps']);
        $this->assertIsArray($result['training_recommendations']);
        $this->assertIsArray($result['development_timeline']);
    }

    /**
     * Test development needs with custom focus areas.
     *
     * @return void
     */
    public function test_predict_development_needs_with_focus_areas(): void
    {
        $focusAreas = ['technical', 'leadership'];
        
        $result = $this->service->predictDevelopmentNeeds(
            $this->application,
            $focusAreas
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('skill_gaps', $result);
        
        // Check that development needs are focused on specified areas
        foreach ($result['skill_gaps'] as $gap) {
            $this->assertArrayHasKey('category', $gap);
            // Category should be one of the focus areas or related
            $this->assertTrue(
                str_contains(strtolower($gap['category']), 'technical') ||
                str_contains(strtolower($gap['category']), 'leadership') ||
                str_contains(strtolower($gap['category']), 'skill') ||
                str_contains(strtolower($gap['category']), 'behavioral')
            );
        }
    }

    /**
     * Test onboarding plan generation.
     *
     * @return void
     */
    public function test_generate_onboarding_plan_returns_valid_data(): void
    {
        $result = $this->service->generateOnboardingPlan($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('onboarding_phases', $result);
        $this->assertArrayHasKey('milestones', $result);
        $this->assertArrayHasKey('estimated_duration_days', $result);
        $this->assertArrayHasKey('resource_assignments', $result);

        // Validate phases
        $this->assertIsArray($result['onboarding_phases']);
        $this->assertNotEmpty($result['onboarding_phases']);

        // Each phase should have required fields
        foreach ($result['onboarding_phases'] as $phase) {
            $this->assertArrayHasKey('phase_name', $phase);
            $this->assertArrayHasKey('duration_days', $phase);
            $this->assertArrayHasKey('activities', $phase);
        }
    }

    /**
     * Test career path prediction.
     *
     * @return void
     */
    public function test_predict_career_path_returns_valid_data(): void
    {
        $result = $this->service->predictCareerPath($this->application);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('current_role', $result);
        $this->assertArrayHasKey('predicted_roles', $result);
        $this->assertArrayHasKey('career_trajectory', $result);
        $this->assertArrayHasKey('succession_potential', $result);
        $this->assertArrayHasKey('development_requirements', $result);

        // Validate data types
        $this->assertIsArray($result['predicted_roles']);
        $this->assertNotEmpty($result['predicted_roles']);

        $this->assertIsFloat($result['succession_potential']);
        $this->assertGreaterThanOrEqual(0, $result['succession_potential']);
        $this->assertLessThanOrEqual(100, $result['succession_potential']);
    }

    /**
     * Test career path with custom horizon.
     *
     * @return void
     */
    public function test_predict_career_path_with_custom_horizon(): void
    {
        $horizon = 3; // 3 years
        
        $result = $this->service->predictCareerPath(
            $this->application,
            $horizon
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('predicted_roles', $result);

        // Check that predicted roles are within the specified horizon
        foreach ($result['predicted_roles'] as $role) {
            $this->assertArrayHasKey('estimated_timeline_months', $role);
            $this->assertLessThanOrEqual($horizon * 12, $role['estimated_timeline_months']);
        }
    }

    /**
     * Test comprehensive predictive report generation.
     *
     * @return void
     */
    public function test_generate_predictive_report_returns_complete_data(): void
    {
        $result = $this->service->generatePredictiveReport($this->application);

        $this->assertIsArray($result);
        
        // Should include all major prediction categories
        $this->assertArrayHasKey('success_prediction', $result);
        $this->assertArrayHasKey('tenure_forecast', $result);
        $this->assertArrayHasKey('productivity_estimate', $result);
        $this->assertArrayHasKey('flight_risk_assessment', $result);
        $this->assertArrayHasKey('development_plan', $result);
        $this->assertArrayHasKey('career_path', $result);
        $this->assertArrayHasKey('overall_recommendation', $result);
        $this->assertArrayHasKey('action_items', $result);

        // Each section should have data
        $this->assertIsArray($result['success_prediction']);
        $this->assertIsArray($result['tenure_forecast']);
        $this->assertIsArray($result['productivity_estimate']);
        $this->assertIsArray($result['flight_risk_assessment']);
        $this->assertIsArray($result['development_plan']);
        $this->assertIsArray($result['career_path']);
    }

    /**
     * Test that service handles missing user profile gracefully.
     *
     * @return void
     */
    public function test_handles_missing_user_profile_gracefully(): void
    {
        // Create application without detailed profile
        $userWithoutProfile = User::factory()->create([
            'account_type' => 'job_seeker',
        ]);

        $applicationWithoutProfile = Application::factory()->create([
            'user_id' => $userWithoutProfile->id,
            'job_id' => $this->job->id,
        ]);

        // Should not throw exception
        $result = $this->service->predictSuccessProbability($applicationWithoutProfile);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success_probability', $result);
        
        // Confidence should be lower due to incomplete data
        $this->assertLessThan(80, $result['confidence_score']);
    }

    /**
     * Test caching across multiple predictions.
     *
     * @return void
     */
    public function test_cache_performance_across_multiple_predictions(): void
    {
        $startTime = microtime(true);

        // First call - should be slower (computing)
        $result1 = $this->service->predictSuccessProbability($this->application);
        
        $firstCallTime = microtime(true) - $startTime;

        $startTime = microtime(true);

        // Second call - should be faster (cached)
        $result2 = $this->service->predictSuccessProbability($this->application);
        
        $secondCallTime = microtime(true) - $startTime;

        // Second call should be significantly faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test that OpenAI integration is properly mocked in tests.
     *
     * @return void
     */
    public function test_openai_insights_are_generated(): void
    {
        $result = $this->service->predictSuccessProbability($this->application);

        $this->assertArrayHasKey('ai_insights', $result);
        $this->assertIsArray($result['ai_insights']);

        // Even in test mode, should have recommendation
        $this->assertArrayHasKey('recommendation', $result);
        $this->assertIsString($result['recommendation']);
        $this->assertNotEmpty($result['recommendation']);
    }

    /**
     * Test database persistence of predictions.
     *
     * @return void
     */
    public function test_predictions_are_persisted_to_database(): void
    {
        // Generate predictions
        $this->service->predictSuccessProbability($this->application);
        
        // Check database
        $this->assertDatabaseHas('success_predictions', [
            'application_id' => $this->application->id,
            'user_id' => $this->jobSeeker->id,
        ]);

        // Generate tenure forecast
        $this->service->forecastTenure($this->application);
        
        $this->assertDatabaseHas('tenure_forecasts', [
            'application_id' => $this->application->id,
            'user_id' => $this->jobSeeker->id,
        ]);

        // Generate productivity estimate
        $this->service->estimateTimeToProductivity($this->application);
        
        $this->assertDatabaseHas('productivity_estimates', [
            'application_id' => $this->application->id,
            'user_id' => $this->jobSeeker->id,
        ]);
    }
}
