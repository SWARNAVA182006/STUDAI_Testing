<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AIGoldenTest;
use App\Models\AIGoldenTestResult;
use App\Services\AI\AIService;
use App\Services\AI\AIEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MonitorModelDriftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('low');
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService, AIEvaluationService $evaluationService): void
    {
        $driftThreshold = (float) config('ai.drift_threshold', 15.0);
        $criticalThreshold = (float) config('ai.drift_critical_threshold', 30.0);

        $goldenTests = AIGoldenTest::where('is_active', true)->get();

        if ($goldenTests->isEmpty()) {
            Log::info('MonitorModelDriftJob: No active golden tests found. Skipping.');
            return;
        }

        Log::info('MonitorModelDriftJob: Running drift detection against golden tests.', [
            'test_count' => $goldenTests->count(),
            'drift_threshold' => $driftThreshold,
            'critical_threshold' => $criticalThreshold,
        ]);

        $driftResults = [];

        foreach ($goldenTests as $goldenTest) {
            try {
                $response = $aiService->generateText($goldenTest->prompt);

                $similarity = $this->calculateSimilarity($goldenTest->expected_output, $response);
                $driftPercentage = round(100.0 - $similarity, 2);

                $result = AIGoldenTestResult::create([
                    'ai_golden_test_id' => $goldenTest->id,
                    'prompt' => $goldenTest->prompt,
                    'expected_output' => $goldenTest->expected_output,
                    'actual_output' => $response,
                    'similarity_score' => round($similarity, 2),
                    'drift_percentage' => $driftPercentage,
                    'threshold_exceeded' => $driftPercentage > $driftThreshold,
                    'evaluated_at' => now(),
                ]);

                $driftResults[] = [
                    'test_id' => $goldenTest->id,
                    'test_name' => $goldenTest->name ?? 'Unnamed',
                    'drift_percentage' => $driftPercentage,
                    'similarity_score' => round($similarity, 2),
                ];

                if ($driftPercentage > $criticalThreshold) {
                    Log::critical('MonitorModelDriftJob: Critical drift detected.', [
                        'test_id' => $goldenTest->id,
                        'test_name' => $goldenTest->name ?? 'Unnamed',
                        'drift_percentage' => $driftPercentage,
                        'critical_threshold' => $criticalThreshold,
                    ]);

                    $this->notifyAdminOfCriticalDrift($goldenTest, $driftPercentage, $response);
                } elseif ($driftPercentage > $driftThreshold) {
                    Log::warning('MonitorModelDriftJob: Drift threshold exceeded.', [
                        'test_id' => $goldenTest->id,
                        'test_name' => $goldenTest->name ?? 'Unnamed',
                        'drift_percentage' => $driftPercentage,
                        'drift_threshold' => $driftThreshold,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('MonitorModelDriftJob: Failed to evaluate golden test.', [
                    'test_id' => $goldenTest->id,
                    'test_name' => $goldenTest->name ?? 'Unnamed',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                AIGoldenTestResult::create([
                    'ai_golden_test_id' => $goldenTest->id,
                    'prompt' => $goldenTest->prompt,
                    'expected_output' => $goldenTest->expected_output,
                    'actual_output' => null,
                    'similarity_score' => 0.0,
                    'drift_percentage' => 100.0,
                    'threshold_exceeded' => true,
                    'error_message' => $e->getMessage(),
                    'evaluated_at' => now(),
                ]);
            }
        }

        Log::info('MonitorModelDriftJob: Drift detection completed.', [
            'results_count' => count($driftResults),
            'results' => $driftResults,
        ]);
    }

    /**
     * Calculate similarity between expected and actual output using keyword overlap scoring.
     *
     * Normalizes both strings, tokenizes them into keywords, and computes the
     * Jaccard similarity coefficient as a percentage.
     */
    protected function calculateSimilarity(string $expected, string $actual): float
    {
        $expectedNormalized = $this->normalizeText($expected);
        $actualNormalized = $this->normalizeText($actual);

        $expectedKeywords = $this->extractKeywords($expectedNormalized);
        $actualKeywords = $this->extractKeywords($actualNormalized);

        if (empty($expectedKeywords) && empty($actualKeywords)) {
            return 100.0;
        }

        if (empty($expectedKeywords) || empty($actualKeywords)) {
            return 0.0;
        }

        $intersection = array_intersect($expectedKeywords, $actualKeywords);
        $union = array_unique(array_merge($expectedKeywords, $actualKeywords));

        $jaccardSimilarity = count($intersection) / count($union);

        return $jaccardSimilarity * 100.0;
    }

    /**
     * Normalize text for comparison by lowercasing and stripping punctuation.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = (string) preg_replace('/[^\w\s]/u', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Extract unique keywords from normalized text, filtering out common stop words.
     *
     * @return array<int, string>
     */
    private function extractKeywords(string $text): array
    {
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to',
            'for', 'of', 'with', 'by', 'from', 'is', 'it', 'that', 'this',
            'was', 'are', 'be', 'has', 'had', 'have', 'will', 'would', 'could',
            'should', 'may', 'might', 'can', 'do', 'does', 'did', 'not', 'no',
            'so', 'if', 'as', 'its', 'also', 'than', 'then', 'just', 'about',
            'into', 'over', 'after', 'before', 'between', 'under', 'above',
        ];

        $words = explode(' ', $text);

        $keywords = array_filter($words, function (string $word) use ($stopWords): bool {
            return mb_strlen($word) > 1 && !in_array($word, $stopWords, true);
        });

        return array_values(array_unique($keywords));
    }

    /**
     * Send a notification to admin users when critical drift is detected.
     */
    private function notifyAdminOfCriticalDrift(
        AIGoldenTest $goldenTest,
        float $driftPercentage,
        string $actualOutput
    ): void {
        try {
            $adminEmail = config('ai.drift_admin_email', config('mail.from.address'));

            if (!$adminEmail) {
                Log::warning('MonitorModelDriftJob: No admin email configured for critical drift notifications.');
                return;
            }

            Notification::route('mail', $adminEmail)->notify(
                new \App\Notifications\CriticalModelDriftNotification(
                    goldenTestName: $goldenTest->name ?? 'Unnamed',
                    goldenTestId: $goldenTest->id,
                    driftPercentage: $driftPercentage,
                    expectedOutput: $goldenTest->expected_output,
                    actualOutput: $actualOutput
                )
            );

            Log::info('MonitorModelDriftJob: Critical drift notification sent.', [
                'test_id' => $goldenTest->id,
                'admin_email' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('MonitorModelDriftJob: Failed to send critical drift notification.', [
                'test_id' => $goldenTest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
