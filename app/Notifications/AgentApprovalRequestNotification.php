<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\JobListing;
use App\Models\JobMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Agent Approval Request Notification
 *
 * Sent when the autonomous agent requires human approval before submitting an application.
 * This implements the "human-in-the-loop" safety mechanism.
 */
class AgentApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The job match requiring approval.
     */
    protected JobMatch $jobMatch;

    /**
     * The target job listing.
     */
    protected ?JobListing $job;

    /**
     * Context about why approval is needed.
     */
    protected array $context;

    /**
     * Create a new notification instance.
     */
    public function __construct(JobMatch $jobMatch, array $context = [])
    {
        $this->jobMatch = $jobMatch;
        $this->job = $jobMatch->discoveredJob?->jobListing ?? $jobMatch->jobListing;
        $this->context = $context;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $jobTitle = $this->job?->title ?? 'Unknown Position';
        $companyName = $this->job?->company?->name ?? 'Unknown Company';
        $matchScore = round($this->jobMatch->overall_match_score ?? 0);

        $approveUrl = url("/agent/approvals/{$this->jobMatch->id}/approve");
        $rejectUrl = url("/agent/approvals/{$this->jobMatch->id}/reject");
        $reviewUrl = url("/agent/approvals/{$this->jobMatch->id}");

        $mail = (new MailMessage)
            ->subject("Action Required: Approve Application to {$jobTitle}")
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Your autonomous agent has found a job that matches your criteria and needs your approval before applying.')
            ->line('')
            ->line("**Position:** {$jobTitle}")
            ->line("**Company:** {$companyName}")
            ->line("**Match Score:** {$matchScore}%");

        // Add reason if provided
        if (!empty($this->context['reason'])) {
            $mail->line('')
                ->line("**Approval Required Because:** {$this->context['reason']}");
        }

        // Add salary info if available
        if ($this->job?->salary_min || $this->job?->salary_max) {
            $salary = $this->formatSalary($this->job->salary_min, $this->job->salary_max);
            $mail->line("**Salary:** {$salary}");
        }

        // Add location info
        if ($this->job?->location) {
            $mail->line("**Location:** {$this->job->location}");
        }

        $mail->line('')
            ->action('Review & Approve', $reviewUrl)
            ->line('')
            ->line('This approval request will expire in 24 hours. If not approved, the agent will skip this job.')
            ->line('')
            ->line('Quick Actions:')
            ->line("[Approve Application]({$approveUrl})")
            ->line("[Reject Application]({$rejectUrl})");

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'agent_approval_request',
            'job_match_id' => $this->jobMatch->id,
            'job_id' => $this->job?->id,
            'job_title' => $this->job?->title,
            'company_name' => $this->job?->company?->name,
            'match_score' => $this->jobMatch->overall_match_score,
            'context' => $this->context,
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'actions' => [
                'review' => "/agent/approvals/{$this->jobMatch->id}",
                'approve' => "/api/agent/approvals/{$this->jobMatch->id}/approve",
                'reject' => "/api/agent/approvals/{$this->jobMatch->id}/reject",
            ],
        ];
    }

    /**
     * Format salary range for display.
     */
    protected function formatSalary(?int $min, ?int $max): string
    {
        if ($min && $max) {
            return '₹' . number_format($min) . ' - ₹' . number_format($max);
        } elseif ($min) {
            return '₹' . number_format($min) . '+';
        } elseif ($max) {
            return 'Up to ₹' . number_format($max);
        }

        return 'Not specified';
    }
}
