<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class MonitorQueues extends Command
{
    protected $signature = 'queue:monitor';
    protected $description = 'Monitor queue sizes and performance';

    public function handle()
    {
        $this->info('Queue Status:');
        $this->info('=============');

        $queues = ['default', 'high', 'low', 'ai-processing', 'emails', 'webhooks'];

        foreach ($queues as $queue) {
            $size = Queue::size($queue);
            
            $status = $size > 100 ? '⚠️' : ($size > 0 ? '📋' : '✅');
            
            $this->line("{$status} {$queue}: {$size} jobs pending");
        }

        // Show failed jobs count
        $failedJobs = \DB::table('failed_jobs')->count();
        $this->newLine();
        $this->line(($failedJobs > 0 ? '❌' : '✅') . " Failed jobs: {$failedJobs}");

        if ($failedJobs > 0) {
            $this->warn("\nRun 'php artisan queue:retry all' to retry failed jobs");
        }

        return 0;
    }
}
