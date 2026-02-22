<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalModelDriftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $driftResults
     * @param float $avgDrift
     */
    public function __construct(
        private readonly array $driftResults,
        private readonly float $avgDrift,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $testCount = count($this->driftResults);
        $driftPercent = round($this->avgDrift * 100, 1);

        return (new MailMessage())
            ->subject("[CRITICAL] AI Model Drift Detected — {$driftPercent}% Average Drift")
            ->error()
            ->greeting('AI Model Drift Alert')
            ->line("Critical drift detected across **{$testCount}** golden tests.")
            ->line("**Average Drift:** {$driftPercent}%")
            ->line('This means AI model outputs have deviated significantly from the established baselines. Immediate investigation is recommended.')
            ->line('**Affected Tests:**')
            ->lines($this->formatDriftLines())
            ->action('View Golden Tests in Admin', url('/admin/ai-golden-tests'))
            ->line('This alert requires immediate attention to ensure AI quality standards are maintained.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'critical_model_drift',
            'avg_drift' => $this->avgDrift,
            'test_count' => count($this->driftResults),
            'drift_results' => array_map(fn (array $result) => [
                'test_name' => $result['test_name'] ?? 'Unknown',
                'similarity' => $result['similarity'] ?? 0,
                'drift' => $result['drift'] ?? 0,
            ], $this->driftResults),
            'severity' => $this->avgDrift > 0.3 ? 'critical' : 'warning',
            'detected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function formatDriftLines(): array
    {
        $lines = [];

        foreach (array_slice($this->driftResults, 0, 10) as $result) {
            $name = $result['test_name'] ?? 'Unknown';
            $drift = round(($result['drift'] ?? 0) * 100, 1);
            $lines[] = "- **{$name}**: {$drift}% drift";
        }

        if (count($this->driftResults) > 10) {
            $remaining = count($this->driftResults) - 10;
            $lines[] = "- ...and {$remaining} more affected tests";
        }

        return $lines;
    }
}
