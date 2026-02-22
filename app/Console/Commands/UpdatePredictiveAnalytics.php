<?php

namespace App\Console\Commands;

use App\Jobs\UpdatePredictionsJob;
use Illuminate\Console\Command;

class UpdatePredictiveAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:update-predictions 
                            {--application= : Update predictions for specific application ID}
                            {--batch= : Comma-separated application IDs to update}
                            {--all : Update predictions for all active applications}
                            {--force : Force refresh (bypass cache)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update predictive analytics for applications (success probability, tenure forecast, productivity, flight risk, etc.)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🔮 S.C.O.U.T. Predictive Analytics Update');
        $this->newLine();

        $forceRefresh = $this->option('force');

        if ($this->option('all')) {
            return $this->updateAllActiveApplications($forceRefresh);
        }

        if ($applicationId = $this->option('application')) {
            return $this->updateSingleApplication((int) $applicationId, $forceRefresh);
        }

        if ($batch = $this->option('batch')) {
            return $this->updateBatchApplications($batch, $forceRefresh);
        }

        $this->error('❌ Please specify --application, --batch, or --all');
        return self::FAILURE;
    }

    /**
     * Update predictions for a single application.
     *
     * @param int $applicationId
     * @param bool $forceRefresh
     * @return int
     */
    protected function updateSingleApplication(int $applicationId, bool $forceRefresh): int
    {
        $application = \App\Models\Application::with(['user', 'job.company'])
            ->find($applicationId);

        if (!$application) {
            $this->error("❌ Application #{$applicationId} not found");
            return self::FAILURE;
        }

        $this->info("📊 Updating predictions for Application #{$applicationId}");
        $this->line("   Candidate: {$application->user->name}");
        $this->line("   Position: {$application->job->title}");
        $this->line("   Company: {$application->job->company->name}");
        $this->newLine();

        UpdatePredictionsJob::dispatch($application, $forceRefresh);

        $this->info('✅ Prediction update job dispatched successfully');
        $this->line('   Monitor progress: php artisan queue:work --queue=predictions');
        
        return self::SUCCESS;
    }

    /**
     * Update predictions for a batch of applications.
     *
     * @param string $batch
     * @param bool $forceRefresh
     * @return int
     */
    protected function updateBatchApplications(string $batch, bool $forceRefresh): int
    {
        $applicationIds = array_map('intval', explode(',', $batch));
        $count = count($applicationIds);

        $this->info("📊 Updating predictions for {$count} applications");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        UpdatePredictionsJob::dispatchBatch($applicationIds, $forceRefresh);

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ {$count} prediction update jobs dispatched successfully");
        $this->line('   Monitor progress: php artisan queue:work --queue=predictions');
        
        return self::SUCCESS;
    }

    /**
     * Update predictions for all active applications.
     *
     * @param bool $forceRefresh
     * @return int
     */
    protected function updateAllActiveApplications(bool $forceRefresh): int
    {
        $this->info('📊 Updating predictions for all active applications');
        $this->newLine();

        $count = \App\Models\Application::query()
            ->whereIn('status', ['under_review', 'interviewing', 'offer_extended'])
            ->whereHas('job', function ($query) {
                $query->where('status', 'published');
            })
            ->count();

        if ($count === 0) {
            $this->warn('⚠️  No active applications found');
            return self::SUCCESS;
        }

        $this->info("Found {$count} active applications to update");
        $this->newLine();

        if (!$this->confirm('Do you want to continue?', true)) {
            $this->warn('Operation cancelled');
            return self::SUCCESS;
        }

        $this->line('Dispatching jobs...');
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        UpdatePredictionsJob::dispatchForAllActive($forceRefresh);

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ {$count} prediction update jobs dispatched successfully");
        $this->line('   Monitor progress: php artisan queue:work --queue=predictions');
        $this->line('   View jobs: php artisan horizon (or check Horizon dashboard)');
        
        return self::SUCCESS;
    }
}
