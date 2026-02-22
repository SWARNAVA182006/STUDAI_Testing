<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CompanyInterviewData;
use App\Models\InterviewQuestion;
use App\Services\AI\InterviewQuestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class InterviewQuestionGeneratorTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected InterviewQuestionGenerator $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InterviewQuestionGenerator::class);
    }

    // ---------------------------------------------------------------
    // generateForCompanyRole
    // ---------------------------------------------------------------

    public function test_generate_for_company_role_returns_questions_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        [
                            'question' => 'Tell me about a time you resolved a team conflict.',
                            'type' => 'behavioral',
                            'category' => 'conflict_resolution',
                            'difficulty' => 'medium',
                            'key_points' => ['Empathy', 'Communication', 'Outcome'],
                            'evaluation_criteria' => ['STAR format', 'Resolution quality'],
                            'follow_ups' => ['What would you do differently?'],
                            'typical_time_minutes' => 3,
                        ],
                        [
                            'question' => 'Explain REST vs GraphQL.',
                            'type' => 'technical',
                            'category' => 'api_design',
                            'difficulty' => 'medium',
                            'key_points' => ['Trade-offs', 'Use cases'],
                            'evaluation_criteria' => ['Depth of understanding'],
                            'follow_ups' => ['When would you choose one over the other?'],
                            'typical_time_minutes' => 5,
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 500]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $company = $this->createCompanyInterviewData();

        $result = $this->service->generateForCompanyRole($company, 'Software Engineer', 2);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('question', $result[0]);
        $this->assertArrayHasKey('type', $result[0]);
        $this->assertArrayHasKey('difficulty', $result[0]);
    }

    public function test_generate_for_company_role_returns_fallback_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service error'], 500)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $company = $this->createCompanyInterviewData();

        $result = $this->service->generateForCompanyRole($company, 'Software Engineer', 3);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        // Fallback questions have a specific structure
        $this->assertArrayHasKey('question', $result[0]);
        $this->assertArrayHasKey('type', $result[0]);
    }

    // ---------------------------------------------------------------
    // generateBehavioral
    // ---------------------------------------------------------------

    public function test_generate_behavioral_returns_star_questions(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        [
                            'question' => 'Tell me about a time when you had to lead a project under a tight deadline.',
                            'category' => 'leadership',
                            'difficulty' => 'hard',
                            'key_points' => ['Time management', 'Delegation', 'Outcome'],
                            'follow_ups' => ['How did you prioritize tasks?'],
                        ],
                        [
                            'question' => 'Describe a situation where you influenced a decision without formal authority.',
                            'category' => 'leadership',
                            'difficulty' => 'medium',
                            'key_points' => ['Persuasion', 'Data-driven approach'],
                            'follow_ups' => ['What was the result?'],
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 300]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $result = $this->service->generateBehavioral('Engineering Manager', 'leadership', 2);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('category', $result[0]);
        $this->assertEquals('leadership', $result[0]['category']);
    }

    // ---------------------------------------------------------------
    // generateTechnical
    // ---------------------------------------------------------------

    public function test_generate_technical_returns_questions_for_tech_stack(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        [
                            'question' => 'How does the Laravel service container resolve dependencies?',
                            'type' => 'conceptual',
                            'difficulty' => 'medium',
                            'topic' => 'Laravel',
                            'evaluation_criteria' => ['Understanding of DI', 'Practical examples'],
                            'key_concepts' => ['Dependency Injection', 'Service Container', 'Binding'],
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 250]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $result = $this->service->generateTechnical('Backend Developer', ['PHP', 'Laravel'], 1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('topic', $result[0]);
        $this->assertArrayHasKey('key_concepts', $result[0]);
    }

    public function test_generate_technical_returns_defaults_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Timeout'], 504)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $result = $this->service->generateTechnical('Developer', [], 3);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    // ---------------------------------------------------------------
    // generateFollowUp
    // ---------------------------------------------------------------

    public function test_generate_follow_up_returns_array_of_questions(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'What specific metrics did you use to measure success?',
                        'How did stakeholders react to your approach?',
                        'What would you change if you faced a similar situation?',
                    ])]]
                ],
                'usage' => ['totalTokens' => 80]
            ], 200)
        ]);

        $question = InterviewQuestion::factory()->create([
            'question_text' => 'Tell me about a time you improved a process.',
        ]);

        $result = $this->service->generateFollowUp(
            $question,
            'I streamlined the deployment pipeline by introducing CI/CD.'
        );

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function test_generate_follow_up_returns_generic_fallback_on_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Error'], 500)
        ]);

        $question = InterviewQuestion::factory()->create([
            'question_text' => 'Describe a challenge.',
        ]);

        $result = $this->service->generateFollowUp($question, 'Short answer.');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContains('Can you elaborate on that?', $result);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function createCompanyInterviewData(array $attributes = []): CompanyInterviewData
    {
        $defaults = [
            'company_name' => 'Test Company',
            'industry' => 'Technology',
            'company_size' => 'medium',
            'company_culture' => 'Collaborative and innovative',
            'average_difficulty' => 6,
        ];

        return CompanyInterviewData::factory()->create(array_merge($defaults, $attributes));
    }
}
