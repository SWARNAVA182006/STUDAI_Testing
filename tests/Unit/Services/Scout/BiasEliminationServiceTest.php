<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Scout;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use App\Services\Scout\BiasEliminationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class BiasEliminationServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected BiasEliminationService $service;
    protected User $candidate;
    protected Company $company;
    protected JobListing $job;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAllAIServices();

        $this->service = app(BiasEliminationService::class);
        $this->candidate = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
        ]);
        $this->company = Company::factory()->create();
        $this->job = JobListing::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->application = Application::factory()->create([
            'user_id' => $this->candidate->id,
            'job_listing_id' => $this->job->id,
        ]);
    }

    public function test_anonymize_candidate_removes_identifying_info(): void
    {
        $result = $this->service->anonymizeCandidate($this->candidate->id);

        $this->assertArrayHasKey('anonymized_id', $result);
        $this->assertArrayHasKey('anonymized_data', $result);

        // Should not contain actual name
        $this->assertStringNotContainsString('John Smith', json_encode($result['anonymized_data']));
        $this->assertStringNotContainsString('john.smith@example.com', json_encode($result['anonymized_data']));
    }

    public function test_anonymize_preserves_skills(): void
    {
        // Add skills to candidate profile
        $this->candidate->profile()->create([
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
        ]);

        $result = $this->service->anonymizeCandidate($this->candidate->id);

        // Skills should be preserved
        $this->assertArrayHasKey('skills', $result['anonymized_data']);
    }

    public function test_anonymize_preserves_experience_years(): void
    {
        $this->candidate->profile()->create([
            'total_experience_years' => 5,
        ]);

        $result = $this->service->anonymizeCandidate($this->candidate->id);

        $this->assertArrayHasKey('experience_years', $result['anonymized_data']);
        $this->assertEquals(5, $result['anonymized_data']['experience_years']);
    }

    public function test_conduct_bias_audit(): void
    {
        $this->mockAIWithJSON([
            'bias_detected' => false,
            'risk_areas' => [],
            'fairness_score' => 95,
            'recommendations' => [],
        ]);

        $result = $this->service->conductAudit($this->company->id, $this->job->id);

        $this->assertArrayHasKey('bias_detected', $result);
        $this->assertArrayHasKey('fairness_score', $result);
        $this->assertFalse($result['bias_detected']);
    }

    public function test_conduct_bias_audit_detects_issues(): void
    {
        $this->mockAIWithJSON([
            'bias_detected' => true,
            'risk_areas' => [
                ['type' => 'gender', 'severity' => 'medium', 'description' => 'Gender imbalance in hiring'],
                ['type' => 'age', 'severity' => 'low', 'description' => 'Slight preference for younger candidates'],
            ],
            'fairness_score' => 65,
            'recommendations' => [
                'Review job description for gendered language',
                'Implement blind resume review',
            ],
        ]);

        $result = $this->service->conductAudit($this->company->id, $this->job->id);

        $this->assertTrue($result['bias_detected']);
        $this->assertCount(2, $result['risk_areas']);
        $this->assertLessThan(70, $result['fairness_score']);
    }

    public function test_get_decision_explanation(): void
    {
        $this->mockAIWithJSON([
            'decision' => 'shortlisted',
            'factors' => [
                ['factor' => 'Technical skills match', 'weight' => 0.35, 'score' => 92],
                ['factor' => 'Experience alignment', 'weight' => 0.25, 'score' => 85],
                ['factor' => 'Education requirements', 'weight' => 0.15, 'score' => 80],
            ],
            'explanation' => 'Candidate was shortlisted based on strong technical skills and relevant experience.',
            'bias_check' => 'No protected characteristics influenced this decision.',
        ]);

        $result = $this->service->getDecisionExplanation($this->application->id);

        $this->assertArrayHasKey('factors', $result);
        $this->assertArrayHasKey('explanation', $result);
        $this->assertArrayHasKey('bias_check', $result);
    }

    public function test_get_diversity_analytics(): void
    {
        $this->mockAIWithJSON([
            'pipeline_diversity' => [
                'gender' => ['male' => 55, 'female' => 40, 'other' => 5],
                'ethnicity' => ['diverse' => true],
                'age_distribution' => ['20-30' => 40, '30-40' => 35, '40+' => 25],
            ],
            'funnel_analysis' => [
                'applied' => 100,
                'screened' => 60,
                'interviewed' => 30,
                'offered' => 10,
            ],
            'drop_off_analysis' => [
                ['stage' => 'screening', 'disparity_detected' => false],
                ['stage' => 'interview', 'disparity_detected' => false],
            ],
        ]);

        $result = $this->service->getDiversityAnalytics($this->company->id, $this->job->id);

        $this->assertArrayHasKey('pipeline_diversity', $result);
        $this->assertArrayHasKey('funnel_analysis', $result);
    }

    public function test_get_discrimination_alerts(): void
    {
        // Create some applications with different outcomes to analyze
        Application::factory()->count(5)->create([
            'job_listing_id' => $this->job->id,
            'status' => 'rejected',
        ]);

        $this->mockAIWithJSON([
            'alerts' => [
                [
                    'type' => 'pattern_alert',
                    'severity' => 'warning',
                    'description' => 'Higher rejection rate for certain candidate profiles',
                    'affected_group' => 'age_40_plus',
                    'statistical_significance' => 0.85,
                ],
            ],
            'total_alerts' => 1,
        ]);

        $result = $this->service->getDiscriminationAlerts($this->company->id);

        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('total_alerts', $result);
    }

    public function test_resolve_alert(): void
    {
        $alertId = 123;

        $result = $this->service->resolveAlert($alertId, 'Reviewed and addressed the issue');

        $this->assertArrayHasKey('resolved', $result);
        $this->assertTrue($result['resolved']);
    }

    public function test_get_fairness_metrics(): void
    {
        $this->mockAIWithJSON([
            'overall_fairness_score' => 88,
            'metrics' => [
                'demographic_parity' => 0.92,
                'equal_opportunity' => 0.89,
                'predictive_equality' => 0.85,
            ],
            'trend' => 'improving',
            'benchmark_comparison' => 'above_industry_average',
        ]);

        $result = $this->service->getFairnessMetrics($this->company->id);

        $this->assertArrayHasKey('overall_fairness_score', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertGreaterThan(0, $result['overall_fairness_score']);
    }

    public function test_anonymize_removes_photos(): void
    {
        $this->candidate->profile()->create([
            'photo_url' => 'https://example.com/photo.jpg',
        ]);

        $result = $this->service->anonymizeCandidate($this->candidate->id);

        $this->assertArrayNotHasKey('photo_url', $result['anonymized_data']);
        $this->assertStringNotContainsString('photo', json_encode($result['anonymized_data']));
    }

    public function test_anonymize_removes_address(): void
    {
        $this->candidate->profile()->create([
            'address' => '123 Main Street, City',
            'city' => 'New York',
        ]);

        $result = $this->service->anonymizeCandidate($this->candidate->id);

        $this->assertArrayNotHasKey('address', $result['anonymized_data']);
        $this->assertStringNotContainsString('123 Main Street', json_encode($result['anonymized_data']));
    }

    public function test_anonymize_removes_age_indicators(): void
    {
        $this->candidate->profile()->create([
            'date_of_birth' => '1990-01-15',
            'graduation_year' => 2012,
        ]);

        $result = $this->service->anonymizeCandidate($this->candidate->id);

        $this->assertArrayNotHasKey('date_of_birth', $result['anonymized_data']);
        $this->assertArrayNotHasKey('graduation_year', $result['anonymized_data']);
    }

    public function test_batch_anonymize(): void
    {
        $candidates = User::factory()->count(3)->create();
        $candidateIds = $candidates->pluck('id')->toArray();

        $results = $this->service->batchAnonymize($candidateIds);

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertArrayHasKey('anonymized_id', $result);
            $this->assertArrayHasKey('anonymized_data', $result);
        }
    }

    public function test_calculate_bias_score_for_job_description(): void
    {
        $this->mockAIWithJSON([
            'bias_score' => 15,
            'issues' => [
                ['type' => 'gendered_language', 'word' => 'rockstar', 'suggestion' => 'top performer'],
            ],
            'overall_rating' => 'good',
            'improved_description' => null,
        ]);

        $result = $this->service->analyzeJobDescriptionBias($this->job->id);

        $this->assertArrayHasKey('bias_score', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertLessThan(100, $result['bias_score']);
    }

    public function test_generate_bias_free_job_description(): void
    {
        $this->mockAIWithResponse('Looking for a skilled developer to join our team...');

        $result = $this->service->generateBiasFreeJobDescription($this->job->id);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('rockstar', strtolower($result));
        $this->assertStringNotContainsString('ninja', strtolower($result));
    }
}
