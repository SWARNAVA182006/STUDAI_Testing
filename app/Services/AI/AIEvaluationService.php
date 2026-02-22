<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIGoldenTest;
use App\Models\AIGoldenTestRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * AI Evaluation Service
 *
 * Evaluates AI outputs against golden test cases to detect quality regressions.
 * Supports multiple evaluation methods: similarity, exact match, keywords, JSON schema.
 *
 * Usage:
 *   $results = app(AIEvaluationService::class)->runAll();
 *   $result = app(AIEvaluationService::class)->runTest($goldenTest);
 */
class AIEvaluationService
{
    /**
     * AI Service for generating responses.
     */
    protected AIService $aiService;

    /**
     * Embedding service for similarity scoring.
     */
    protected EmbeddingService $embeddingService;

    /**
     * Prompt registry for prompt-based tests.
     */
    protected PromptRegistryService $promptRegistry;

    /**
     * Create a new AIEvaluationService instance.
     */
    public function __construct(
        AIService $aiService,
        EmbeddingService $embeddingService,
        PromptRegistryService $promptRegistry
    ) {
        $this->aiService = $aiService;
        $this->embeddingService = $embeddingService;
        $this->promptRegistry = $promptRegistry;
    }

    /**
     * Run all active golden tests.
     */
    public function runAll(?string $category = null): array
    {
        $query = AIGoldenTest::active();

        if ($category) {
            $query->category($category);
        }

        $tests = $query->get();
        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            $result = $this->runTest($test);
            $results[] = $result;

            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'pass_rate' => count($results) > 0 ? round(($passed / count($results)) * 100, 2) : 0,
            'results' => $results,
        ];
    }

    /**
     * Run a single golden test.
     */
    public function runTest(AIGoldenTest $test): array
    {
        $startTime = microtime(true);

        try {
            // Generate actual output
            $actualOutput = $this->generateOutput($test);

            $latencyMs = (microtime(true) - $startTime) * 1000;

            // Evaluate the output
            $evaluation = $this->evaluate($test, $actualOutput);

            // Record the run
            $run = $test->recordRun(
                $actualOutput,
                $evaluation['similarity_score'] ?? 0.0,
                $evaluation['passed'],
                $evaluation['details'],
                $latencyMs,
                config('ai.azure.models.chat', 'gpt-5.1')
            );

            Log::info('Golden test run completed', [
                'test' => $test->name,
                'passed' => $evaluation['passed'],
                'similarity' => $evaluation['similarity_score'] ?? null,
                'latency_ms' => $latencyMs,
            ]);

            return [
                'test_id' => $test->id,
                'test_name' => $test->name,
                'category' => $test->category,
                'passed' => $evaluation['passed'],
                'similarity_score' => $evaluation['similarity_score'] ?? null,
                'details' => $evaluation['details'],
                'latency_ms' => round($latencyMs, 2),
                'run_id' => $run->id,
            ];
        } catch (\Exception $e) {
            $latencyMs = (microtime(true) - $startTime) * 1000;

            Log::error('Golden test run failed', [
                'test' => $test->name,
                'error' => $e->getMessage(),
            ]);

            // Record failed run
            $test->recordRun(
                '',
                0.0,
                false,
                ['error' => $e->getMessage()],
                $latencyMs
            );

            return [
                'test_id' => $test->id,
                'test_name' => $test->name,
                'category' => $test->category,
                'passed' => false,
                'error' => $e->getMessage(),
                'latency_ms' => round($latencyMs, 2),
            ];
        }
    }

    /**
     * Generate output for a test.
     */
    protected function generateOutput(AIGoldenTest $test): string
    {
        // If test uses a registered prompt
        if ($test->prompt_name) {
            $renderedPrompt = $this->promptRegistry->render(
                $test->prompt_name,
                $test->input_variables ?? []
            );

            if (!$renderedPrompt) {
                throw new \RuntimeException("Prompt '{$test->prompt_name}' not found");
            }

            $systemPrompt = $this->promptRegistry->getSystemPrompt($test->prompt_name);

            return $this->aiService->generateText($renderedPrompt, $systemPrompt);
        }

        // Direct input test
        return $this->aiService->generateText($test->input);
    }

    /**
     * Evaluate output against test criteria.
     */
    protected function evaluate(AIGoldenTest $test, string $actualOutput): array
    {
        return match ($test->evaluation_type) {
            AIGoldenTest::EVAL_EXACT => $this->evaluateExact($test, $actualOutput),
            AIGoldenTest::EVAL_CONTAINS => $this->evaluateContains($test, $actualOutput),
            AIGoldenTest::EVAL_JSON_SCHEMA => $this->evaluateJsonSchema($test, $actualOutput),
            AIGoldenTest::EVAL_KEYWORDS => $this->evaluateKeywords($test, $actualOutput),
            AIGoldenTest::EVAL_COMPOSITE => $this->evaluateComposite($test, $actualOutput),
            default => $this->evaluateSimilarity($test, $actualOutput),
        };
    }

    /**
     * Evaluate using semantic similarity.
     */
    protected function evaluateSimilarity(AIGoldenTest $test, string $actualOutput): array
    {
        $expectedEmbedding = $this->embeddingService->generate($test->expected_output);
        $actualEmbedding = $this->embeddingService->generate($actualOutput);

        $similarity = $this->embeddingService->cosineSimilarity($expectedEmbedding, $actualEmbedding);
        $passed = $similarity >= $test->min_similarity_score;

        return [
            'passed' => $passed,
            'similarity_score' => round($similarity, 4),
            'details' => [
                'method' => 'similarity',
                'threshold' => $test->min_similarity_score,
                'actual_score' => round($similarity, 4),
            ],
        ];
    }

    /**
     * Evaluate using exact match.
     */
    protected function evaluateExact(AIGoldenTest $test, string $actualOutput): array
    {
        $normalizedExpected = $this->normalizeText($test->expected_output);
        $normalizedActual = $this->normalizeText($actualOutput);

        $passed = $normalizedExpected === $normalizedActual;

        return [
            'passed' => $passed,
            'details' => [
                'method' => 'exact',
                'match' => $passed,
            ],
        ];
    }

    /**
     * Evaluate using contains check.
     */
    protected function evaluateContains(AIGoldenTest $test, string $actualOutput): array
    {
        $normalizedActual = strtolower($actualOutput);
        $normalizedExpected = strtolower($test->expected_output);

        $passed = str_contains($normalizedActual, $normalizedExpected);

        return [
            'passed' => $passed,
            'details' => [
                'method' => 'contains',
                'found' => $passed,
            ],
        ];
    }

    /**
     * Evaluate JSON schema validation.
     */
    protected function evaluateJsonSchema(AIGoldenTest $test, string $actualOutput): array
    {
        try {
            $json = json_decode($actualOutput, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [
                'passed' => false,
                'details' => [
                    'method' => 'json_schema',
                    'error' => 'Invalid JSON: ' . $e->getMessage(),
                ],
            ];
        }

        $schema = $test->expected_json_schema ?? [];
        $errors = $this->validateJsonSchema($json, $schema);

        return [
            'passed' => empty($errors),
            'details' => [
                'method' => 'json_schema',
                'errors' => $errors,
            ],
        ];
    }

    /**
     * Simple JSON schema validator.
     */
    protected function validateJsonSchema(array $data, array $schema, string $path = ''): array
    {
        $errors = [];

        // Check required fields
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!array_key_exists($field, $data)) {
                    $errors[] = "Missing required field: {$path}{$field}";
                }
            }
        }

        // Check properties
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $field => $fieldSchema) {
                if (array_key_exists($field, $data)) {
                    $fieldPath = $path ? "{$path}.{$field}" : $field;

                    // Type check
                    if (isset($fieldSchema['type'])) {
                        $actualType = gettype($data[$field]);
                        $expectedType = $fieldSchema['type'];

                        $typeMap = [
                            'integer' => 'integer',
                            'string' => 'string',
                            'boolean' => 'boolean',
                            'array' => 'array',
                            'object' => 'array',
                            'number' => ['integer', 'double'],
                        ];

                        $expectedTypes = (array) ($typeMap[$expectedType] ?? $expectedType);

                        if (!in_array($actualType, $expectedTypes)) {
                            $errors[] = "Type mismatch at {$fieldPath}: expected {$expectedType}, got {$actualType}";
                        }
                    }

                    // Recursive validation for nested objects
                    if (isset($fieldSchema['properties']) && is_array($data[$field])) {
                        $nestedErrors = $this->validateJsonSchema($data[$field], $fieldSchema, $fieldPath);
                        $errors = array_merge($errors, $nestedErrors);
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Evaluate using keyword matching.
     */
    protected function evaluateKeywords(AIGoldenTest $test, string $actualOutput): array
    {
        $normalizedOutput = strtolower($actualOutput);
        $requiredKeywords = $test->required_keywords ?? [];
        $forbiddenKeywords = $test->forbidden_keywords ?? [];

        $foundRequired = [];
        $missingRequired = [];
        $foundForbidden = [];

        // Check required keywords
        foreach ($requiredKeywords as $keyword) {
            if (str_contains($normalizedOutput, strtolower($keyword))) {
                $foundRequired[] = $keyword;
            } else {
                $missingRequired[] = $keyword;
            }
        }

        // Check forbidden keywords
        foreach ($forbiddenKeywords as $keyword) {
            if (str_contains($normalizedOutput, strtolower($keyword))) {
                $foundForbidden[] = $keyword;
            }
        }

        $passed = empty($missingRequired) && empty($foundForbidden);

        return [
            'passed' => $passed,
            'details' => [
                'method' => 'keywords',
                'required_found' => $foundRequired,
                'required_missing' => $missingRequired,
                'forbidden_found' => $foundForbidden,
            ],
        ];
    }

    /**
     * Evaluate using composite method (all applicable methods).
     */
    protected function evaluateComposite(AIGoldenTest $test, string $actualOutput): array
    {
        $results = [];
        $allPassed = true;

        // Always run similarity
        $similarityResult = $this->evaluateSimilarity($test, $actualOutput);
        $results['similarity'] = $similarityResult;
        if (!$similarityResult['passed']) {
            $allPassed = false;
        }

        // Run keywords if configured
        if (!empty($test->required_keywords) || !empty($test->forbidden_keywords)) {
            $keywordsResult = $this->evaluateKeywords($test, $actualOutput);
            $results['keywords'] = $keywordsResult;
            if (!$keywordsResult['passed']) {
                $allPassed = false;
            }
        }

        // Run JSON schema if configured
        if (!empty($test->expected_json_schema)) {
            $schemaResult = $this->evaluateJsonSchema($test, $actualOutput);
            $results['json_schema'] = $schemaResult;
            if (!$schemaResult['passed']) {
                $allPassed = false;
            }
        }

        return [
            'passed' => $allPassed,
            'similarity_score' => $results['similarity']['similarity_score'] ?? null,
            'details' => [
                'method' => 'composite',
                'sub_results' => $results,
            ],
        ];
    }

    /**
     * Normalize text for comparison.
     */
    protected function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Get test statistics.
     */
    public function getStatistics(?string $category = null): array
    {
        $query = AIGoldenTest::query();

        if ($category) {
            $query->category($category);
        }

        $tests = $query->get();

        $totalTests = $tests->count();
        $activeTests = $tests->where('is_active', true)->count();
        $totalRuns = $tests->sum('run_count');
        $totalPasses = $tests->sum('pass_count');
        $totalFails = $tests->sum('fail_count');
        $currentlyFailing = $tests->where('last_run_status', 'failed')->count();

        $avgSimilarity = $tests->whereNotNull('avg_similarity_score')
            ->avg('avg_similarity_score');

        return [
            'total_tests' => $totalTests,
            'active_tests' => $activeTests,
            'total_runs' => $totalRuns,
            'total_passes' => $totalPasses,
            'total_fails' => $totalFails,
            'overall_pass_rate' => $totalRuns > 0 ? round(($totalPasses / $totalRuns) * 100, 2) : 0,
            'currently_failing' => $currentlyFailing,
            'avg_similarity' => $avgSimilarity ? round($avgSimilarity, 4) : null,
            'categories' => AIGoldenTest::getCategories(),
        ];
    }

    /**
     * Get failing tests.
     */
    public function getFailingTests(): Collection
    {
        return AIGoldenTest::active()
            ->where('last_run_status', 'failed')
            ->get();
    }

    /**
     * Seed default golden tests.
     */
    public function seedDefaults(): void
    {
        $defaults = [
            [
                'name' => 'resume_summary_quality',
                'category' => 'resume',
                'prompt_name' => 'resume_analysis',
                'input' => 'Test resume content',
                'input_variables' => [
                    'resume_content' => 'John Doe, Software Engineer with 5 years experience in Python, JavaScript, React. Worked at Google and Meta.',
                    'target_job_title' => 'Senior Software Engineer',
                ],
                'expected_output' => 'Skills: Python, JavaScript, React. Experience: 5 years. Companies: Google, Meta.',
                'required_keywords' => ['Python', 'JavaScript', 'experience'],
                'min_similarity_score' => 0.6,
                'evaluation_type' => AIGoldenTest::EVAL_COMPOSITE,
                'description' => 'Validates resume analysis produces coherent skill extraction',
            ],
            [
                'name' => 'interview_question_relevance',
                'category' => 'interview',
                'input' => 'Generate 3 behavioral interview questions for a Senior Software Engineer position focusing on leadership.',
                'expected_output' => 'Questions should focus on leadership, team management, and technical decision making.',
                'required_keywords' => ['team', 'leadership', 'decision'],
                'forbidden_keywords' => ['unrelated', 'salary'],
                'min_similarity_score' => 0.5,
                'evaluation_type' => AIGoldenTest::EVAL_KEYWORDS,
                'description' => 'Ensures interview questions are relevant to the role',
            ],
            [
                'name' => 'skill_gap_json_format',
                'category' => 'skills',
                'prompt_name' => 'skill_gap_analysis',
                'input_variables' => [
                    'current_skills' => 'Python, SQL, Git',
                    'target_role' => 'Data Scientist',
                    'industry' => 'Technology',
                    'experience_years' => '3',
                    'career_goals' => 'Become a senior data scientist',
                ],
                'expected_output' => '{}',
                'expected_json_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'critical_gaps' => ['type' => 'array'],
                        'nice_to_have' => ['type' => 'array'],
                        'recommendations' => ['type' => 'array'],
                    ],
                ],
                'min_similarity_score' => 0.5,
                'evaluation_type' => AIGoldenTest::EVAL_JSON_SCHEMA,
                'description' => 'Validates skill gap analysis returns proper JSON structure',
            ],
        ];

        foreach ($defaults as $testData) {
            AIGoldenTest::firstOrCreate(
                ['name' => $testData['name']],
                $testData
            );
        }

        Log::info('Default golden tests seeded', ['count' => count($defaults)]);
    }
}
