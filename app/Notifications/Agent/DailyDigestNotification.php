<?php

namespace App\Notifications\Agent;

use App\Models\AgentConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Daily Digest Notification
 * 
 * Sends a comprehensive daily summary of agent activity to users.
 * Includes applications submitted, outcomes received, and performance metrics.
 */
class DailyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AgentConfiguration $config,
        public $applications,
        public $newOutcomes,
        public array $stats
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Daily Agent Report - ' . now()->format('M d, Y'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Here\'s your autonomous agent activity summary for yesterday:');

        // Applications Summary
        if ($this->applications->count() > 0) {
            $message->line('## Applications Submitted');
            $message->line('**' . $this->applications->count() . ' new applications** were submitted yesterday.');
            
            // Show top 5 applications
            $topApplications = $this->applications->take(5);
            foreach ($topApplications as $application) {
                $message->line('✓ **' . $application->job_title . '** at ' . $application->company_name . 
                             ($application->match_score ? ' (' . round($application->match_score) . '% match)' : ''));
            }

            if ($this->applications->count() > 5) {
                $message->line('_And ' . ($this->applications->count() - 5) . ' more..._');
            }
        } else {
            $message->line('No new applications were submitted yesterday.');
        }

        // New Outcomes
        if ($this->newOutcomes->count() > 0) {
            $message->line('---');
            $message->line('## New Responses');
            $message->line('You received **' . $this->newOutcomes->count() . ' new responses** to your applications:');
            
            // Group by outcome
            $outcomeCounts = $this->newOutcomes->groupBy('outcome')->map->count();
            
            if (isset($outcomeCounts['interview_scheduled'])) {
                $message->line('🎉 **' . $outcomeCounts['interview_scheduled'] . ' interview' . ($outcomeCounts['interview_scheduled'] > 1 ? 's' : '') . ' scheduled!**');
            }
            if (isset($outcomeCounts['offer_received'])) {
                $message->line('🎊 **' . $outcomeCounts['offer_received'] . ' offer' . ($outcomeCounts['offer_received'] > 1 ? 's' : '') . ' received!**');
            }
            if (isset($outcomeCounts['rejected'])) {
                $message->line('❌ ' . $outcomeCounts['rejected'] . ' rejection' . ($outcomeCounts['rejected'] > 1 ? 's' : ''));
            }
            if (isset($outcomeCounts['no_response'])) {
                $message->line('⏳ ' . $outcomeCounts['no_response'] . ' no response');
            }
        }

        // Performance Metrics
        $message->line('---');
        $message->line('## Overall Performance');
        $message->line('📊 **Success Rate:** ' . $this->stats['success_rate'] . '%');
        $message->line('📈 **Total Applications:** ' . $this->stats['total_applications']);
        $message->line('✅ **Successful Outcomes:** ' . $this->stats['successful_outcomes'] . ' (interviews, offers, accepted)');
        $message->line('⏱️ **Average Response Time:** ' . ($this->stats['avg_days_to_response'] ?? 'N/A') . ' days');
        
        // This Week/Month Stats
        if ($this->stats['applications_this_week'] > 0 || $this->stats['applications_this_month'] > 0) {
            $message->line('📅 **This Week:** ' . $this->stats['applications_this_week'] . ' applications');
            $message->line('📆 **This Month:** ' . $this->stats['applications_this_month'] . ' applications');
        }

        // Pending Applications
        if ($this->stats['pending_applications'] > 0) {
            $message->line('---');
            $message->line('⏳ You have **' . $this->stats['pending_applications'] . ' applications pending** responses.');
        }

        // Action Button
        $message->action('View Dashboard', route('agent.dashboard'));

        // Footer Tips
        if ($this->stats['success_rate'] < 20 && $this->stats['total_applications'] >= 10) {
            $message->line('---');
            $message->line('💡 **Tip:** Your success rate is lower than average. Consider adjusting your job search criteria or match threshold for better results.');
        } elseif ($this->stats['success_rate'] >= 50) {
            $message->line('---');
            $message->line('🌟 **Great work!** Your agent is performing excellently with a ' . $this->stats['success_rate'] . '% success rate!');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'daily_digest',
            'config_id' => $this->config->id,
            'applications_count' => $this->applications->count(),
            'new_outcomes_count' => $this->newOutcomes->count(),
            'success_rate' => $this->stats['success_rate'],
            'total_applications' => $this->stats['total_applications'],
            'pending_applications' => $this->stats['pending_applications'],
            'successful_outcomes' => $this->stats['successful_outcomes'],
        ];
    }
}
