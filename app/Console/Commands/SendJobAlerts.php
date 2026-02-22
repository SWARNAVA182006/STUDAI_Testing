<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobAlert;
use App\Services\AI\JobMatchingService;
use App\Notifications\JobAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendJobAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:send-alerts {--force : Send alerts regardless of last sent time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send job alert notifications to users based on their preferences';

    protected JobMatchingService $jobMatchingService;

    public function __construct(JobMatchingService $jobMatchingService)
    {
        parent::__construct();
        $this->jobMatchingService = $jobMatchingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting job alerts process...');

        // Get alerts due for notification
        $query = JobAlert::query()
            ->with('user.profile')
            ->active();

        if (!$this->option('force')) {
            $query->dueForNotification();
        }

        $alerts = $query->get();

        if ($alerts->isEmpty()) {
            $this->info('No alerts due for notification.');
            return 0;
        }

        $this->info("Found {$alerts->count()} alerts to process.");

        $sentCount = 0;
        $errorCount = 0;
        $matchedJobsCount = 0;

        // Group alerts by user to send one email per user
        $alertsByUser = $alerts->groupBy('user_id');

        $this->withProgressBar($alertsByUser, function ($userAlerts, $userId) use (&$sentCount, &$errorCount, &$matchedJobsCount) {
            $user = $userAlerts->first()->user;
            $allMatchedJobs = collect();

            foreach ($userAlerts as $alert) {
                try {
                    // Get jobs published since last alert or last 7 days
                    $since = $alert->last_sent_at ?? now()->subDays(7);
                    
                    $jobs = Job::query()
                        ->with('company')
                        ->active()
                        ->where('published_at', '>', $since)
                        ->get()
                        ->filter(fn($job) => $alert->matchesJob($job));

                    if ($jobs->isNotEmpty()) {
                        // Calculate match scores for each job
                        $jobsWithScores = $jobs->map(function ($job) use ($user, $alert) {
                            $profile = $user->profile;
                            $matchScore = $profile 
                                ? $this->jobMatchingService->calculateMatchScore($profile, $job)
                                : ['overall_score' => 50];

                            return [
                                'job' => $job,
                                'match_score' => $matchScore['overall_score'],
                                'match_analysis' => $matchScore,
                                'alert_name' => $alert->name,
                            ];
                        })->sortByDesc('match_score');

                        $allMatchedJobs = $allMatchedJobs->merge($jobsWithScores);
                        $matchedJobsCount += $jobs->count();
                    }

                    // Mark alert as sent
                    $alert->markAsSent();

                } catch (\Exception $e) {
                    $this->error("Error processing alert {$alert->id}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            // Send notification if there are matched jobs
            if ($allMatchedJobs->isNotEmpty()) {
                try {
                    // Take top 10 matches to avoid overwhelming the user
                    $topMatches = $allMatchedJobs->unique('job.id')->take(10);
                    
                    $user->notify(new JobAlertNotification($topMatches->toArray()));
                    $sentCount++;
                    
                } catch (\Exception $e) {
                    $this->error("Error sending notification to user {$user->id}: " . $e->getMessage());
                    $errorCount++;
                }
            }
        });

        $this->newLine(2);
        $this->info("Job alerts process completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Alerts Processed', $alerts->count()],
                ['Users Notified', $sentCount],
                ['Matched Jobs Found', $matchedJobsCount],
                ['Errors', $errorCount],
            ]
        );

        return 0;
    }
}

