<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\PassiveCandidateProfile;
use App\Models\TalentPipeline;
use App\Models\User;
use App\Services\AI\Scout\PassiveCandidateScoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DiscoverPassiveCandidatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company to discover candidates for
     */
    protected Company $company;

    /**
     * Optional specific pipeline to discover for
     */
    protected ?TalentPipeline $pipeline;

    /**
     * Maximum candidates to discover
     */
    protected int $limit;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900; // 15 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company, ?TalentPipeline $pipeline = null, int $limit = 50)
    {
        $this->company = $company;
        $this->pipeline = $pipeline;
        $this->limit = $limit;
        $this->onQueue('candidate-discovery');
    }

    /**
     * Execute the job.
     */
    public function handle(PassiveCandidateScoutService $passiveCandidateScout): void
    {
        $startTime = now();
        $progressKey = "passive_discovery_progress_{$this->company->id}";
        
        try {
            Log::info('Starting passive candidate discovery', [
                'company_id' => $this->company->id,
                'pipeline_id' => $this->pipeline?->id,
                'limit' => $this->limit,
            ]);

            // Initialize progress
            $this->updateProgress($progressKey, 0, 'Starting candidate discovery...');

            if ($this->pipeline) {
                // Discover for specific pipeline
                $this->discoverForPipeline($passiveCandidateScout, $progressKey);
            } else {
                // Discover for all active pipelines
                $this->discoverForAllPipelines($passiveCandidateScout, $progressKey);
            }

            // Monitor existing passive candidates
            $this->updateProgress($progressKey, 80, 'Monitoring existing passive candidates...');
            $monitoredCount = $this->monitorExistingCandidates($passiveCandidateScout);

            $this->updateProgress($progressKey, 100, 'Discovery completed');

            $duration = now()->diffInSeconds($startTime);

            Log::info('Passive candidate discovery completed', [
                'company_id' => $this->company->id,
                'duration_seconds' => $duration,
                'monitored_candidates' => $monitoredCount,
            ]);

            // Store completion timestamp
            Cache::put("passive_discovery_last_run_{$this->company->id}", now(), 86400);

        } catch (\Exception $e) {
            Log::error('Failed to discover passive candidates', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress($progressKey, -1, 'Discovery failed: ' . $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Discover candidates for specific pipeline
     *
     * @param PassiveCandidateScoutService $service
     * @param string $progressKey
     * @return void
     */
    protected function discoverForPipeline(PassiveCandidateScoutService $service, string $progressKey): void
    {
        $this->updateProgress($progressKey, 10, "Discovering candidates for {$this->pipeline->pipeline_name}...");

        $candidates = $service->discoverCandidatesForPipeline($this->pipeline, $this->limit);

        $this->updateProgress($progressKey, 40, "Found {$candidates->count()} potential candidates");

        $createdCount = 0;
        foreach ($candidates as $candidateData) {
            $user = $candidateData['user'];
            $score = $candidateData['score'];
            $dnaMatch = $candidateData['dna_match'];

            // Check if already in passive candidate pool
            $existingProfile = PassiveCandidateProfile::where('company_id', $this->company->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$existingProfile) {
                $profile = $service->createPassiveCandidateProfile($this->company, $user, [
                    'discovery_method' => 'automated_discovery',
                    'discovery_source' => "Pipeline: {$this->pipeline->pipeline_name}",
                    'discovery_notes' => "Match score: {$score}%, DNA compatibility: {$dnaMatch}%",
                ]);

                $createdCount++;

                Log::info('Passive candidate profile created', [
                    'profile_id' => $profile->id,
                    'user_id' => $user->id,
                    'pipeline_id' => $this->pipeline->id,
                    'match_score' => $score,
                    'dna_match' => $dnaMatch,
                ]);
            }
        }

        $this->updateProgress($progressKey, 70, "Created {$createdCount} new passive candidate profiles");
    }

    /**
     * Discover candidates for all active pipelines
     *
     * @param PassiveCandidateScoutService $service
     * @param string $progressKey
     * @return void
     */
    protected function discoverForAllPipelines(PassiveCandidateScoutService $service, string $progressKey): void
    {
        $pipelines = TalentPipeline::where('company_id', $this->company->id)
            ->active()
            ->get();

        if ($pipelines->isEmpty()) {
            $this->updateProgress($progressKey, 70, 'No active pipelines found');
            Log::warning('No active pipelines for passive candidate discovery', [
                'company_id' => $this->company->id,
            ]);
            return;
        }

        $totalCreated = 0;
        $pipelinesProcessed = 0;

        foreach ($pipelines as $pipeline) {
            $progress = 10 + (($pipelinesProcessed / $pipelines->count()) * 60);
            $this->updateProgress($progressKey, (int)$progress, "Processing {$pipeline->pipeline_name}...");

            $limitPerPipeline = (int)ceil($this->limit / $pipelines->count());
            $candidates = $service->discoverCandidatesForPipeline($pipeline, $limitPerPipeline);

            foreach ($candidates as $candidateData) {
                $user = $candidateData['user'];
                $score = $candidateData['score'];
                $dnaMatch = $candidateData['dna_match'];

                // Check if already exists
                $existingProfile = PassiveCandidateProfile::where('company_id', $this->company->id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$existingProfile) {
                    $service->createPassiveCandidateProfile($this->company, $user, [
                        'discovery_method' => 'automated_discovery',
                        'discovery_source' => "Pipeline: {$pipeline->pipeline_name}",
                        'discovery_notes' => "Match score: {$score}%, DNA compatibility: {$dnaMatch}%",
                    ]);

                    $totalCreated++;
                }
            }

            $pipelinesProcessed++;
        }

        $this->updateProgress($progressKey, 70, "Created {$totalCreated} passive candidate profiles across {$pipelinesProcessed} pipelines");
    }

    /**
     * Monitor existing passive candidates for engagement signals
     *
     * @param PassiveCandidateScoutService $service
     * @return int
     */
    protected function monitorExistingCandidates(PassiveCandidateScoutService $service): int
    {
        $profiles = PassiveCandidateProfile::where('company_id', $this->company->id)
            ->monitoring()
            ->needsMonitoring(30) // Haven't been monitored in 30 days
            ->get();

        $count = 0;
        foreach ($profiles as $profile) {
            try {
                $service->monitorEngagementSignals($profile);
                $count++;
            } catch (\Exception $e) {
                Log::warning('Failed to monitor passive candidate', [
                    'profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Passive candidates monitored', [
            'company_id' => $this->company->id,
            'profiles_monitored' => $count,
        ]);

        return $count;
    }

    /**
     * Update job progress
     *
     * @param string $key
     * @param int $percentage
     * @param string $message
     * @return void
     */
    protected function updateProgress(string $key, int $percentage, string $message): void
    {
        Cache::put($key, [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toIso8601String(),
        ], 3600); // 1 hour
    }

    /**
     * Get progress for a company
     *
     * @param int $companyId
     * @return array|null
     */
    public static function getProgress(int $companyId): ?array
    {
        return Cache::get("passive_discovery_progress_{$companyId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DiscoverPassiveCandidatesJob failed', [
            'company_id' => $this->company->id,
            'pipeline_id' => $this->pipeline?->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators
    }
}
