<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BiasAuditCompleted;
use App\Events\CandidateShortlisted;
use App\Events\PredictionGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogScoutActivity implements ShouldQueue
{
    public string $queue = 'default';

    public function handleCandidateShortlisted(CandidateShortlisted $event): void
    {
        $this->logActivity('candidate_shortlisted', [
            'employer_id' => $event->employer->id,
            'application_id' => $event->application->id,
            'match_score' => $event->matchScore,
            'reasons' => $event->reasons,
        ]);

        // Notify the candidate that they were shortlisted
        $candidate = $event->application->user ?? null;
        if ($candidate) {
            try {
                $candidate->notify(new \App\Notifications\CandidateShortlistedNotification(
                    $event->application,
                    $event->matchScore
                ));
            } catch (\Exception $e) {
                Log::warning('Failed to send shortlisted notification', ['error' => $e->getMessage()]);
            }
        }
    }

    public function handlePredictionGenerated(PredictionGenerated $event): void
    {
        $this->logActivity('prediction_generated', [
            'employer_id' => $event->employer->id,
            'application_id' => $event->application->id,
            'prediction_type' => $event->predictionType,
            'confidence' => $event->results['confidence'] ?? null,
        ]);
    }

    public function handleBiasAuditCompleted(BiasAuditCompleted $event): void
    {
        $this->logActivity('bias_audit_completed', [
            'employer_id' => $event->employer->id,
            'job_id' => $event->jobId,
            'fairness_score' => $event->fairnessScore,
            'issues_found' => count($event->auditResults['issues'] ?? []),
        ]);

        // Alert if fairness score is below threshold
        if ($event->fairnessScore < 0.7) {
            Log::warning('Low fairness score detected in bias audit', [
                'employer_id' => $event->employer->id,
                'job_id' => $event->jobId,
                'fairness_score' => $event->fairnessScore,
            ]);
        }
    }

    protected function logActivity(string $action, array $data): void
    {
        DB::table('activity_logs')->insert([
            'action' => $action,
            'data' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            CandidateShortlisted::class => 'handleCandidateShortlisted',
            PredictionGenerated::class => 'handlePredictionGenerated',
            BiasAuditCompleted::class => 'handleBiasAuditCompleted',
        ];
    }
}
