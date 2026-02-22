<?php

namespace App\Jobs;

use App\Models\CompanyDNAProfile;
use App\Models\Company;
use App\Services\AI\Scout\CorporateDNADecoderService;
use App\Notifications\DNAAnalysisCompleteNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyzeCompanyDNAJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60; // Start with 60 seconds, exponential backoff

    /**
     * The company ID to analyze.
     *
     * @var int
     */
    protected $companyId;

    /**
     * Force refresh flag.
     *
     * @var bool
     */
    protected $forceRefresh;

    /**
     * Create a new job instance.
     *
     * @param int $companyId
     * @param bool $forceRefresh
     */
    public function __construct(int $companyId, bool $forceRefresh = false)
    {
        $this->companyId = $companyId;
        $this->forceRefresh = $forceRefresh;
    }

    /**
     * Execute the job.
     *
     * @param CorporateDNADecoderService $dnaService
     * @return void
     * @throws Exception
     */
    public function handle(CorporateDNADecoderService $dnaService): void
    {
        try {
            Log::info('Starting DNA analysis', [
                'company_id' => $this->companyId,
                'force_refresh' => $this->forceRefresh,
                'attempt' => $this->attempts()
            ]);

            // Load company
            $company = Company::findOrFail($this->companyId);

            // Check if analysis is needed
            $dnaProfile = CompanyDNAProfile::where('company_id', $this->companyId)->first();

            if (!$this->forceRefresh && $dnaProfile && $dnaProfile->analyzed_at) {
                // Skip if analyzed within last 6 days (weekly schedule)
                if ($dnaProfile->analyzed_at->isAfter(now()->subDays(6))) {
                    Log::info('DNA analysis skipped - too recent', [
                        'company_id' => $this->companyId,
                        'last_analyzed' => $dnaProfile->analyzed_at->toDateTimeString()
                    ]);
                    return;
                }
            }

            // Perform DNA analysis
            $analysisResult = $dnaService->analyzeCompanyDNA($this->companyId);

            if (!$analysisResult['success']) {
                throw new Exception($analysisResult['message'] ?? 'DNA analysis failed');
            }

            $dnaData = $analysisResult['data'];

            // Create or update DNA profile
            $dnaProfile = CompanyDNAProfile::updateOrCreate(
                ['company_id' => $this->companyId],
                [
                    'mission_statement' => $dnaData['mission_statement'] ?? null,
                    'vision_statement' => $dnaData['vision_statement'] ?? null,
                    'core_values' => $dnaData['core_values'] ?? [],
                    'cultural_dna' => $dnaData['cultural_dna'] ?? [],
                    'success_traits' => $dnaData['success_traits'] ?? [],
                    'work_style_preferences' => $dnaData['work_style_preferences'] ?? [],
                    'communication_patterns' => $dnaData['communication_patterns'] ?? [],
                    'retention_metrics' => $dnaData['retention_metrics'] ?? [],
                    'cultural_archetypes' => $dnaData['cultural_archetypes'] ?? [],
                    'ai_summary' => $dnaData['ai_summary'] ?? null,
                    'employees_analyzed' => $dnaData['employees_analyzed'] ?? 0,
                    'hires_analyzed' => $dnaData['hires_analyzed'] ?? 0,
                    'analyzed_at' => now(),
                ]
            );

            // Update completion score
            $dnaProfile->updateCompletionScore();

            // Calculate DNA health score
            $healthScore = $this->calculateDNAHealthScore($dnaProfile);
            $dnaProfile->update(['dna_health_score' => $healthScore]);

            Log::info('DNA analysis completed successfully', [
                'company_id' => $this->companyId,
                'dna_health_score' => $healthScore,
                'completion_score' => $dnaProfile->completion_score,
                'confidence_score' => $dnaProfile->confidence_score,
                'employees_analyzed' => $dnaProfile->employees_analyzed,
            ]);

            // Send notification to company admin
            $this->notifyCompanyAdmin($company, $dnaProfile);

        } catch (Exception $e) {
            Log::error('DNA analysis job failed', [
                'company_id' => $this->companyId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Calculate DNA health score based on profile completeness and data quality.
     *
     * @param CompanyDNAProfile $profile
     * @return int
     */
    protected function calculateDNAHealthScore(CompanyDNAProfile $profile): int
    {
        $score = 0;

        // Mission/Vision completeness (20 points)
        if ($profile->mission_statement) $score += 10;
        if ($profile->vision_statement) $score += 10;

        // Core values (15 points)
        $valueCount = count($profile->core_values ?? []);
        $score += min(15, $valueCount * 3); // 3 points per value, max 15

        // Cultural DNA traits (20 points)
        $dnaCount = count($profile->cultural_dna ?? []);
        $score += min(20, $dnaCount * 2.5); // 2.5 points per trait, max 20

        // Success traits (15 points)
        $traitsCount = count($profile->success_traits ?? []);
        $score += min(15, $traitsCount * 3); // 3 points per trait, max 15

        // Data volume (20 points)
        $employeePoints = min(10, ($profile->employees_analyzed / 10) * 10);
        $hiresPoints = min(10, ($profile->hires_analyzed / 5) * 10);
        $score += $employeePoints + $hiresPoints;

        // Confidence score (10 points)
        $score += min(10, $profile->confidence_score / 10);

        return min(100, round($score));
    }

    /**
     * Send notification to company admin about completed analysis.
     *
     * @param Company $company
     * @param CompanyDNAProfile $profile
     * @return void
     */
    protected function notifyCompanyAdmin(Company $company, CompanyDNAProfile $profile): void
    {
        try {
            // Find company admin/owner
            $admin = $company->users()
                ->where('account_type', 'employer')
                ->where('role', 'admin')
                ->first();

            if (!$admin) {
                $admin = $company->users()
                    ->where('account_type', 'employer')
                    ->first();
            }

            if ($admin) {
                $admin->notify(new DNAAnalysisCompleteNotification($profile));
                
                Log::info('DNA analysis notification sent', [
                    'company_id' => $this->companyId,
                    'user_id' => $admin->id
                ]);
            }
        } catch (Exception $e) {
            // Don't fail job if notification fails
            Log::warning('Failed to send DNA analysis notification', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        // Exponential backoff: 60s, 180s, 540s
        return [60, 180, 540];
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('DNA analysis job permanently failed', [
            'company_id' => $this->companyId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Update DNA profile with error status
        try {
            CompanyDNAProfile::where('company_id', $this->companyId)
                ->update([
                    'last_error' => $exception->getMessage(),
                    'last_error_at' => now()
                ]);
        } catch (Exception $e) {
            Log::error('Failed to update DNA profile error status', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
