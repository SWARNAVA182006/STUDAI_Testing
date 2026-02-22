<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Filament\Notifications\Notification;

class QueueMonitor extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static \UnitEnum|string|null $navigationGroup = 'System & Tools';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.queue-monitor';

    protected static ?string $title = 'Queue Monitor';

    protected static ?string $navigationLabel = 'Queue Monitor';

    public function getViewData(): array
    {
        return [
            'queueStats' => $this->getQueueStats(),
            'failedJobs' => $this->getFailedJobs(),
            'recentJobs' => $this->getRecentJobs(),
            'queueSizes' => $this->getQueueSizes(),
        ];
    }

    protected function getQueueStats(): array
    {
        try {
            $failed = \DB::table('failed_jobs')->count();
            $pending = 0; // Requires queue inspection
            $processed = 0; // Requires job tracking

            return [
                'failed' => $failed,
                'pending' => $pending,
                'processed' => $processed,
                'total' => $failed + $pending + $processed,
            ];
        } catch (\Exception $e) {
            return [
                'failed' => 0,
                'pending' => 0,
                'processed' => 0,
                'total' => 0,
                'error' => 'Failed jobs table not found',
            ];
        }
    }

    protected function getFailedJobs(): array
    {
        try {
            return \DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(20)
                ->get()
                ->map(function($job) {
                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid ?? 'N/A',
                        'connection' => $job->connection,
                        'queue' => $job->queue,
                        'payload' => $this->extractJobName($job->payload),
                        'exception' => $this->extractExceptionMessage($job->exception),
                        'failed_at' => \Carbon\Carbon::parse($job->failed_at)->format('d M Y, H:i'),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function extractJobName($payload): string
    {
        $decoded = json_decode($payload, true);
        if (isset($decoded['displayName'])) {
            return $decoded['displayName'];
        }
        if (isset($decoded['data']['commandName'])) {
            return class_basename($decoded['data']['commandName']);
        }
        return 'Unknown Job';
    }

    protected function extractExceptionMessage($exception): string
    {
        if (empty($exception)) {
            return 'No exception message';
        }
        $lines = explode("\n", $exception);
        return strlen($lines[0]) > 150 ? substr($lines[0], 0, 150) . '...' : $lines[0];
    }

    protected function getRecentJobs(): array
    {
        // Placeholder - requires job tracking table
        // This would need a jobs_processed table to track completed jobs
        return [];
    }

    protected function getQueueSizes(): array
    {
        try {
            $queues = ['default', 'high', 'low', 'emails', 'notifications'];
            $sizes = [];

            foreach ($queues as $queue) {
                try {
                    // For database queue driver
                    $size = \DB::table('jobs')->where('queue', $queue)->count();
                    $sizes[$queue] = $size;
                } catch (\Exception $e) {
                    $sizes[$queue] = 0;
                }
            }

            return $sizes;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function retryFailedJob($jobId): void
    {
        try {
            Artisan::call('queue:retry', ['id' => [$jobId]]);
            Notification::make()
                ->title('Job Retried')
                ->success()
                ->body("Job #{$jobId} has been queued for retry.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Retry Failed')
                ->danger()
                ->body("Failed to retry job: " . $e->getMessage())
                ->send();
        }
    }

    public function deleteFailedJob($jobId): void
    {
        try {
            Artisan::call('queue:forget', ['id' => $jobId]);
            Notification::make()
                ->title('Job Deleted')
                ->success()
                ->body("Failed job #{$jobId} has been deleted.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Delete Failed')
                ->danger()
                ->body("Failed to delete job: " . $e->getMessage())
                ->send();
        }
    }

    public function retryAllFailedJobs(): void
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            Notification::make()
                ->title('All Jobs Retried')
                ->success()
                ->body('All failed jobs have been queued for retry.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Retry Failed')
                ->danger()
                ->body("Failed to retry jobs: " . $e->getMessage())
                ->send();
        }
    }

    public function flushFailedJobs(): void
    {
        try {
            Artisan::call('queue:flush');
            Notification::make()
                ->title('Failed Jobs Cleared')
                ->success()
                ->body('All failed jobs have been deleted.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Flush Failed')
                ->danger()
                ->body("Failed to flush jobs: " . $e->getMessage())
                ->send();
        }
    }

    public function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            Notification::make()
                ->title('Cache Cleared')
                ->success()
                ->body('All caches have been cleared successfully.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Clear Failed')
                ->danger()
                ->body("Failed to clear cache: " . $e->getMessage())
                ->send();
        }
    }
}
