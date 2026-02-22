<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Models\AIGoldenTest;
use App\Models\AIGoldenTestRun;
use App\Models\User;
use App\Services\AI\AIEvaluationService;
use App\Services\AI\AIService;
use App\Services\AI\EmbeddingService;
use App\Services\AI\PromptRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GoldenTestSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected AIEvaluationService $evaluationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AI Service to avoid actual API calls
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generateText')
                ->andReturn('Mocked AI response with relevant content about Python, JavaScript, and experience in software development.');
        });

        // Mock Embedding Service
        $this->mock(EmbeddingService::class, function ($mock) {
            // Return consistent embeddings for similarity testing
            $mock->shouldReceive('generate')
                ->andReturn(array_fill(0, 1536, 0.1));
            $mock->shouldReceive('cosineSimilarity')
                ->andReturn(0.85); // 85% similarity
        });

        // Use real prompt registry with mocked prompts
        $this->mock(PromptRegistryService::class, function ($mock) {
            $mock->shouldReceive('render')
                ->andReturn('Rendered prompt text');
            $mock->shouldReceive('getSystemPrompt')
                ->andReturn('System prompt');
        });

        $this->evaluationService = app(AIEvaluationService::class);
    }

    /** @test */
    public function it_can_create_a_golden_test(): void
    {
        $user = User::factory()->create();

        $test = AIGoldenTest::create([
            'name' => 'test_resume_analysis',
            'category' => 'resume',
            'input' => 'Analyze this resume',
            'expected_output' => 'Expected skills and experience',
            'min_similarity_score' => 0.7,
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('ai_golden_tests', [
            'name' => 'test_resume_analysis',
            'category' => 'resume',
            'is_active' => true,
        ]);

        $this->assertEquals($user->id, $test->created_by);
    }

    /** @test */
    public function it_can_run_a_similarity_evaluation(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'similarity_test',
            'category' => 'general',
            'input' => 'Test input',
            'expected_output' => 'Expected output about Python and JavaScript',
            'min_similarity_score' => 0.7,
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        $result = $this->evaluationService->runTest($test);

        $this->assertTrue($result['passed']);
        $this->assertEquals(0.85, $result['similarity_score']);
        $this->assertArrayHasKey('latency_ms', $result);
    }

    /** @test */
    public function it_can_run_a_keyword_evaluation(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'keyword_test',
            'category' => 'general',
            'input' => 'Test input',
            'expected_output' => 'Not used for keyword tests',
            'required_keywords' => ['Python', 'JavaScript'],
            'forbidden_keywords' => ['error', 'failed'],
            'evaluation_type' => AIGoldenTest::EVAL_KEYWORDS,
            'is_active' => true,
        ]);

        $result = $this->evaluationService->runTest($test);

        $this->assertTrue($result['passed']);
        $this->assertEquals('keywords', $result['details']['method']);
    }

    /** @test */
    public function it_fails_when_required_keywords_missing(): void
    {
        // Override mock to return output without required keywords
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generateText')
                ->andReturn('Response without any relevant keywords');
        });

        $service = app(AIEvaluationService::class);

        $test = AIGoldenTest::create([
            'name' => 'missing_keyword_test',
            'category' => 'general',
            'input' => 'Test input',
            'expected_output' => 'Not used',
            'required_keywords' => ['Python', 'JavaScript'],
            'evaluation_type' => AIGoldenTest::EVAL_KEYWORDS,
            'is_active' => true,
        ]);

        $result = $service->runTest($test);

        $this->assertFalse($result['passed']);
        $this->assertContains('Python', $result['details']['required_missing']);
    }

    /** @test */
    public function it_can_run_a_json_schema_evaluation(): void
    {
        // Mock AI to return valid JSON
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generateText')
                ->andReturn(json_encode([
                    'skills' => ['Python', 'JavaScript'],
                    'experience' => 5,
                ]));
        });

        $service = app(AIEvaluationService::class);

        $test = AIGoldenTest::create([
            'name' => 'json_schema_test',
            'category' => 'general',
            'input' => 'Generate JSON',
            'expected_output' => '{}',
            'expected_json_schema' => [
                'type' => 'object',
                'required' => ['skills', 'experience'],
                'properties' => [
                    'skills' => ['type' => 'array'],
                    'experience' => ['type' => 'integer'],
                ],
            ],
            'evaluation_type' => AIGoldenTest::EVAL_JSON_SCHEMA,
            'is_active' => true,
        ]);

        $result = $service->runTest($test);

        $this->assertTrue($result['passed']);
        $this->assertEquals('json_schema', $result['details']['method']);
    }

    /** @test */
    public function it_fails_json_schema_for_invalid_json(): void
    {
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('generateText')
                ->andReturn('Not valid JSON');
        });

        $service = app(AIEvaluationService::class);

        $test = AIGoldenTest::create([
            'name' => 'invalid_json_test',
            'category' => 'general',
            'input' => 'Generate JSON',
            'expected_output' => '{}',
            'expected_json_schema' => ['type' => 'object'],
            'evaluation_type' => AIGoldenTest::EVAL_JSON_SCHEMA,
            'is_active' => true,
        ]);

        $result = $service->runTest($test);

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('Invalid JSON', $result['details']['error']);
    }

    /** @test */
    public function it_records_test_runs(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'run_recording_test',
            'category' => 'general',
            'input' => 'Test input',
            'expected_output' => 'Expected output',
            'min_similarity_score' => 0.7,
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        $this->evaluationService->runTest($test);

        $test->refresh();

        $this->assertEquals(1, $test->run_count);
        $this->assertEquals(1, $test->pass_count);
        $this->assertEquals(0, $test->fail_count);
        $this->assertNotNull($test->last_run_at);
        $this->assertEquals('passed', $test->last_run_status);

        $this->assertDatabaseHas('ai_golden_test_runs', [
            'golden_test_id' => $test->id,
            'passed' => true,
        ]);
    }

    /** @test */
    public function it_can_run_all_active_tests(): void
    {
        // Create multiple tests
        AIGoldenTest::create([
            'name' => 'test_1',
            'category' => 'resume',
            'input' => 'Input 1',
            'expected_output' => 'Output 1',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        AIGoldenTest::create([
            'name' => 'test_2',
            'category' => 'interview',
            'input' => 'Input 2',
            'expected_output' => 'Output 2',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        AIGoldenTest::create([
            'name' => 'inactive_test',
            'category' => 'general',
            'input' => 'Input 3',
            'expected_output' => 'Output 3',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => false,
        ]);

        $results = $this->evaluationService->runAll();

        $this->assertEquals(2, $results['total']);
        $this->assertEquals(2, $results['passed']);
        $this->assertEquals(0, $results['failed']);
        $this->assertEquals(100, $results['pass_rate']);
    }

    /** @test */
    public function it_can_filter_tests_by_category(): void
    {
        AIGoldenTest::create([
            'name' => 'resume_test',
            'category' => 'resume',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        AIGoldenTest::create([
            'name' => 'interview_test',
            'category' => 'interview',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        $results = $this->evaluationService->runAll('resume');

        $this->assertEquals(1, $results['total']);
        $this->assertEquals('resume_test', $results['results'][0]['test_name']);
    }

    /** @test */
    public function it_calculates_pass_rate_correctly(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'pass_rate_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'run_count' => 10,
            'pass_count' => 8,
            'fail_count' => 2,
        ]);

        $this->assertEquals(80.0, $test->pass_rate);
    }

    /** @test */
    public function it_can_get_statistics(): void
    {
        AIGoldenTest::create([
            'name' => 'stat_test_1',
            'category' => 'resume',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'run_count' => 5,
            'pass_count' => 4,
            'fail_count' => 1,
            'avg_similarity_score' => 0.82,
        ]);

        AIGoldenTest::create([
            'name' => 'stat_test_2',
            'category' => 'interview',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'run_count' => 3,
            'pass_count' => 3,
            'fail_count' => 0,
            'avg_similarity_score' => 0.90,
        ]);

        $stats = $this->evaluationService->getStatistics();

        $this->assertEquals(2, $stats['total_tests']);
        $this->assertEquals(2, $stats['active_tests']);
        $this->assertEquals(8, $stats['total_runs']);
        $this->assertEquals(7, $stats['total_passes']);
        $this->assertEquals(1, $stats['total_fails']);
        $this->assertEquals(87.5, $stats['overall_pass_rate']);
        $this->assertContains('resume', $stats['categories']);
        $this->assertContains('interview', $stats['categories']);
    }

    /** @test */
    public function it_can_get_failing_tests(): void
    {
        AIGoldenTest::create([
            'name' => 'passing_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'last_run_status' => 'passed',
        ]);

        AIGoldenTest::create([
            'name' => 'failing_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'last_run_status' => 'failed',
        ]);

        $failing = $this->evaluationService->getFailingTests();

        $this->assertCount(1, $failing);
        $this->assertEquals('failing_test', $failing->first()->name);
    }

    /** @test */
    public function it_handles_composite_evaluation(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'composite_test',
            'category' => 'general',
            'input' => 'Test input',
            'expected_output' => 'Expected with Python and JavaScript',
            'required_keywords' => ['Python', 'JavaScript'],
            'min_similarity_score' => 0.7,
            'evaluation_type' => AIGoldenTest::EVAL_COMPOSITE,
            'is_active' => true,
        ]);

        $result = $this->evaluationService->runTest($test);

        $this->assertTrue($result['passed']);
        $this->assertEquals('composite', $result['details']['method']);
        $this->assertArrayHasKey('sub_results', $result['details']);
    }

    /** @test */
    public function artisan_command_runs_tests(): void
    {
        AIGoldenTest::create([
            'name' => 'command_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        $this->artisan('ai:golden-tests')
            ->assertSuccessful();
    }

    /** @test */
    public function artisan_command_shows_statistics(): void
    {
        AIGoldenTest::create([
            'name' => 'stats_command_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'run_count' => 5,
            'pass_count' => 5,
        ]);

        $this->artisan('ai:golden-tests --stats')
            ->expectsOutputToContain('AI GOLDEN TEST STATISTICS')
            ->assertSuccessful();
    }

    /** @test */
    public function artisan_command_can_seed_defaults(): void
    {
        $this->artisan('ai:golden-tests --seed')
            ->expectsOutputToContain('Golden tests seeded')
            ->assertSuccessful();

        $this->assertGreaterThan(0, AIGoldenTest::count());
    }

    /** @test */
    public function it_updates_average_similarity_correctly(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'avg_sim_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
            'run_count' => 0,
            'avg_similarity_score' => null,
        ]);

        // Run test twice
        $this->evaluationService->runTest($test);
        $test->refresh();

        $this->assertEquals(1, $test->run_count);
        $this->assertNotNull($test->avg_similarity_score);

        $this->evaluationService->runTest($test);
        $test->refresh();

        $this->assertEquals(2, $test->run_count);
        // Both runs have 0.85 similarity, so average should be 0.85
        $this->assertEquals(0.85, round($test->avg_similarity_score, 2));
    }

    /** @test */
    public function test_run_has_relationship_to_golden_test(): void
    {
        $test = AIGoldenTest::create([
            'name' => 'relationship_test',
            'category' => 'general',
            'input' => 'Input',
            'expected_output' => 'Output',
            'evaluation_type' => AIGoldenTest::EVAL_SIMILARITY,
            'is_active' => true,
        ]);

        $this->evaluationService->runTest($test);

        $run = AIGoldenTestRun::first();

        $this->assertInstanceOf(AIGoldenTest::class, $run->goldenTest);
        $this->assertEquals($test->id, $run->goldenTest->id);
    }
}
