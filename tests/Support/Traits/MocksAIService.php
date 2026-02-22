<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use App\Services\AI\AIService;
use App\Services\AI\EmbeddingService;
use App\Services\AI\PromptRegistryService;
use Mockery;
use Mockery\MockInterface;

/**
 * Mocks AI Service Trait
 *
 * Provides helper methods for mocking AI services in tests.
 * Prevents actual API calls to Azure OpenAI during testing.
 *
 * Usage:
 *   class MyTest extends TestCase
 *   {
 *       use MocksAIService;
 *
 *       public function test_something(): void
 *       {
 *           $this->mockAI();
 *           // or with custom responses
 *           $this->mockAIWithResponse('Custom response');
 *       }
 *   }
 */
trait MocksAIService
{
    /**
     * Mock AI Service with default responses.
     */
    protected function mockAI(): MockInterface
    {
        return $this->mock(AIService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateText')
                ->andReturn('This is a mocked AI response.')
                ->byDefault();

            $mock->shouldReceive('generateJSON')
                ->andReturn([
                    'mocked' => true,
                    'data' => [],
                ])
                ->byDefault();

            $mock->shouldReceive('analyze')
                ->andReturn([
                    'analysis' => 'Mocked analysis result',
                    'score' => 85,
                ])
                ->byDefault();

            $mock->shouldReceive('summarize')
                ->andReturn('This is a mocked summary.')
                ->byDefault();

            $mock->shouldReceive('isAvailable')
                ->andReturn(true)
                ->byDefault();
        });
    }

    /**
     * Mock AI Service with a specific text response.
     */
    protected function mockAIWithResponse(string $response): MockInterface
    {
        return $this->mock(AIService::class, function (MockInterface $mock) use ($response) {
            $mock->shouldReceive('generateText')
                ->andReturn($response);

            $mock->shouldReceive('isAvailable')
                ->andReturn(true);
        });
    }

    /**
     * Mock AI Service with a specific JSON response.
     */
    protected function mockAIWithJSON(array $json): MockInterface
    {
        return $this->mock(AIService::class, function (MockInterface $mock) use ($json) {
            $mock->shouldReceive('generateJSON')
                ->andReturn($json);

            $mock->shouldReceive('generateText')
                ->andReturn(json_encode($json));

            $mock->shouldReceive('isAvailable')
                ->andReturn(true);
        });
    }

    /**
     * Mock AI Service to simulate failure.
     */
    protected function mockAIFailure(string $errorMessage = 'AI service unavailable'): MockInterface
    {
        return $this->mock(AIService::class, function (MockInterface $mock) use ($errorMessage) {
            $mock->shouldReceive('generateText')
                ->andThrow(new \RuntimeException($errorMessage));

            $mock->shouldReceive('generateJSON')
                ->andThrow(new \RuntimeException($errorMessage));

            $mock->shouldReceive('isAvailable')
                ->andReturn(false);
        });
    }

    /**
     * Mock Embedding Service with default responses.
     */
    protected function mockEmbeddings(): MockInterface
    {
        return $this->mock(EmbeddingService::class, function (MockInterface $mock) {
            // Return consistent zero-filled embeddings for predictable tests
            $mock->shouldReceive('generate')
                ->andReturn(array_fill(0, 1536, 0.1))
                ->byDefault();

            $mock->shouldReceive('generateBatch')
                ->andReturnUsing(function (array $texts) {
                    return array_map(
                        fn() => array_fill(0, 1536, 0.1),
                        $texts
                    );
                })
                ->byDefault();

            $mock->shouldReceive('cosineSimilarity')
                ->andReturn(0.85)
                ->byDefault();

            $mock->shouldReceive('isAvailable')
                ->andReturn(true)
                ->byDefault();
        });
    }

    /**
     * Mock Embedding Service with specific similarity score.
     */
    protected function mockEmbeddingsWithSimilarity(float $similarity): MockInterface
    {
        return $this->mock(EmbeddingService::class, function (MockInterface $mock) use ($similarity) {
            $mock->shouldReceive('generate')
                ->andReturn(array_fill(0, 1536, 0.1));

            $mock->shouldReceive('cosineSimilarity')
                ->andReturn($similarity);

            $mock->shouldReceive('isAvailable')
                ->andReturn(true);
        });
    }

    /**
     * Mock Prompt Registry Service.
     */
    protected function mockPromptRegistry(): MockInterface
    {
        return $this->mock(PromptRegistryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->andReturn(null)
                ->byDefault();

            $mock->shouldReceive('render')
                ->andReturnUsing(function (string $name, array $variables = []) {
                    return "Rendered prompt: {$name}";
                })
                ->byDefault();

            $mock->shouldReceive('getSystemPrompt')
                ->andReturn('You are a helpful assistant.')
                ->byDefault();

            $mock->shouldReceive('getConfig')
                ->andReturn([
                    'model' => 'gpt-5.1',
                    'max_tokens' => 2000,
                    'temperature' => 0.7,
                ])
                ->byDefault();

            $mock->shouldReceive('recordUsage')
                ->andReturn(null)
                ->byDefault();
        });
    }

    /**
     * Mock all AI services at once.
     */
    protected function mockAllAIServices(): void
    {
        $this->mockAI();
        $this->mockEmbeddings();
        $this->mockPromptRegistry();
    }

    /**
     * Mock AI service with specific expectations for resume analysis.
     */
    protected function mockResumeAnalysis(array $skills = ['PHP', 'Laravel'], int $experienceYears = 5): MockInterface
    {
        return $this->mockAIWithJSON([
            'skills' => $skills,
            'experience_years' => $experienceYears,
            'education' => 'Bachelor\'s Degree',
            'achievements' => ['Led team projects', 'Improved performance by 50%'],
            'ats_score' => 85,
            'recommendations' => ['Add more quantifiable achievements'],
        ]);
    }

    /**
     * Mock AI service with specific expectations for interview questions.
     */
    protected function mockInterviewQuestions(int $count = 5): MockInterface
    {
        $questions = [];
        for ($i = 1; $i <= $count; $i++) {
            $questions[] = [
                'id' => $i,
                'question' => "Interview question {$i}?",
                'type' => $i % 2 === 0 ? 'behavioral' : 'technical',
                'difficulty' => 'medium',
                'evaluation_criteria' => ['clarity', 'relevance', 'depth'],
            ];
        }

        return $this->mockAIWithJSON([
            'questions' => $questions,
            'total' => $count,
        ]);
    }

    /**
     * Mock AI service with specific expectations for skill gap analysis.
     */
    protected function mockSkillGapAnalysis(array $gaps = ['React', 'TypeScript']): MockInterface
    {
        return $this->mockAIWithJSON([
            'critical_gaps' => $gaps,
            'nice_to_have' => ['GraphQL', 'Docker'],
            'emerging_skills' => ['AI/ML', 'Rust'],
            'recommendations' => [
                [
                    'skill' => $gaps[0] ?? 'JavaScript',
                    'priority' => 'high',
                    'estimated_time' => '2-3 months',
                    'resources' => ['Online courses', 'Documentation'],
                ],
            ],
        ]);
    }

    /**
     * Mock AI service with specific expectations for job matching.
     */
    protected function mockJobMatching(float $score = 85.5): MockInterface
    {
        return $this->mockAIWithJSON([
            'overall_score' => $score,
            'skill_match' => $score - 5,
            'experience_match' => $score + 5,
            'education_match' => $score,
            'culture_fit' => $score - 10,
            'strengths' => ['Technical skills', 'Experience level'],
            'gaps' => ['Missing certification'],
            'recommendation' => $score >= 70 ? 'Good Match' : 'Partial Match',
        ]);
    }
}
