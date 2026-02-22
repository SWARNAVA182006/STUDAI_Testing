<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Job;
use App\Models\Application;
use App\Models\User;
use App\Services\AI\Scout\SuccessPredictorService;
use App\Notifications\TopCandidateMatchesNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateCandidateMatchScoresJob implements ShouldQueue
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
    public $timeout = 900; // 15 minutes

    /**
     * The company ID to process (null for all companies).
     *
     * @var int|null
     */
    protected $companyId;

    /**
     * Create a new job instance.
     *
     * @param int|null $companyId
     */
    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     *
     * @param SuccessPredictorService $predictorService
     * @return void
     * @throws Exception
     */
    public function handle(SuccessPredictorService $predictorService): void
    {
        try {
            Log::info('Starting candidate match score generation', [
                'company_id' => $this->companyId,
                'attempt' => $this->attempts()
            ]);

            // Get companies with active jobs
            $companiesQuery = Company::query()
                ->whereHas('jobs', function ($query) {
                    $query->where('status', 'active')
                        ->where('expires_at', '>', now());
                });

            if ($this->companyId) {
                $companiesQuery->where('id', $this->companyId);
            }

            $companies = $companiesQuery->get();

            $totalScored = 0;
            $topMatches = [];

            foreach ($companies as $company) {
                try {
                    $companyResults = $this->processCompanyCandidates($company, $predictorService);
                    $totalScored += $companyResults['scored'];
                    
                    if (!empty($companyResults['top_matches'])) {
                        $topMatches[$company->id] = $companyResults['top_matches'];
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to process candidates for company', [
                        'company_id' => $company->id,
                        'error' => $e->getMessage()
                    ]);
                    continue; // Continue with next company
                }
            }

            Log::info('Candidate match score generation completed', [
                'companies_processed' => $companies->count(),
                'total_candidates_scored' => $totalScored,
                'companies_with_matches' => count($topMatches)
            ]);

            // Send summary notifications
            $this->sendMatchNotifications($topMatches);

        } catch (Exception $e) {
            Log::error('Candidate match score generation job failed', [
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
     * Process candidates for a single company.
     *
     * @param Company $company
     * @param SuccessPredictorService $predictorService
     * @return array
     */
    protected function processCompanyCandidates(Company $company, SuccessPredictorService $predictorService): array
    {
        $scored = 0;
        $topMatches = [];

        // Get active applications for this company
        $applications = Application::where('company_id', $company->id)
            ->whereIn('status', ['pending', 'reviewing', 'shortlisted'])
            ->with(['user.profile', 'job'])
            ->get();

        if ($applications->isEmpty()) {
            return ['scored' => 0, 'top_matches' => []];
        }

        // Batch process candidates to avoid memory issues
        $batchSize = 50;
        $batches = $applications->chunk($batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $application) {
                try {
                    $candidate = $this->buildCandidateProfile($application->user);
                    
                    // Predict success
                    $prediction = $predictorService->predictCandidateSuccess(
                        $company->id,
                        $candidate
                    );

                    if ($prediction['success']) {
                        $score = $prediction['data']['success_prediction']['overall_success_score'] ?? 0;
                        
                        // Cache the match score (TTL: 24 hours)
                        $cacheKey = "candidate_match_{$company->id}_{$application->user_id}";
                        Cache::put($cacheKey, $prediction['data'], now()->addDay());

                        // Track top matches (score >= 85)
                        if ($score >= 85) {
                            $topMatches[] = [
                                'application_id' => $application->id,
                                'user_id' => $application->user_id,
                                'user_name' => $application->user->name,
                                'job_title' => $application->job->title,
                                'score' => $score,
                                'recommendation' => $prediction['data']['success_prediction']['recommendation'] ?? null
                            ];
                        }

                        $scored++;
                    }

                } catch (Exception $e) {
                    Log::warning('Failed to score candidate', [
                        'company_id' => $company->id,
                        'application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'error' => $e->getMessage()
                    ]);
                    continue; // Continue with next candidate
                }
            }

            // Small delay between batches to avoid rate limits
            if ($batches->count() > 1) {
                sleep(1);
            }
        }

        // Sort top matches by score
        usort($topMatches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return [
            'scored' => $scored,
            'top_matches' => array_slice($topMatches, 0, 10) // Top 10
        ];
    }

    /**
     * Build candidate profile from user data.
     *
     * @param User $user
     * @return array
     */
    protected function buildCandidateProfile(User $user): array
    {
        $profile = $user->profile;

        return [
            'skills' => $profile->skills ?? [],
            'experience' => $profile->experience ?? [],
            'education' => $profile->education ?? [],
            'values' => $profile->values ?? [],
            'work_style' => $profile->work_style_preferences ?? [],
            'traits' => $profile->personality_traits ?? [],
            'achievements' => $profile->achievements ?? [],
            'years_of_experience' => $profile->years_of_experience ?? 0,
        ];
    }

    /**
     * Send match notifications to companies with top candidates.
     *
     * @param array $topMatches
     * @return void
     */
    protected function sendMatchNotifications(array $topMatches): void
    {
        foreach ($topMatches as $companyId => $matches) {
            try {
                $company = Company::find($companyId);
                if (!$company) {
                    continue;
                }

                // Find company admin/HR manager
                $admin = $company->users()
                    ->where('account_type', 'employer')
                    ->whereIn('role', ['admin', 'hr_manager'])
                    ->first();

                if (!$admin) {
                    $admin = $company->users()
                        ->where('account_type', 'employer')
                        ->first();
                }

                if ($admin && !empty($matches)) {
                    $admin->notify(new TopCandidateMatchesNotification($matches));
                    
                    Log::info('Top candidate matches notification sent', [
                        'company_id' => $companyId,
                        'user_id' => $admin->id,
                        'match_count' => count($matches)
                    ]);
                }

            } catch (Exception $e) {
                // Don't fail job if notification fails
                Log::warning('Failed to send candidate match notification', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        // 3 minutes between retries
        return 180;
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Candidate match score generation job permanently failed', [
            'company_id' => $this->companyId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Clear any partial cache data
        try {
            if ($this->companyId) {
                $pattern = "candidate_match_{$this->companyId}_*";
                Cache::flush(); // Consider more targeted cache clearing in production
            }
        } catch (Exception $e) {
            Log::error('Failed to clear candidate match cache', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
