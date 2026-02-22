<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Services\AI\AIService;
use Mockery;
use Mockery\MockInterface;

/**
 * Trait AIServiceMock
 *
 * Provides deterministic mocking of the AIService for unit and feature tests.
 * Eliminates real API calls to Azure OpenAI / Azure Anthropic, ensuring
 * reproducible results and zero external dependencies during test runs.
 *
 * This trait is designed around the public API of App\Services\AI\AIService:
 *   - generateText(string $prompt, ?string $systemPrompt, array $options): string
 *   - generateJSON(string $prompt, ?string $systemPrompt, array $options): array
 *   - generateEmbeddings(string $text): array
 *   - forUser(User $user): self
 *
 * It also supports mocking higher-level AI-dependent operations (interview
 * questions, resume analysis, cover letters, etc.) that are typically
 * orchestrated by domain services built on top of AIService.
 *
 * Usage:
 *   use Tests\Traits\AIServiceMock;
 *
 *   class MyFeatureTest extends TestCase
 *   {
 *       use AIServiceMock;
 *
 *       public function test_example(): void
 *       {
 *           $mock = $this->mockAIService();
 *           // All AI calls now return sensible defaults.
 *
 *           // Override a specific method:
 *           $this->mockAITextGeneration('Custom AI output');
 *
 *           // Simulate a failure:
 *           $this->mockAIFailure('generateText', 'Rate limit exceeded');
 *
 *           // Assert a method was called with expected args:
 *           $this->assertAICalledWith('generateText', ['expected prompt']);
 *       }
 *   }
 *
 * @see \App\Services\AI\AIService
 */
trait AIServiceMock
{
    /**
     * The Mockery mock instance for AIService.
     */
    protected MockInterface $aiServiceMock;

    /**
     * Registry of recorded method invocations for assertion support.
     *
     * @var array<string, list<array{args: array, response: mixed}>>
     */
    protected array $aiCallLog = [];

    // ------------------------------------------------------------------
    // Core mock setup
    // ------------------------------------------------------------------

    /**
     * Create a fully configured AIService mock and bind it into the
     * Laravel service container, replacing any real instance.
     *
     * Every public method on AIService is stubbed with a sensible default
     * response drawn from {@see getDefaultMockResponses()}. All stubs also
     * record their invocations into `$this->aiCallLog` so that assertions
     * can be made after the system-under-test has run.
     *
     * @return MockInterface The configured AIService mock.
     */
    protected function mockAIService(): MockInterface
    {
        $this->aiCallLog = [];
        $defaults = $this->getDefaultMockResponses();

        $this->aiServiceMock = Mockery::mock(AIService::class);

        // forUser() returns self for fluent chaining
        $this->aiServiceMock
            ->shouldReceive('forUser')
            ->byDefault()
            ->andReturnUsing(function () {
                $this->recordCall('forUser', func_get_args());
                return $this->aiServiceMock;
            });

        // generateText
        $this->aiServiceMock
            ->shouldReceive('generateText')
            ->byDefault()
            ->andReturnUsing(function () use ($defaults) {
                $this->recordCall('generateText', func_get_args());
                return $defaults['generateText'];
            });

        // generateJSON
        $this->aiServiceMock
            ->shouldReceive('generateJSON')
            ->byDefault()
            ->andReturnUsing(function () use ($defaults) {
                $this->recordCall('generateJSON', func_get_args());
                return $defaults['analyze'];
            });

        // generateEmbeddings
        $this->aiServiceMock
            ->shouldReceive('generateEmbeddings')
            ->byDefault()
            ->andReturnUsing(function () use ($defaults) {
                $this->recordCall('generateEmbeddings', func_get_args());
                return $this->buildDeterministicEmbedding();
            });

        // Bind into the container so dependency injection resolves the mock
        $this->app->instance(AIService::class, $this->aiServiceMock);

        return $this->aiServiceMock;
    }

    // ------------------------------------------------------------------
    // Method-specific response overrides
    // ------------------------------------------------------------------

    /**
     * Override the response for a specific AIService method.
     *
     * If the mock has not yet been created, it will be initialised first
     * with all default responses before the override is applied.
     *
     * @param string $method   The public method name (e.g. 'generateText').
     * @param mixed  $response The value the method should return.
     *
     * @return MockInterface
     */
    protected function mockAIResponse(string $method, mixed $response): MockInterface
    {
        if (!isset($this->aiServiceMock)) {
            $this->mockAIService();
        }

        $this->aiServiceMock
            ->shouldReceive($method)
            ->andReturnUsing(function () use ($method, $response) {
                $this->recordCall($method, func_get_args());
                return $response;
            });

        return $this->aiServiceMock;
    }

    /**
     * Quick helper to mock generateText() with a specific string response.
     *
     * @param string $response The text the AI should "return".
     *
     * @return MockInterface
     */
    protected function mockAITextGeneration(string $response): MockInterface
    {
        return $this->mockAIResponse('generateText', $response);
    }

    /**
     * Quick helper to mock generateJSON() with a structured analysis array.
     *
     * The provided array is returned verbatim from generateJSON().
     *
     * @param array $analysis The analysis payload.
     *
     * @return MockInterface
     */
    protected function mockAIAnalysis(array $analysis): MockInterface
    {
        return $this->mockAIResponse('generateJSON', $analysis);
    }

    // ------------------------------------------------------------------
    // Failure simulation
    // ------------------------------------------------------------------

    /**
     * Configure a specific AIService method to throw an exception,
     * simulating an AI provider failure (rate-limit, timeout, etc.).
     *
     * If the mock has not yet been created, it will be initialised first.
     *
     * @param string $method       The method that should fail.
     * @param string $errorMessage The exception message.
     * @param string $exceptionClass FQCN of the exception to throw.
     *
     * @return MockInterface
     */
    protected function mockAIFailure(
        string $method,
        string $errorMessage = 'AI service unavailable',
        string $exceptionClass = \Exception::class
    ): MockInterface {
        if (!isset($this->aiServiceMock)) {
            $this->mockAIService();
        }

        $this->aiServiceMock
            ->shouldReceive($method)
            ->andThrow(new $exceptionClass($errorMessage));

        return $this->aiServiceMock;
    }

    // ------------------------------------------------------------------
    // Assertions
    // ------------------------------------------------------------------

    /**
     * Assert that a specific AIService method was called and that
     * the provided arguments are a subset of the actual call arguments.
     *
     * Each entry in `$expectedArgs` is compared positionally against the
     * recorded invocation. String arguments use `str_contains` for
     * flexible partial matching (useful for prompts), while all other
     * types are compared with strict equality.
     *
     * @param string $method       The method name.
     * @param array  $expectedArgs Positional expected argument values.
     *
     * @return void
     *
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    protected function assertAICalledWith(string $method, array $expectedArgs = []): void
    {
        $this->assertTrue(
            isset($this->aiCallLog[$method]) && count($this->aiCallLog[$method]) > 0,
            "Expected AIService::{$method}() to have been called, but it was not."
        );

        if (empty($expectedArgs)) {
            return;
        }

        $matched = false;

        foreach ($this->aiCallLog[$method] as $invocation) {
            $args = $invocation['args'];
            $allMatch = true;

            foreach ($expectedArgs as $index => $expected) {
                if (!array_key_exists($index, $args)) {
                    $allMatch = false;
                    break;
                }

                $actual = $args[$index];

                if (is_string($expected) && is_string($actual)) {
                    // Partial string match for prompt flexibility
                    if (!str_contains($actual, $expected)) {
                        $allMatch = false;
                        break;
                    }
                } elseif ($expected !== $actual) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                $matched = true;
                break;
            }
        }

        $this->assertTrue(
            $matched,
            sprintf(
                "AIService::%s() was called %d time(s), but none of the invocations matched the expected arguments.\nExpected: %s\nRecorded calls: %s",
                $method,
                count($this->aiCallLog[$method]),
                json_encode($expectedArgs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                json_encode(
                    array_map(fn (array $inv) => $inv['args'], $this->aiCallLog[$method]),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            )
        );
    }

    /**
     * Assert that a specific AIService method was called at least once.
     *
     * @param string $method The method name.
     *
     * @return void
     */
    protected function assertAICalled(string $method): void
    {
        $this->assertAICalledWith($method);
    }

    /**
     * Assert that a specific AIService method was never called.
     *
     * @param string $method The method name.
     *
     * @return void
     */
    protected function assertAINotCalled(string $method): void
    {
        $callCount = isset($this->aiCallLog[$method])
            ? count($this->aiCallLog[$method])
            : 0;

        $this->assertSame(
            0,
            $callCount,
            "Expected AIService::{$method}() not to have been called, but it was called {$callCount} time(s)."
        );
    }

    /**
     * Assert that a specific AIService method was called exactly N times.
     *
     * @param string $method        The method name.
     * @param int    $expectedCount The expected invocation count.
     *
     * @return void
     */
    protected function assertAICallCount(string $method, int $expectedCount): void
    {
        $actualCount = isset($this->aiCallLog[$method])
            ? count($this->aiCallLog[$method])
            : 0;

        $this->assertSame(
            $expectedCount,
            $actualCount,
            "Expected AIService::{$method}() to have been called {$expectedCount} time(s), but it was called {$actualCount} time(s)."
        );
    }

    // ------------------------------------------------------------------
    // Default mock responses
    // ------------------------------------------------------------------

    /**
     * Return a comprehensive set of sensible default responses keyed by
     * logical operation name.
     *
     * These defaults are intentionally deterministic and realistic enough
     * to pass through downstream validation logic without modification.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultMockResponses(): array
    {
        return [
            // ---------------------------------------------------------
            // generateText default
            // ---------------------------------------------------------
            'generateText' => 'With over five years of progressive experience in software engineering, '
                . 'the candidate demonstrates strong proficiency in full-stack development, '
                . 'cloud architecture, and agile methodologies. Their track record of '
                . 'delivering scalable solutions and mentoring junior developers positions '
                . 'them as a valuable asset for teams seeking technical leadership.',

            // ---------------------------------------------------------
            // analyze / generateJSON default
            // ---------------------------------------------------------
            'analyze' => [
                'overall_score' => 82,
                'confidence' => 0.91,
                'summary' => 'Strong candidate profile with solid technical foundation and growth trajectory.',
                'scores' => [
                    'technical_skills' => 85,
                    'communication' => 78,
                    'leadership' => 72,
                    'problem_solving' => 88,
                    'cultural_fit' => 80,
                ],
                'strengths' => [
                    'Deep expertise in backend technologies (PHP, Laravel, MySQL)',
                    'Proven track record of delivering production-grade applications',
                    'Strong analytical and problem-solving abilities',
                ],
                'areas_for_improvement' => [
                    'Expand frontend framework experience beyond Alpine.js',
                    'Gain hands-on experience with container orchestration (Kubernetes)',
                    'Pursue cloud certification (AWS/Azure)',
                ],
                'recommendations' => [
                    'Enroll in a cloud architecture certification programme',
                    'Contribute to open-source projects to broaden visibility',
                    'Seek cross-functional project leadership opportunities',
                ],
            ],

            // ---------------------------------------------------------
            // generateInterviewQuestions
            // ---------------------------------------------------------
            'generateInterviewQuestions' => [
                [
                    'id' => 1,
                    'question' => 'Describe a time when you had to refactor a critical system under tight deadlines. How did you balance quality with speed?',
                    'type' => 'behavioral',
                    'difficulty' => 'medium',
                    'competency' => 'problem_solving',
                    'expected_points' => [
                        'Identified the scope and risk of the refactor',
                        'Communicated trade-offs to stakeholders',
                        'Implemented incremental changes with test coverage',
                        'Delivered on time without sacrificing stability',
                    ],
                    'follow_up' => 'What metrics did you use to validate the refactor was successful?',
                ],
                [
                    'id' => 2,
                    'question' => 'How would you design a job matching algorithm that balances relevance with fairness across diverse candidate pools?',
                    'type' => 'technical',
                    'difficulty' => 'hard',
                    'competency' => 'system_design',
                    'expected_points' => [
                        'Define relevance scoring criteria (skills, experience, location)',
                        'Incorporate bias mitigation strategies',
                        'Use embedding-based semantic similarity',
                        'Implement feedback loops for continuous improvement',
                    ],
                    'follow_up' => 'How would you measure fairness in the algorithm output?',
                ],
                [
                    'id' => 3,
                    'question' => 'Tell me about a project where you introduced a new technology to your team. What resistance did you face and how did you overcome it?',
                    'type' => 'behavioral',
                    'difficulty' => 'medium',
                    'competency' => 'leadership',
                    'expected_points' => [
                        'Researched and presented a business case',
                        'Addressed team concerns with a proof of concept',
                        'Provided training and documentation',
                        'Measured adoption and impact',
                    ],
                    'follow_up' => 'Would you take the same approach again? Why or why not?',
                ],
                [
                    'id' => 4,
                    'question' => 'Walk me through how you would troubleshoot a production performance issue in a Laravel application serving 10,000 concurrent users.',
                    'type' => 'technical',
                    'difficulty' => 'hard',
                    'competency' => 'technical_skills',
                    'expected_points' => [
                        'Check application logs and error monitoring (e.g. Sentry)',
                        'Profile database queries for N+1 and slow queries',
                        'Analyse Redis cache hit rates and queue backlogs',
                        'Review infrastructure metrics (CPU, memory, network)',
                    ],
                    'follow_up' => 'How would you prevent this class of issue from recurring?',
                ],
                [
                    'id' => 5,
                    'question' => 'Describe a situation where you received critical feedback. How did you respond and what changed as a result?',
                    'type' => 'situational',
                    'difficulty' => 'easy',
                    'competency' => 'communication',
                    'expected_points' => [
                        'Listened actively without becoming defensive',
                        'Asked clarifying questions',
                        'Created an action plan for improvement',
                        'Followed up with the feedback provider',
                    ],
                    'follow_up' => 'How do you proactively seek feedback now?',
                ],
            ],

            // ---------------------------------------------------------
            // evaluateAnswer
            // ---------------------------------------------------------
            'evaluateAnswer' => [
                'score' => 76,
                'max_score' => 100,
                'grade' => 'B+',
                'feedback' => 'Your answer demonstrated solid understanding of the core concepts '
                    . 'and included a relevant real-world example. To strengthen your response, '
                    . 'consider quantifying your impact with specific metrics and structuring '
                    . 'your narrative using the STAR method for greater clarity.',
                'strengths' => [
                    'Clear articulation of the problem context',
                    'Demonstrated ownership and initiative',
                    'Good use of a concrete example',
                ],
                'improvements' => [
                    'Include measurable outcomes (e.g. percentage improvement, time saved)',
                    'Structure the response with Situation, Task, Action, Result',
                    'Address what you would do differently in hindsight',
                ],
                'keyword_coverage' => [
                    'covered' => ['scalability', 'collaboration', 'testing'],
                    'missing' => ['metrics', 'stakeholder communication', 'iteration'],
                ],
            ],

            // ---------------------------------------------------------
            // generateCoverLetter
            // ---------------------------------------------------------
            'generateCoverLetter' => "Dear Hiring Manager,\n\n"
                . "I am writing to express my enthusiastic interest in the Software Engineer "
                . "position at your organisation. With over five years of experience building "
                . "scalable web applications using PHP, Laravel, and modern frontend technologies, "
                . "I am confident in my ability to contribute meaningfully to your engineering team.\n\n"
                . "In my current role, I led the development of a high-traffic SaaS platform "
                . "serving over 50,000 users, where I reduced API response times by 40% through "
                . "strategic caching and query optimisation. I thrive in collaborative environments "
                . "and am passionate about writing clean, maintainable code that delivers real "
                . "business value.\n\n"
                . "I would welcome the opportunity to discuss how my background aligns with your "
                . "team's goals. Thank you for considering my application.\n\n"
                . "Warm regards,\n"
                . "Test User",

            // ---------------------------------------------------------
            // analyzeResume
            // ---------------------------------------------------------
            'analyzeResume' => [
                'overall_score' => 78,
                'ats_compatibility' => 82,
                'sections' => [
                    'contact_information' => ['present' => true, 'score' => 95],
                    'professional_summary' => ['present' => true, 'score' => 72],
                    'work_experience' => ['present' => true, 'score' => 80],
                    'education' => ['present' => true, 'score' => 85],
                    'skills' => ['present' => true, 'score' => 75],
                    'certifications' => ['present' => false, 'score' => 0],
                ],
                'extracted_skills' => [
                    'technical' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'JavaScript', 'Vue.js', 'Git'],
                    'soft' => ['Team Leadership', 'Communication', 'Problem Solving', 'Mentoring'],
                ],
                'experience_years' => 5,
                'education_level' => 'bachelor',
                'keyword_density' => [
                    'matched' => ['PHP', 'Laravel', 'REST API', 'Agile', 'MySQL'],
                    'missing' => ['Docker', 'Kubernetes', 'CI/CD', 'TypeScript'],
                    'coverage_percentage' => 68,
                ],
                'formatting' => [
                    'length_pages' => 2,
                    'has_action_verbs' => true,
                    'has_quantified_achievements' => false,
                    'consistent_formatting' => true,
                ],
                'recommendations' => [
                    'Add quantified achievements to each work experience entry',
                    'Include relevant certifications section',
                    'Add missing keywords: Docker, CI/CD, TypeScript',
                    'Strengthen professional summary with measurable impact',
                ],
            ],

            // ---------------------------------------------------------
            // calculateMatchScore
            // ---------------------------------------------------------
            'calculateMatchScore' => [
                'overall_score' => 74,
                'breakdown' => [
                    'skills_match' => 80,
                    'experience_match' => 75,
                    'education_match' => 85,
                    'location_match' => 60,
                    'salary_match' => 70,
                    'culture_fit' => 78,
                ],
                'matched_skills' => ['PHP', 'Laravel', 'MySQL', 'REST API', 'Git'],
                'missing_skills' => ['TypeScript', 'Docker', 'AWS'],
                'transferable_skills' => ['Redis', 'Vue.js', 'Agile'],
                'recommendation' => 'Good fit for the role. Upskilling in cloud technologies would strengthen the match.',
                'apply_suggestion' => true,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Domain-specific mock helpers
    // ------------------------------------------------------------------

    /**
     * Configure the mock to return default interview question data when
     * generateJSON() is called, using the 'generateInterviewQuestions'
     * default response.
     *
     * @param int $count Number of questions to include (max 5 from defaults).
     *
     * @return MockInterface
     */
    protected function mockInterviewQuestionGeneration(int $count = 5): MockInterface
    {
        $defaults = $this->getDefaultMockResponses();
        $questions = array_slice($defaults['generateInterviewQuestions'], 0, $count);

        return $this->mockAIResponse('generateJSON', [
            'questions' => $questions,
            'total' => count($questions),
            'session_type' => 'mock_interview',
        ]);
    }

    /**
     * Configure the mock to return answer evaluation data.
     *
     * @param int        $score     The score to return (0-100).
     * @param array|null $overrides Optional overrides merged into the default.
     *
     * @return MockInterface
     */
    protected function mockAnswerEvaluation(int $score = 76, ?array $overrides = null): MockInterface
    {
        $defaults = $this->getDefaultMockResponses();
        $evaluation = $defaults['evaluateAnswer'];
        $evaluation['score'] = $score;

        if ($overrides !== null) {
            $evaluation = array_merge($evaluation, $overrides);
        }

        return $this->mockAIResponse('generateJSON', $evaluation);
    }

    /**
     * Configure the mock to return cover letter text.
     *
     * @param string|null $letterText Custom letter text, or null for default.
     *
     * @return MockInterface
     */
    protected function mockCoverLetterGeneration(?string $letterText = null): MockInterface
    {
        $defaults = $this->getDefaultMockResponses();
        $text = $letterText ?? $defaults['generateCoverLetter'];

        return $this->mockAITextGeneration($text);
    }

    /**
     * Configure the mock to return resume analysis data.
     *
     * @param array $overrides Optional overrides merged into the default.
     *
     * @return MockInterface
     */
    protected function mockResumeAnalysis(array $overrides = []): MockInterface
    {
        $defaults = $this->getDefaultMockResponses();
        $analysis = array_merge($defaults['analyzeResume'], $overrides);

        return $this->mockAIResponse('generateJSON', $analysis);
    }

    /**
     * Configure the mock to return a job match score.
     *
     * @param int   $score     The overall match score (0-100).
     * @param array $overrides Optional overrides merged into the breakdown.
     *
     * @return MockInterface
     */
    protected function mockMatchScoreCalculation(int $score = 74, array $overrides = []): MockInterface
    {
        $defaults = $this->getDefaultMockResponses();
        $matchData = $defaults['calculateMatchScore'];
        $matchData['overall_score'] = $score;

        if (!empty($overrides)) {
            $matchData = array_merge($matchData, $overrides);
        }

        return $this->mockAIResponse('generateJSON', $matchData);
    }

    // ------------------------------------------------------------------
    // Sequential response support
    // ------------------------------------------------------------------

    /**
     * Configure a method to return different responses on consecutive calls.
     *
     * This is useful for testing retry logic or multi-step workflows where
     * the same method is called multiple times with varying expectations.
     *
     * @param string  $method    The method name.
     * @param mixed[] $responses Ordered list of return values.
     *
     * @return MockInterface
     */
    protected function mockAISequentialResponses(string $method, array $responses): MockInterface
    {
        if (!isset($this->aiServiceMock)) {
            $this->mockAIService();
        }

        $callIndex = 0;

        $this->aiServiceMock
            ->shouldReceive($method)
            ->andReturnUsing(function () use ($method, $responses, &$callIndex) {
                $this->recordCall($method, func_get_args());
                $response = $responses[$callIndex] ?? end($responses);
                $callIndex++;
                return $response;
            });

        return $this->aiServiceMock;
    }

    // ------------------------------------------------------------------
    // Introspection
    // ------------------------------------------------------------------

    /**
     * Retrieve the underlying Mockery mock instance for advanced
     * configuration not covered by the convenience methods.
     *
     * If the mock has not been created yet, it will be initialised
     * with default responses.
     *
     * @return MockInterface
     */
    protected function getAIServiceMock(): MockInterface
    {
        if (!isset($this->aiServiceMock)) {
            $this->mockAIService();
        }

        return $this->aiServiceMock;
    }

    /**
     * Return the full call log for inspection or custom assertions.
     *
     * @return array<string, list<array{args: array, response: mixed}>>
     */
    protected function getAICallLog(): array
    {
        return $this->aiCallLog;
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Record a method call in the internal call log.
     *
     * @param string $method The method name.
     * @param array  $args   The arguments passed to the method.
     *
     * @return void
     */
    private function recordCall(string $method, array $args): void
    {
        if (!isset($this->aiCallLog[$method])) {
            $this->aiCallLog[$method] = [];
        }

        $this->aiCallLog[$method][] = [
            'args' => $args,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Build a deterministic 1536-dimensional embedding vector suitable
     * for OpenAI text-embedding-ada-002 compatibility.
     *
     * Values are normalised between -1.0 and 1.0 with a fixed seed so
     * that tests produce identical results across runs.
     *
     * @param int $seed Random seed for reproducibility.
     *
     * @return float[]
     */
    private function buildDeterministicEmbedding(int $seed = 42): array
    {
        $embedding = [];
        mt_srand($seed);

        for ($i = 0; $i < 1536; $i++) {
            $embedding[] = round((mt_rand() / mt_getrandmax()) * 2 - 1, 8);
        }

        // Reset the random seed to avoid polluting other tests
        mt_srand();

        return $embedding;
    }
}
