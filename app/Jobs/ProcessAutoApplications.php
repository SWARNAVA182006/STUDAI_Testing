<?php

namespace App\Jobs;

use App\Models\AgentConfiguration;
use App\Models\ApplicationActivityLog;
use App\Models\JobMatch;
use App\Notifications\AgentApprovalRequestNotification;
use App\Services\Agent\ApplicationSubmissionService;
use App\Services\Agent\AutoApplicationAgentService;
use App\Services\Agent\UserResumeResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessAutoApplications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff intervals (seconds) between retries.
     *
     * @var array<int>
     */
    public array $backoff = [60, 300, 900];

    /**
     * Maximum number of unhandled exceptions before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    protected ?int $configurationId;
    protected int $maxPerConfig;

    public function __construct(?int $configurationId = null, int $maxPerConfig = 3)
    {
        $this->configurationId = $configurationId;
        $this->maxPerConfig = max(1, $maxPerConfig);
        $this->onQueue('agent');
    }

    public function handle(
        AutoApplicationAgentService $agentService,
        UserResumeResolver $resumeResolver,
        ApplicationSubmissionService $submissionService
    ): void {
        $query = AgentConfiguration::query()
            ->with(['user', 'user.profile'])
            ->where('is_active', true);

        if ($this->configurationId) {
            $query->whereKey($this->configurationId);
        }

        $configurations = $query->get();

        foreach ($configurations as $configuration) {
            $this->processConfiguration(
                $configuration,
                $agentService,
                $resumeResolver,
                $submissionService
            );
        }
    }

    /**
     * Process a single agent configuration.
     */
    protected function processConfiguration(
        AgentConfiguration $configuration,
        AutoApplicationAgentService $agentService,
        UserResumeResolver $resumeResolver,
        ApplicationSubmissionService $submissionService
    ): void {
        $user = $configuration->user;

        if (!$user) {
            Log::warning('Agent configuration missing user', ['configuration_id' => $configuration->id]);
            return;
        }

        // Check if agent can run
        if (!$configuration->canRun()) {
            Log::info('Agent cannot run', [
                'configuration_id' => $configuration->id,
                'reason' => 'canRun() returned false',
            ]);
            return;
        }

        if (!$configuration->isInActiveHours()) {
            return;
        }

        if ($configuration->hasReachedMonthlyLimit()) {
            Log::info('Agent reached monthly limit', ['configuration_id' => $configuration->id]);
            return;
        }

        // P0-6: Check daily hard cap
        if ($configuration->hasReachedDailyHardCap()) {
            Log::info('Agent reached daily hard cap', [
                'configuration_id' => $configuration->id,
                'applications_today' => $configuration->getApplicationsToday(),
                'daily_limit' => $configuration->daily_application_limit,
            ]);
            return;
        }

        $remainingToday = $configuration->getRemainingApplicationsToday();
        if ($remainingToday <= 0) {
            return;
        }

        $adjustedLimit = (int) min(
            $remainingToday,
            max(1, floor($remainingToday * $configuration->getAggressivenessMultiplier()))
        );

        $limit = min($adjustedLimit, $this->maxPerConfig);

        $matches = $this->getMatchesForConfiguration($configuration, $limit);
        if ($matches->isEmpty()) {
            $configuration->updateRunSchedule();
            return;
        }

        $baseResume = $resumeResolver->resolve($user);

        foreach ($matches as $match) {
            // Check daily cap before each application
            if ($configuration->hasReachedDailyHardCap()) {
                Log::info('Daily cap reached during processing', [
                    'configuration_id' => $configuration->id,
                ]);
                break;
            }

            try {
                $this->processMatch(
                    $match,
                    $configuration,
                    $user,
                    $baseResume,
                    $agentService,
                    $submissionService
                );
            } catch (RuntimeException $exception) {
                ApplicationActivityLog::log(
                    $configuration->user_id,
                    'application_error',
                    $exception->getMessage(),
                    $match->auto_application_id,
                    $match->discovered_job_id,
                    ['match_id' => $match->id],
                    'error'
                );
                Log::warning('Auto application skipped', [
                    'configuration_id' => $configuration->id,
                    'job_match_id' => $match->id,
                    'message' => $exception->getMessage(),
                ]);

                if (str_contains(strtolower($exception->getMessage()), 'limit')) {
                    break;
                }
            } catch (\Throwable $exception) {
                ApplicationActivityLog::log(
                    $configuration->user_id,
                    'application_error',
                    'Unexpected error preparing application.',
                    $match->auto_application_id,
                    $match->discovered_job_id,
                    [
                        'match_id' => $match->id,
                        'error' => $exception->getMessage(),
                    ],
                    'error'
                );
                Log::error('Auto application processing failed', [
                    'configuration_id' => $configuration->id,
                    'job_match_id' => $match->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $configuration->updateRunSchedule();
    }

    /**
     * Process a single job match.
     */
    protected function processMatch(
        JobMatch $match,
        AgentConfiguration $configuration,
        $user,
        $baseResume,
        AutoApplicationAgentService $agentService,
        ApplicationSubmissionService $submissionService
    ): void {
        $matchScore = (float) ($match->overall_match_score ?? 0);

        // P0-5: Check if approval is required
        if ($configuration->requiresApprovalForMatch($matchScore)) {
            $this->requestApproval($match, $configuration, $user, $matchScore);
            return;
        }

        $screeningQuestions = $this->extractScreeningQuestions($match);
        $autoSubmit = $this->shouldAutoSubmit($configuration, $match);

        $result = $agentService->prepareForMatch(
            $match,
            $baseResume,
            $screeningQuestions,
            [
                'auto_submit' => $autoSubmit,
                'submission_method' => $autoSubmit ? 'api' : 'manual_review',
            ]
        );

        // If auto_submit is enabled, actually submit the application
        if ($autoSubmit && isset($result['application'])) {
            $autoApplication = $result['application'];

            try {
                $submitted = $submissionService->submit($autoApplication);

                if ($submitted) {
                    // Increment daily counter on successful submission
                    $configuration->incrementApplicationsToday();
                    $configuration->incrementApplicationsThisMonth();

                    Log::info('Auto application submitted successfully', [
                        'application_id' => $autoApplication->id,
                        'job_id' => $match->discovered_job_id,
                        'user_id' => $configuration->user_id,
                    ]);
                } else {
                    Log::warning('Application submission failed', [
                        'application_id' => $autoApplication->id,
                        'job_id' => $match->discovered_job_id,
                    ]);
                }
            } catch (\Exception $submissionException) {
                Log::error('Application submission error', [
                    'application_id' => $autoApplication->id,
                    'error' => $submissionException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Request human approval for an application.
     *
     * P0-5: Human-in-the-loop approval gate
     */
    protected function requestApproval(
        JobMatch $match,
        AgentConfiguration $configuration,
        $user,
        float $matchScore
    ): void {
        // Mark the match as requiring review
        $match->update([
            'agent_decision' => 'review',
            'decision_reasoning' => $configuration->getApprovalReason($matchScore),
        ]);

        // Send notification to user
        $user->notify(new AgentApprovalRequestNotification($match, [
            'reason' => $configuration->getApprovalReason($matchScore),
            'match_score' => $matchScore,
            'approval_threshold' => $configuration->approval_threshold ?? 80,
        ]));

        ApplicationActivityLog::log(
            $configuration->user_id,
            'approval_requested',
            'Application requires user approval before submission',
            null,
            $match->discovered_job_id,
            [
                'match_id' => $match->id,
                'match_score' => $matchScore,
                'reason' => $configuration->getApprovalReason($matchScore),
            ],
            'pending'
        );

        Log::info('Agent approval requested', [
            'configuration_id' => $configuration->id,
            'job_match_id' => $match->id,
            'match_score' => $matchScore,
            'reason' => $configuration->getApprovalReason($matchScore),
        ]);
    }

    protected function getMatchesForConfiguration(AgentConfiguration $configuration, int $limit): Collection
    {
        return JobMatch::query()
            ->where('user_id', $configuration->user_id)
            ->approved()
            ->whereNull('auto_application_id')
            ->where('overall_match_score', '>=', $configuration->match_threshold_percentage ?? 0)
            ->orderByDesc('overall_match_score')
            ->limit($limit)
            ->get();
    }

    protected function extractScreeningQuestions(JobMatch $match): array
    {
        $questions = [];

        $job = $match->discoveredJob;
        if ($job) {
            $jobQuestions = Arr::get($job->toArray(), 'screening_questions');
            if (is_array($jobQuestions)) {
                $questions = $jobQuestions;
            }
        }

        if (empty($questions) && is_array($match->score_breakdown)) {
            $breakdownQuestions = Arr::get($match->score_breakdown, 'screening_questions');
            if (is_array($breakdownQuestions)) {
                $questions = $breakdownQuestions;
            }
        }

        return array_values(array_filter(array_unique($questions)));
    }

    protected function shouldAutoSubmit(AgentConfiguration $configuration, JobMatch $match): bool
    {
        if (!in_array($configuration->application_aggressiveness, ['aggressive', 'moderate'])) {
            return false;
        }

        if ($match->overall_match_score < max(85, $configuration->match_threshold_percentage ?? 0)) {
            return false;
        }

        return true;
    }

    /**
     * Handle a job failure.
     *
     * Logs the failure and pushes to dead-letter monitoring channel
     * so operations can review and replay if needed.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessAutoApplications job permanently failed', [
            'configuration_id' => $this->configurationId,
            'max_per_config'   => $this->maxPerConfig,
            'attempts'         => $this->attempts(),
            'error'            => $exception->getMessage(),
            'trace'            => $exception->getTraceAsString(),
        ]);

        // Record in activity log for the user
        if ($this->configurationId) {
            $config = AgentConfiguration::find($this->configurationId);
            if ($config) {
                ApplicationActivityLog::log(
                    $config->user_id,
                    'job_failed',
                    'Auto-application processing job permanently failed after ' . $this->attempts() . ' attempts.',
                    null,
                    null,
                    [
                        'configuration_id' => $this->configurationId,
                        'error'            => $exception->getMessage(),
                    ],
                    'error'
                );
            }
        }
    }
}
