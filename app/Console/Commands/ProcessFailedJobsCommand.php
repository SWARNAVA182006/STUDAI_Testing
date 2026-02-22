<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Process Failed Jobs Command
 *
 * Monitors failed jobs and takes appropriate action:
 * - Sends alerts when failure thresholds are exceeded
 * - Provides retry capability for failed jobs
 * - Cleans up old failed job records
 *
 * Usage:
 *   php artisan queue:process-failed          # Check and alert on failed jobs
 *   php artisan queue:process-failed --retry  # Retry all failed jobs
 *   php artisan queue:process-failed --cleanup # Delete old failed jobs
 */
class ProcessFailedJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:process-failed
                            {--retry : Retry all failed jobs}
                            {--cleanup : Clean up old failed jobs}
                            {--threshold=5 : Alert threshold for failed jobs}
                            {--hours=1 : Time window in hours for failure count}
                            {--retention=30 : Days to retain failed jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process failed jobs: monitor, alert, retry, and cleanup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('retry')) {
            return $this->retryFailedJobs();
        }

        if ($this->option('cleanup')) {
            return $this->cleanupOldJobs();
        }

        return $this->checkAndAlert();
    }

    /**
     * Check failed job count and send alert if threshold exceeded.
     */
    protected function checkAndAlert(): int
    {
        $threshold = (int) $this->option('threshold');
        $hours = (int) $this->option('hours');

        $recentFailedCount = $this->getRecentFailedCount($hours);
        $totalFailedCount = $this->getTotalFailedCount();

        $this->info("Failed jobs in last {$hours} hour(s): {$recentFailedCount}");
        $this->info("Total failed jobs: {$totalFailedCount}");

        if ($recentFailedCount >= $threshold) {
            $this->warn("Threshold exceeded! Sending alert...");
            $this->sendAlert($recentFailedCount, $hours, $totalFailedCount);
            return Command::SUCCESS;
        }

        $this->info("Failed job count is below threshold ({$threshold}).");

        // Also check for specific critical job failures
        $this->checkCriticalJobs();

        return Command::SUCCESS;
    }

    /**
     * Retry all failed jobs.
     */
    protected function retryFailedJobs(): int
    {
        $failedJobs = DB::table('failed_jobs')->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed jobs to retry.');
            return Command::SUCCESS;
        }

        $this->info("Found {$failedJobs->count()} failed jobs to retry.");

        $retried = 0;
        $errors = 0;

        foreach ($failedJobs as $job) {
            try {
                $this->call('queue:retry', ['id' => [$job->uuid]]);
                $retried++;
            } catch (\Exception $e) {
                $this->error("Failed to retry job {$job->uuid}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Retried: {$retried}, Errors: {$errors}");

        Log::info('Failed jobs retry completed', [
            'retried' => $retried,
            'errors' => $errors,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Clean up old failed jobs beyond retention period.
     */
    protected function cleanupOldJobs(): int
    {
        $retentionDays = (int) $this->option('retention');
        $cutoffDate = now()->subDays($retentionDays);

        $count = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->count();

        if ($count === 0) {
            $this->info('No old failed jobs to clean up.');
            return Command::SUCCESS;
        }

        if (!$this->confirm("Delete {$count} failed jobs older than {$retentionDays} days?")) {
            $this->info('Cleanup cancelled.');
            return Command::SUCCESS;
        }

        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deleted} old failed jobs.");

        Log::info('Failed jobs cleanup completed', [
            'deleted' => $deleted,
            'retention_days' => $retentionDays,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Get count of failed jobs in the last N hours.
     */
    protected function getRecentFailedCount(int $hours): int
    {
        return DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHours($hours))
            ->count();
    }

    /**
     * Get total count of failed jobs.
     */
    protected function getTotalFailedCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    /**
     * Check for failures in critical job types.
     */
    protected function checkCriticalJobs(): void
    {
        $criticalJobPatterns = [
            'Payment' => 'payment',
            'Subscription' => 'subscription',
            'Application' => 'ProcessAutoApplications',
        ];

        foreach ($criticalJobPatterns as $name => $pattern) {
            $count = DB::table('failed_jobs')
                ->where('payload', 'like', "%{$pattern}%")
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();

            if ($count > 0) {
                $this->warn("  - {$name} jobs failed in last 24h: {$count}");

                Log::warning("Critical job failures detected", [
                    'job_type' => $name,
                    'count' => $count,
                    'period' => '24h',
                ]);
            }
        }
    }

    /**
     * Send alert notification about failed jobs.
     */
    protected function sendAlert(int $recentCount, int $hours, int $totalCount): void
    {
        $message = $this->buildAlertMessage($recentCount, $hours, $totalCount);

        // Log the alert
        Log::critical('Failed jobs threshold exceeded', [
            'recent_count' => $recentCount,
            'hours' => $hours,
            'total_count' => $totalCount,
            'threshold' => $this->option('threshold'),
        ]);

        // Get failed job details for context
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get(['uuid', 'queue', 'payload', 'exception', 'failed_at']);

        // Send to configured channels
        $this->sendToSlack($message, $failedJobs);
        $this->sendToEmail($message, $failedJobs);
    }

    /**
     * Build alert message.
     */
    protected function buildAlertMessage(int $recentCount, int $hours, int $totalCount): string
    {
        $appName = config('app.name', 'StudAI Career');
        $env = config('app.env', 'unknown');

        return <<<MSG
⚠️ QUEUE ALERT: Failed Jobs Threshold Exceeded

Environment: {$env}
Application: {$appName}

Summary:
- Failed jobs in last {$hours} hour(s): {$recentCount}
- Total failed jobs: {$totalCount}
- Alert threshold: {$this->option('threshold')}

Action Required:
1. Check Horizon dashboard at /horizon
2. Review failed job exceptions
3. Consider running: php artisan queue:process-failed --retry
MSG;
    }

    /**
     * Send alert to Slack webhook.
     */
    protected function sendToSlack(string $message, $failedJobs): void
    {
        $webhookUrl = config('services.slack.webhook_url');

        if (empty($webhookUrl)) {
            $this->line('Slack webhook not configured, skipping.');
            return;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $client->post($webhookUrl, [
                'json' => [
                    'text' => $message,
                    'attachments' => [
                        [
                            'title' => 'Recent Failed Jobs',
                            'text' => $failedJobs->map(function ($job) {
                                $payload = json_decode($job->payload, true);
                                $jobName = $payload['displayName'] ?? 'Unknown';
                                return "• {$jobName} ({$job->queue}) - {$job->failed_at}";
                            })->implode("\n"),
                            'color' => 'danger',
                        ],
                    ],
                ],
            ]);

            $this->info('Slack notification sent.');
        } catch (\Exception $e) {
            $this->error("Failed to send Slack notification: {$e->getMessage()}");
        }
    }

    /**
     * Send alert to configured email addresses.
     */
    protected function sendToEmail(string $message, $failedJobs): void
    {
        $recipients = config('queue.failed.notification_emails', []);

        if (empty($recipients)) {
            $this->line('Email notifications not configured, skipping.');
            return;
        }

        try {
            Mail::raw($message, function ($mail) use ($recipients) {
                $mail->to($recipients)
                    ->subject('[ALERT] Queue Failed Jobs Threshold Exceeded - ' . config('app.name'));
            });

            $this->info('Email notification sent to: ' . implode(', ', $recipients));
        } catch (\Exception $e) {
            $this->error("Failed to send email notification: {$e->getMessage()}");
        }
    }
}
