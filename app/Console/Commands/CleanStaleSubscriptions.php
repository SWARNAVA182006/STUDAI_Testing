<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use Illuminate\Console\Command;

class CleanStaleSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:clean-stale {--days=90 : Number of days to consider a subscription stale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove stale push subscriptions that haven\'t been used';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning push subscriptions not used in {$days} days...");

        $staleCount = PushSubscription::where('last_used_at', '<=', now()->subDays($days))
            ->orWhereNull('last_used_at')
            ->count();

        if ($staleCount === 0) {
            $this->info('No stale subscriptions found.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$staleCount} stale subscription(s).");

        if ($this->confirm('Do you want to delete them?', true)) {
            $deleted = PushSubscription::where('last_used_at', '<=', now()->subDays($days))
                ->orWhereNull('last_used_at')
                ->delete();

            $this->info("Deleted {$deleted} stale subscription(s).");
        } else {
            $this->info('Operation cancelled.');
        }

        return Command::SUCCESS;
    }
}
