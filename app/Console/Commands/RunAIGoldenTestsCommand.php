<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AIGoldenTest;
use App\Services\AI\AIEvaluationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Run AI Golden Tests Command
 *
 * Executes golden tests to detect AI quality regressions.
 *
 * Usage:
 *   php artisan ai:golden-tests                    Run all active tests
 *   php artisan ai:golden-tests --category=resume  Run tests in category
 *   php artisan ai:golden-tests --test=test_name   Run specific test
 *   php artisan ai:golden-tests --seed             Seed default tests
 *   php artisan ai:golden-tests --stats            Show statistics
 *   php artisan ai:golden-tests --failing          Show failing tests
 */
class RunAIGoldenTestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:golden-tests
                            {--category= : Run tests in specific category}
                            {--test= : Run specific test by name}
                            {--seed : Seed default golden tests}
                            {--stats : Show test statistics}
                            {--failing : Show currently failing tests}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run AI golden tests to detect quality regressions';

    /**
     * Execute the console command.
     */
    public function handle(AIEvaluationService $evaluationService): int
    {
        // Handle seed option
        if ($this->option('seed')) {
            return $this->seedTests($evaluationService);
        }

        // Handle stats option
        if ($this->option('stats')) {
            return $this->showStats($evaluationService);
        }

        // Handle failing option
        if ($this->option('failing')) {
            return $this->showFailing($evaluationService);
        }

        // Handle specific test
        if ($testName = $this->option('test')) {
            return $this->runSpecificTest($evaluationService, $testName);
        }

        // Run all tests (optionally filtered by category)
        return $this->runAllTests($evaluationService, $this->option('category'));
    }

    /**
     * Seed default golden tests.
     */
    protected function seedTests(AIEvaluationService $evaluationService): int
    {
        $this->info('Seeding default golden tests...');

        $evaluationService->seedDefaults();

        $count = AIGoldenTest::count();
        $this->info("✓ Golden tests seeded. Total tests: {$count}");

        return self::SUCCESS;
    }

    /**
     * Show test statistics.
     */
    protected function showStats(AIEvaluationService $evaluationService): int
    {
        $stats = $evaluationService->getStatistics($this->option('category'));

        $this->info('');
        $this->info('┌──────────────────────────────────────────────────┐');
        $this->info('│              AI GOLDEN TEST STATISTICS           │');
        $this->info('└──────────────────────────────────────────────────┘');
        $this->info('');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tests', $stats['total_tests']],
                ['Active Tests', $stats['active_tests']],
                ['Total Runs', $stats['total_runs']],
                ['Total Passes', $stats['total_passes']],
                ['Total Fails', $stats['total_fails']],
                ['Overall Pass Rate', $stats['overall_pass_rate'] . '%'],
                ['Currently Failing', $stats['currently_failing']],
                ['Avg Similarity', $stats['avg_similarity'] ?? 'N/A'],
            ]
        );

        if (!empty($stats['categories'])) {
            $this->info('');
            $this->info('Categories: ' . implode(', ', $stats['categories']));
        }

        return self::SUCCESS;
    }

    /**
     * Show failing tests.
     */
    protected function showFailing(AIEvaluationService $evaluationService): int
    {
        $failing = $evaluationService->getFailingTests();

        if ($failing->isEmpty()) {
            $this->info('✓ No failing tests!');
            return self::SUCCESS;
        }

        $this->warn("⚠ {$failing->count()} failing test(s):");
        $this->info('');

        $rows = $failing->map(function ($test) {
            return [
                $test->name,
                $test->category,
                $test->last_run_at?->diffForHumans() ?? 'Never',
                $test->pass_rate . '%',
            ];
        })->toArray();

        $this->table(
            ['Test Name', 'Category', 'Last Run', 'Pass Rate'],
            $rows
        );

        return self::FAILURE;
    }

    /**
     * Run a specific test.
     */
    protected function runSpecificTest(AIEvaluationService $evaluationService, string $testName): int
    {
        $test = AIGoldenTest::where('name', $testName)->first();

        if (!$test) {
            $this->error("Test '{$testName}' not found.");
            return self::FAILURE;
        }

        $this->info("Running test: {$testName}");
        $this->info('');

        $result = $evaluationService->runTest($test);

        return $this->displayResult($result);
    }

    /**
     * Run all tests.
     */
    protected function runAllTests(AIEvaluationService $evaluationService, ?string $category): int
    {
        $categoryInfo = $category ? " in category '{$category}'" : '';
        $this->info("Running all active golden tests{$categoryInfo}...");
        $this->info('');

        $startTime = microtime(true);
        $results = $evaluationService->runAll($category);
        $totalTime = (microtime(true) - $startTime) * 1000;

        // Display individual results
        foreach ($results['results'] as $result) {
            $this->displayResultLine($result);
        }

        // Display summary
        $this->info('');
        $this->info('──────────────────────────────────────────────────');
        $this->info('');

        $passedStyle = $results['passed'] === $results['total'] ? 'info' : 'warn';

        $this->$passedStyle("Tests: {$results['total']} | Passed: {$results['passed']} | Failed: {$results['failed']}");
        $this->info("Pass Rate: {$results['pass_rate']}%");
        $this->info("Total Time: " . round($totalTime, 2) . "ms");

        Log::info('Golden test suite run completed', [
            'total' => $results['total'],
            'passed' => $results['passed'],
            'failed' => $results['failed'],
            'pass_rate' => $results['pass_rate'],
            'duration_ms' => round($totalTime, 2),
        ]);

        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display a single result line.
     */
    protected function displayResultLine(array $result): void
    {
        $icon = $result['passed'] ? '✓' : '✗';
        $style = $result['passed'] ? 'info' : 'error';

        $similarity = isset($result['similarity_score'])
            ? " (sim: {$result['similarity_score']})"
            : '';

        $latency = " [{$result['latency_ms']}ms]";

        $this->$style("  {$icon} {$result['test_name']}{$similarity}{$latency}");

        // Show error if present
        if (isset($result['error']) && $this->option('verbose')) {
            $this->error("    Error: {$result['error']}");
        }
    }

    /**
     * Display detailed result.
     */
    protected function displayResult(array $result): int
    {
        $icon = $result['passed'] ? '✓' : '✗';
        $status = $result['passed'] ? 'PASSED' : 'FAILED';
        $style = $result['passed'] ? 'info' : 'error';

        $this->$style("{$icon} {$status}: {$result['test_name']}");
        $this->info('');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Category', $result['category']],
                ['Similarity Score', $result['similarity_score'] ?? 'N/A'],
                ['Latency', $result['latency_ms'] . 'ms'],
                ['Run ID', $result['run_id'] ?? 'N/A'],
            ]
        );

        if ($this->option('verbose') && !empty($result['details'])) {
            $this->info('');
            $this->info('Details:');
            $this->line(json_encode($result['details'], JSON_PRETTY_PRINT));
        }

        if (isset($result['error'])) {
            $this->info('');
            $this->error("Error: {$result['error']}");
        }

        return $result['passed'] ? self::SUCCESS : self::FAILURE;
    }
}
