<?php

namespace App\Jobs;

use App\Models\HiringPattern;
use App\Models\Company;
use App\Models\Job;
use App\Services\AI\Scout\HiringPatternAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateHiringPatternsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * The company ID to analyze.
     *
     * @var int
     */
    protected $companyId;

    /**
     * The job ID that triggered this analysis.
     *
     * @var int|null
     */
    protected $jobId;

    /**
     * Create a new job instance.
     *
     * @param int $companyId
     * @param int|null $jobId
     */
    public function __construct(int $companyId, ?int $jobId = null)
    {
        $this->companyId = $companyId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     *
     * @param HiringPatternAnalyzerService $patternService
     * @return void
     * @throws Exception
     */
    public function handle(HiringPatternAnalyzerService $patternService): void
    {
        try {
            Log::info('Starting hiring pattern analysis', [
                'company_id' => $this->companyId,
                'job_id' => $this->jobId,
                'attempt' => $this->attempts()
            ]);

            // Load company
            $company = Company::findOrFail($this->companyId);

            // Verify minimum data requirement
            $totalHires = $company->applications()
                ->where('status', 'hired')
                ->count();

            if ($totalHires < 5) {
                Log::info('Hiring pattern analysis skipped - insufficient data', [
                    'company_id' => $this->companyId,
                    'total_hires' => $totalHires,
                    'minimum_required' => 5
                ]);
                return;
            }

            // Perform pattern analysis
            $analysisResult = $patternService->analyzeHiringPatterns($this->companyId, $this->jobId);

            if (!$analysisResult['success']) {
                throw new Exception($analysisResult['message'] ?? 'Hiring pattern analysis failed');
            }

            $patternData = $analysisResult['data'];

            // Get existing pattern or create new
            $hiringPattern = HiringPattern::firstOrNew([
                'company_id' => $this->companyId
            ]);

            // Increment total hires counter
            $hiringPattern->total_hires_in_period = ($hiringPattern->total_hires_in_period ?? 0) + 1;

            // Update pattern data
            $hiringPattern->fill([
                'hiring_sources' => $patternData['hiring_sources'] ?? [],
                'source_effectiveness' => $patternData['source_effectiveness'] ?? [],
                'time_to_hire_avg' => $patternData['time_to_hire_avg'] ?? null,
                'time_to_fill_avg' => $patternData['time_to_fill_avg'] ?? null,
                'interview_to_offer_ratio' => $patternData['interview_to_offer_ratio'] ?? null,
                'offer_acceptance_rate' => $patternData['offer_acceptance_rate'] ?? null,
                'success_patterns' => $patternData['success_patterns'] ?? [],
                'failure_patterns' => $patternData['failure_patterns'] ?? [],
                'seasonal_trends' => $patternData['seasonal_trends'] ?? [],
                'retention_correlation' => $patternData['retention_correlation'] ?? [],
                'top_performer_traits' => $patternData['top_performer_traits'] ?? [],
                'red_flags' => $patternData['red_flags'] ?? [],
                'ai_recommendations' => $patternData['ai_recommendations'] ?? null,
                'analyzed_at' => now(),
            ]);

            // Calculate and update confidence score
            $confidenceScore = $this->calculateConfidenceScore($totalHires, $hiringPattern);
            $hiringPattern->confidence_score = $confidenceScore;

            // Save pattern
            $hiringPattern->save();

            Log::info('Hiring pattern analysis completed successfully', [
                'company_id' => $this->companyId,
                'job_id' => $this->jobId,
                'total_hires' => $totalHires,
                'confidence_score' => $confidenceScore,
                'sources_analyzed' => count($hiringPattern->hiring_sources ?? [])
            ]);

            // Log significant pattern changes for monitoring
            $this->logPatternChanges($hiringPattern);

        } catch (Exception $e) {
            Log::error('Hiring pattern analysis job failed', [
                'company_id' => $this->companyId,
                'job_id' => $this->jobId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Calculate confidence score based on data volume and quality.
     *
     * @param int $totalHires
     * @param HiringPattern $pattern
     * @return int
     */
    protected function calculateConfidenceScore(int $totalHires, HiringPattern $pattern): int
    {
        $score = 0;

        // Data volume score (0-50 points)
        // 5 hires = 25 points, 20+ hires = 50 points
        $volumeScore = min(50, ($totalHires / 20) * 50);
        $score += $volumeScore;

        // Data diversity score (0-25 points)
        $sourceCount = count($pattern->hiring_sources ?? []);
        $diversityScore = min(25, $sourceCount * 5); // 5 points per source, max 25
        $score += $diversityScore;

        // Pattern completeness score (0-25 points)
        $completenessPoints = 0;
        if (!empty($pattern->success_patterns)) $completenessPoints += 8;
        if (!empty($pattern->failure_patterns)) $completenessPoints += 8;
        if (!empty($pattern->top_performer_traits)) $completenessPoints += 9;
        $score += $completenessPoints;

        return min(100, round($score));
    }

    /**
     * Log significant pattern changes for monitoring.
     *
     * @param HiringPattern $pattern
     * @return void
     */
    protected function logPatternChanges(HiringPattern $pattern): void
    {
        try {
            // Get previous version if exists
            $previousPattern = HiringPattern::where('company_id', $this->companyId)
                ->where('id', '!=', $pattern->id)
                ->orderBy('analyzed_at', 'desc')
                ->first();

            if (!$previousPattern) {
                return; // First analysis, nothing to compare
            }

            // Check for significant changes
            $changes = [];

            // Offer acceptance rate change > 10%
            if ($previousPattern->offer_acceptance_rate && $pattern->offer_acceptance_rate) {
                $rateDiff = abs($pattern->offer_acceptance_rate - $previousPattern->offer_acceptance_rate);
                if ($rateDiff > 10) {
                    $changes[] = [
                        'metric' => 'offer_acceptance_rate',
                        'previous' => $previousPattern->offer_acceptance_rate,
                        'current' => $pattern->offer_acceptance_rate,
                        'change' => $rateDiff
                    ];
                }
            }

            // Time to hire change > 7 days
            if ($previousPattern->time_to_hire_avg && $pattern->time_to_hire_avg) {
                $timeDiff = abs($pattern->time_to_hire_avg - $previousPattern->time_to_hire_avg);
                if ($timeDiff > 7) {
                    $changes[] = [
                        'metric' => 'time_to_hire_avg',
                        'previous' => $previousPattern->time_to_hire_avg,
                        'current' => $pattern->time_to_hire_avg,
                        'change' => $timeDiff
                    ];
                }
            }

            if (!empty($changes)) {
                Log::info('Significant hiring pattern changes detected', [
                    'company_id' => $this->companyId,
                    'changes' => $changes
                ]);
            }

        } catch (Exception $e) {
            // Don't fail job if pattern comparison fails
            Log::warning('Failed to log pattern changes', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        // Linear backoff: 120s between retries
        return 120;
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Hiring pattern analysis job permanently failed', [
            'company_id' => $this->companyId,
            'job_id' => $this->jobId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Update pattern with error status
        try {
            HiringPattern::where('company_id', $this->companyId)
                ->update([
                    'last_error' => $exception->getMessage(),
                    'last_error_at' => now()
                ]);
        } catch (Exception $e) {
            Log::error('Failed to update hiring pattern error status', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
