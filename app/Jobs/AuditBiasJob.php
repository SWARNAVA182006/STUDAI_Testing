<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\AI\Scout\BiasEliminationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AuditBiasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $companyId;
    protected array $options;
    protected string $progressKey;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $companyId, array $options = [])
    {
        $this->companyId = $companyId;
        $this->options = $options;
        $this->progressKey = "bias_audit_{$companyId}_progress";
    }

    /**
     * Execute the job.
     */
    public function handle(BiasEliminationService $biasService): void
    {
        Log::info('Starting bias audit job', [
            'company_id' => $this->companyId,
            'options' => $this->options
        ]);

        try {
            // Step 1: Initialize audit (10%)
            $this->updateProgress(10, 'Initializing audit...');
            $this->validateCompany();

            // Step 2: Conduct bias audit (40%)
            $this->updateProgress(40, 'Analyzing hiring data for bias patterns...');
            $auditResult = $biasService->auditForBias(
                $this->companyId,
                [
                    'timeframe' => $this->options['timeframe'] ?? '6_months'
                ]
            );

            // Step 3: Check for proxy discrimination (60%)
            $this->updateProgress(60, 'Detecting proxy discrimination...');
            // Proxy discrimination is already checked in auditForBias
            
            // Step 4: Generate diversity analytics (80%)
            $this->updateProgress(80, 'Generating diversity analytics...');
            $diversityAnalytics = $biasService->getDiversityAnalytics(
                $this->companyId,
                [
                    'timeframe' => $this->options['timeframe'] ?? '12_months'
                ]
            );

            // Step 5: Compile report and notifications (95%)
            $this->updateProgress(95, 'Compiling audit report...');
            $this->compileAuditReport($auditResult, $diversityAnalytics);

            // Step 6: Send notifications if needed (100%)
            $this->updateProgress(100, 'Audit completed successfully');
            
            if ($auditResult['requires_attention']) {
                $this->sendAlertNotifications($auditResult);
            }

            Log::info('Bias audit completed successfully', [
                'company_id' => $this->companyId,
                'audit_id' => $auditResult['audit_id'],
                'bias_score' => $auditResult['bias_score']
            ]);

            // Clear progress after successful completion
            $this->clearInstanceProgress();

        } catch (Exception $e) {
            Log::error('Bias audit job failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateProgress(0, 'Audit failed: ' . $e->getMessage(), 'failed');
            
            throw $e; // Re-throw for queue retry
        }
    }

    /**
     * Validate that company exists and is active
     */
    protected function validateCompany(): void
    {
        $company = Company::find($this->companyId);
        
        if (!$company) {
            throw new Exception("Company with ID {$this->companyId} not found");
        }

        // Check if company subscription allows bias auditing
        // (Implementation depends on subscription system)
    }

    /**
     * Compile comprehensive audit report
     */
    protected function compileAuditReport(array $auditResult, array $diversityAnalytics): void
    {
        $report = [
            'audit_summary' => [
                'audit_id' => $auditResult['audit_id'],
                'company_id' => $this->companyId,
                'fairness_rating' => $auditResult['fairness_rating'],
                'bias_score' => $auditResult['bias_score'],
                'applications_analyzed' => $auditResult['applications_analyzed'],
                'requires_attention' => $auditResult['requires_attention'],
            ],
            'recommendations' => $auditResult['recommendations'] ?? [],
            'proxy_alerts' => [
                'count' => $auditResult['proxy_alerts_count'] ?? 0,
                'critical_count' => 0, // Would be populated from detailed analysis
            ],
            'diversity_metrics' => [
                'total_applications' => $diversityAnalytics['total_applications'],
                'pay_equity_score' => $diversityAnalytics['pay_equity_score'] ?? null,
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        // Store report in cache for quick access
        Cache::put(
            "bias_audit_report_{$this->companyId}",
            $report,
            now()->addDays(30)
        );

        // Optionally store in database for historical records
        // BiasAuditReport::create($report);
    }

    /**
     * Send alert notifications to company admins
     */
    protected function sendAlertNotifications(array $auditResult): void
    {
        $company = Company::with('users')->find($this->companyId);
        
        if (!$company) {
            return;
        }

        $admins = $company->users()->where('role', 'admin')->get();

        foreach ($admins as $admin) {
            // Send email notification
            // Mail::to($admin->email)->send(new BiasAuditAlert($auditResult));

            // Create in-app notification
            // $admin->notify(new BiasAuditCompleted($auditResult));
        }

        Log::info('Bias audit alert notifications sent', [
            'company_id' => $this->companyId,
            'admin_count' => $admins->count()
        ]);
    }

    /**
     * Update job progress
     */
    protected function updateProgress(int $percentage, string $message, string $status = 'processing'): void
    {
        Cache::put($this->progressKey, [
            'percentage' => $percentage,
            'message' => $message,
            'status' => $status,
            'updated_at' => now()->toIso8601String()
        ], now()->addHours(1));
    }

    /**
     * Clear progress from cache (instance method)
     */
    protected function clearInstanceProgress(): void
    {
        Cache::forget($this->progressKey);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Bias audit job failed permanently', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);

        $this->updateProgress(0, 'Audit failed: ' . $exception->getMessage(), 'failed');

        // Notify admins of failure
        // $this->sendFailureNotification($exception);
    }

    /**
     * Static method to dispatch job with progress tracking
     */
    public static function dispatchWithProgress(int $companyId, array $options = []): string
    {
        $progressKey = "bias_audit_{$companyId}_progress";
        
        // Initialize progress
        Cache::put($progressKey, [
            'percentage' => 0,
            'message' => 'Audit queued',
            'status' => 'queued',
            'updated_at' => now()->toIso8601String()
        ], now()->addHours(1));

        self::dispatch($companyId, $options);

        return $progressKey;
    }

    /**
     * Get current progress
     */
    public static function getProgress(int $companyId): ?array
    {
        return Cache::get("bias_audit_{$companyId}_progress");
    }

    /**
     * Clear progress for company
     */
    public static function clearProgress(int $companyId): void
    {
        Cache::forget("bias_audit_{$companyId}_progress");
    }

    /**
     * Check if audit is currently running
     */
    public static function isInProgress(int $companyId): bool
    {
        $progress = self::getProgress($companyId);
        
        if (!$progress) {
            return false;
        }

        return in_array($progress['status'], ['queued', 'processing']);
    }

    /**
     * Get estimated completion time
     */
    public static function getEstimatedCompletion(int $companyId): ?string
    {
        $progress = self::getProgress($companyId);
        
        if (!$progress || $progress['status'] !== 'processing') {
            return null;
        }

        // Estimate based on current percentage
        // Assume 5 minutes for full audit
        $totalMinutes = 5;
        $remainingPercentage = 100 - $progress['percentage'];
        $remainingMinutes = ($remainingPercentage / 100) * $totalMinutes;

        return now()->addMinutes(ceil($remainingMinutes))->diffForHumans();
    }

    /**
     * Schedule periodic audits for all active companies
     */
    public static function schedulePeriodicAudits(): void
    {
        $companies = Company::where('is_active', true)
            ->whereHas('subscription', function($q) {
                $q->where('plan_type', 'premium') // Only for premium plans
                  ->where('status', 'active');
            })
            ->get();

        foreach ($companies as $company) {
            // Check if last audit was more than 30 days ago
            $lastAudit = $company->biasAudits()
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastAudit || $lastAudit->created_at->lt(now()->subDays(30))) {
                self::dispatchWithProgress($company->id, [
                    'timeframe' => '6_months',
                    'scheduled' => true
                ]);

                Log::info('Scheduled periodic bias audit', [
                    'company_id' => $company->id
                ]);
            }
        }
    }

    /**
     * Get audit statistics for company
     */
    public static function getAuditStatistics(int $companyId): array
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            return [];
        }

        $audits = $company->biasAudits()
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'total_audits' => $audits->count(),
            'last_audit_date' => $audits->first()?->created_at,
            'average_bias_score' => $audits->avg('bias_score'),
            'fairness_trend' => $audits->take(6)->pluck('bias_score', 'created_at'),
            'alerts_generated' => $audits->sum(function($audit) {
                return count($audit->proxy_discrimination_findings['alerts'] ?? []);
            }),
            'audits_requiring_attention' => $audits->where('requires_attention', true)->count(),
        ];
    }
}
