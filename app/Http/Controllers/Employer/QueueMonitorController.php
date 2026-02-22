<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueMonitorController extends Controller
{
    /**
     * Display queue monitoring dashboard.
     */
    public function index()
    {
        $queues = $this->getQueueStats();
        $failedJobs = $this->getFailedJobs();
        $recentJobs = $this->getRecentJobs();
        
        return view('employer.queue-monitor', compact('queues', 'failedJobs', 'recentJobs'));
    }

    /**
     * Get statistics for all queues.
     */
    private function getQueueStats(): array
    {
        $queueNames = ['high', 'default', 'low', 'ai-processing', 'emails', 'webhooks'];
        $stats = [];

        foreach ($queueNames as $queue) {
            $stats[] = [
                'name' => $queue,
                'size' => Queue::size($queue),
                'status' => $this->getQueueStatus($queue),
            ];
        }

        return $stats;
    }

    /**
     * Determine queue health status.
     */
    private function getQueueStatus(string $queue): string
    {
        $size = Queue::size($queue);

        if ($size > 100) {
            return 'critical';
        } elseif ($size > 50) {
            return 'warning';
        } elseif ($size > 0) {
            return 'active';
        } else {
            return 'idle';
        }
    }

    /**
     * Get failed jobs.
     */
    private function getFailedJobs()
    {
        return DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'class' => $payload['displayName'] ?? 'Unknown',
                    'exception' => substr($job->exception, 0, 200),
                    'failed_at' => $job->failed_at,
                ];
            });
    }

    /**
     * Get recent job activity.
     */
    private function getRecentJobs()
    {
        return DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as count'))
            ->groupBy('queue')
            ->get();
    }

    /**
     * Retry a failed job.
     */
    public function retry(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $this->runCommand("queue:retry {$request->id}");

        return back()->with('success', 'Job queued for retry');
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll()
    {
        $this->runCommand('queue:retry all');

        return back()->with('success', 'All failed jobs queued for retry');
    }

    /**
     * Delete a failed job.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        DB::table('failed_jobs')
            ->where('uuid', $request->id)
            ->delete();

        return back()->with('success', 'Failed job deleted');
    }

    /**
     * Flush all failed jobs.
     */
    public function flush()
    {
        $this->runCommand('queue:flush');

        return back()->with('success', 'All failed jobs deleted');
    }

    /**
     * Run artisan command.
     */
    private function runCommand(string $command): void
    {
        \Artisan::call($command);
    }

    /**
     * Get queue health check data (for API monitoring).
     */
    public function healthCheck()
    {
        $queues = $this->getQueueStats();
        $failedCount = DB::table('failed_jobs')->count();

        $health = 'healthy';
        foreach ($queues as $queue) {
            if ($queue['status'] === 'critical') {
                $health = 'critical';
                break;
            } elseif ($queue['status'] === 'warning' && $health !== 'critical') {
                $health = 'warning';
            }
        }

        return response()->json([
            'status' => $health,
            'queues' => $queues,
            'failed_jobs' => $failedCount,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
