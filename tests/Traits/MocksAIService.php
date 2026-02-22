<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Services\AI\AIService;
use Mockery;
use Mockery\MockInterface;

/**
 * Trait for mocking AI services in tests.
 *
 * Provides deterministic, predictable responses for AI-dependent tests,
 * eliminating the need for actual API calls and ensuring consistent test results.
 *
 * Usage:
 *   use Tests\Traits\MocksAIService;
 *
 *   class MyTest extends TestCase
 *   {
 *       use MocksAIService;
 *
 *       public function test_something(): void
 *       {
 *           $this->mockAI();
 *           // or
 *           $this->mockAIResponse('Custom response');
 *           // or
 *           $this->mockAIJSON(['key' => 'value']);
 *       }
 *   }
 */
trait MocksAIService
{
    /**
     * The AIService mock instance.
     */
    protected MockInterface $aiServiceMock;

    /**
     * Default mock responses for different AI operations.
     */
    protected array $defaultAIResponses = [
        'text' => 'This is a mocked AI text response for testing purposes.',
        'json' => [
            'success' => true,
            'data' => [
                'analysis' => 'Mocked analysis result',
                'score' => 85,
                'recommendations' => ['Improve X', 'Consider Y', 'Focus on Z'],
            ],
        ],
        'embeddings' => [], // Will be filled with 1536 floats
    ];

    /**
     * Set up the AI service mock with default responses.
     *
     * @return MockInterface
     */
    protected function mockAI(): MockInterface
    {
        $this->aiServiceMock = Mockery::mock(AIService::class);

        // Mock generateText
        $this->aiServiceMock
            ->shouldReceive('generateText')
            ->byDefault()
            ->andReturn($this->defaultAIResponses['text']);

        // Mock generateJSON
        $this->aiServiceMock
            ->shouldReceive('generateJSON')
            ->byDefault()
            ->andReturn($this->defaultAIResponses['json']);

        // Mock generateEmbeddings - returns 1536-dimensional vector
        $this->aiServiceMock
            ->shouldReceive('generateEmbeddings')
            ->byDefault()
            ->andReturn($this->generateMockEmbedding());

        // Mock forUser (returns self for chaining)
        $this->aiServiceMock
            ->shouldReceive('forUser')
            ->byDefault()
            ->andReturnSelf();

        // Bind the mock to the container
        $this->app->instance(AIService::class, $this->aiServiceMock);

        return $this->aiServiceMock;
    }

    /**
     * Mock AIService with a specific text response.
     *
     * @param string $response
     * @return MockInterface
     */
    protected function mockAIResponse(string $response): MockInterface
    {
        $mock = $this->mockAI();
        $mock->shouldReceive('generateText')->andReturn($response);
        return $mock;
    }

    /**
     * Mock AIService with a specific JSON response.
     *
     * @param array $response
     * @return MockInterface
     */
    protected function mockAIJSON(array $response): MockInterface
    {
        $mock = $this->mockAI();
        $mock->shouldReceive('generateJSON')->andReturn($response);
        return $mock;
    }

    /**
     * Mock AIService with specific embeddings.
     *
     * @param array $embeddings
     * @return MockInterface
     */
    protected function mockAIEmbeddings(array $embeddings): MockInterface
    {
        $mock = $this->mockAI();
        $mock->shouldReceive('generateEmbeddings')->andReturn($embeddings);
        return $mock;
    }

    /**
     * Mock AIService to throw an exception.
     *
     * @param string $message
     * @param string $exceptionClass
     * @return MockInterface
     */
    protected function mockAIError(string $message = 'AI service unavailable', string $exceptionClass = \Exception::class): MockInterface
    {
        $mock = $this->mockAI();
        $mock->shouldReceive('generateText')->andThrow(new $exceptionClass($message));
        $mock->shouldReceive('generateJSON')->andThrow(new $exceptionClass($message));
        $mock->shouldReceive('generateEmbeddings')->andThrow(new $exceptionClass($message));
        return $mock;
    }

    /**
     * Configure specific method expectations on the AI mock.
     *
     * @param string $method
     * @param array $args
     * @param mixed $return
     * @return MockInterface
     */
    protected function expectAICall(string $method, array $args = [], mixed $return = null): MockInterface
    {
        if (!isset($this->aiServiceMock)) {
            $this->mockAI();
        }

        $expectation = $this->aiServiceMock->shouldReceive($method);

        if (!empty($args)) {
            $expectation->withArgs($args);
        }

        if ($return !== null) {
            $expectation->andReturn($return);
        }

        return $this->aiServiceMock;
    }

    /**
     * Generate a deterministic mock embedding vector.
     *
     * Returns a 1536-dimensional vector (OpenAI ada-002 compatible)
     * with deterministic values for reproducible tests.
     *
     * @param int $seed Optional seed for reproducibility
     * @return array
     */
    protected function generateMockEmbedding(int $seed = 42): array
    {
        $embedding = [];
        mt_srand($seed);

        for ($i = 0; $i < 1536; $i++) {
            // Generate values between -1 and 1
            $embedding[] = (mt_rand() / mt_getrandmax()) * 2 - 1;
        }

        return $embedding;
    }

    /**
     * Generate multiple mock embeddings for batch tests.
     *
     * @param int $count
     * @return array
     */
    protected function generateMockEmbeddings(int $count): array
    {
        $embeddings = [];

        for ($i = 0; $i < $count; $i++) {
            $embeddings[] = $this->generateMockEmbedding($i);
        }

        return $embeddings;
    }

    /**
     * Assert that generateText was called with specific arguments.
     *
     * @param string $expectedPrompt
     * @param string|null $expectedSystem
     * @return void
     */
    protected function assertAITextCalled(string $expectedPrompt, ?string $expectedSystem = null): void
    {
        $this->aiServiceMock
            ->shouldHaveReceived('generateText')
            ->with(
                Mockery::on(fn($prompt) => str_contains($prompt, $expectedPrompt)),
                Mockery::any(),
                Mockery::any()
            );
    }

    /**
     * Assert that generateJSON was called.
     *
     * @return void
     */
    protected function assertAIJSONCalled(): void
    {
        $this->aiServiceMock->shouldHaveReceived('generateJSON');
    }

    /**
     * Assert that generateEmbeddings was called with specific text.
     *
     * @param string $expectedText
     * @return void
     */
    protected function assertAIEmbeddingsCalled(string $expectedText): void
    {
        $this->aiServiceMock
            ->shouldHaveReceived('generateEmbeddings')
            ->with(Mockery::on(fn($text) => str_contains($text, $expectedText)));
    }

    /**
     * Get the mock instance if needed for additional configuration.
     *
     * @return MockInterface
     */
    protected function getAIMock(): MockInterface
    {
        if (!isset($this->aiServiceMock)) {
            $this->mockAI();
        }

        return $this->aiServiceMock;
    }

    /**
     * Mock responses for specific AI features.
     */

    /**
     * Mock resume analysis response.
     *
     * @param array $overrides
     * @return MockInterface
     */
    protected function mockResumeAnalysis(array $overrides = []): MockInterface
    {
        $response = array_merge([
            'skills' => ['PHP', 'Laravel', 'MySQL', 'JavaScript', 'Vue.js'],
            'experience_years' => 5,
            'education' => ['Bachelor of Computer Science'],
            'strengths' => ['Strong backend skills', 'Team leadership'],
            'improvements' => ['Add more quantified achievements'],
            'ats_score' => 78,
            'keyword_coverage' => 82,
        ], $overrides);

        return $this->mockAIJSON($response);
    }

    /**
     * Mock skill gap analysis response.
     *
     * @param array $overrides
     * @return MockInterface
     */
    protected function mockSkillGapAnalysis(array $overrides = []): MockInterface
    {
        $response = array_merge([
            'gaps' => [
                ['skill' => 'TypeScript', 'priority' => 'high', 'market_demand' => 85],
                ['skill' => 'Docker', 'priority' => 'medium', 'market_demand' => 75],
                ['skill' => 'AWS', 'priority' => 'high', 'market_demand' => 90],
            ],
            'matching_skills' => ['PHP', 'Laravel', 'MySQL'],
            'recommendation' => 'Focus on cloud technologies',
        ], $overrides);

        return $this->mockAIJSON($response);
    }

    /**
     * Mock interview question generation.
     *
     * @param int $count
     * @return MockInterface
     */
    protected function mockInterviewQuestions(int $count = 5): MockInterface
    {
        $questions = [];

        for ($i = 1; $i <= $count; $i++) {
            $questions[] = [
                'question' => "Mock interview question #{$i}",
                'type' => ['behavioral', 'technical', 'situational'][($i - 1) % 3],
                'difficulty' => ['easy', 'medium', 'hard'][($i - 1) % 3],
                'expected_answer_points' => ['Point A', 'Point B', 'Point C'],
            ];
        }

        return $this->mockAIJSON(['questions' => $questions]);
    }

    /**
     * Mock job matching score calculation.
     *
     * @param int $score
     * @param array $breakdown
     * @return MockInterface
     */
    protected function mockJobMatchScore(int $score = 85, array $breakdown = []): MockInterface
    {
        $response = [
            'overall_score' => $score,
            'breakdown' => array_merge([
                'skills_match' => 80,
                'experience_match' => 90,
                'education_match' => 85,
                'location_match' => 100,
            ], $breakdown),
            'missing_skills' => ['Kubernetes', 'GraphQL'],
            'strengths' => ['Strong PHP background', 'Relevant experience'],
        ];

        return $this->mockAIJSON($response);
    }

    /**
     * Mock cover letter generation.
     *
     * @param string|null $content
     * @return MockInterface
     */
    protected function mockCoverLetter(?string $content = null): MockInterface
    {
        $content = $content ?? "Dear Hiring Manager,\n\nI am writing to express my interest in the position at your company. With my extensive experience in software development...\n\nSincerely,\nTest User";

        return $this->mockAIResponse($content);
    }

    /**
     * Mock negotiation strategy generation.
     *
     * @param array $overrides
     * @return MockInterface
     */
    protected function mockNegotiationStrategy(array $overrides = []): MockInterface
    {
        $response = array_merge([
            'recommended_salary_range' => ['min' => 80000, 'max' => 100000],
            'market_rate' => 90000,
            'negotiation_points' => ['Experience level', 'Market demand', 'Unique skills'],
            'tactics' => ['Start high', 'Emphasize value', 'Consider total compensation'],
            'confidence_level' => 'high',
        ], $overrides);

        return $this->mockAIJSON($response);
    }
}
