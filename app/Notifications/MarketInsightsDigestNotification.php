<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Market Insights Digest Notification
 * 
 * Weekly email digest with personalized market insights, trending roles,
 * skill recommendations, and market position updates.
 */
class MarketInsightsDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Personalized insights data
     */
    protected array $insights;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $insights)
    {
        $this->insights = $insights;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $readinessScore = $this->insights['readiness_score'];
        $readinessChange = $this->insights['readiness_change'];
        
        $mail = (new MailMessage)
            ->subject('Your Weekly Market Intelligence Digest 📊')
            ->greeting("Hi {$this->insights['user_name']}!")
            ->line("Here's your personalized market intelligence update for this week.");
        
        // Readiness Score Update
        $changeEmoji = $readinessChange > 0 ? '📈' : ($readinessChange < 0 ? '📉' : '➡️');
        $mail->line("**Your Market Readiness Score:** {$readinessScore}/100 {$changeEmoji}");
        
        if ($readinessChange != 0) {
            $changeText = $readinessChange > 0 ? 'increased' : 'decreased';
            $mail->line("Your score {$changeText} by " . abs(round($readinessChange, 1)) . " points this week!");
        }
        
        // Market Position
        $percentile = $this->insights['percentile_ranking'];
        $mail->line("**Market Position:** Top " . (100 - $percentile) . "% of job seekers in your field");
        
        // Competitive Advantages
        if (!empty($this->insights['competitive_advantages'])) {
            $mail->line("**Your Competitive Advantages:**");
            foreach (array_slice($this->insights['competitive_advantages'], 0, 3) as $advantage) {
                $mail->line("✓ " . $advantage);
            }
        }
        
        // Skill Gaps
        if (!empty($this->insights['skill_gaps'])) {
            $mail->line("**Skills to Develop:**");
            foreach (array_slice($this->insights['skill_gaps'], 0, 3) as $gap) {
                $skillName = is_array($gap) ? $gap['skill'] : $gap;
                $mail->line("• " . $skillName);
            }
        }
        
        // Trending Roles
        if (!empty($this->insights['trending_roles'])) {
            $mail->line("**Trending Roles Matching Your Profile:**");
            foreach (array_slice($this->insights['trending_roles'], 0, 3) as $role) {
                $mail->line("🔥 {$role['title']} ({$role['job_count']} openings)");
            }
        }
        
        // Market Shifts
        if (!empty($this->insights['global_shifts'])) {
            $mail->line("**This Week's Market Shifts:**");
            foreach (array_slice($this->insights['global_shifts'], 0, 2) as $shift) {
                $mail->line($shift['message']);
            }
        }
        
        // Recommended Actions
        if (!empty($this->insights['recommended_actions'])) {
            $mail->line("**Recommended Actions:**");
            foreach (array_slice($this->insights['recommended_actions'], 0, 3) as $action) {
                $actionText = is_array($action) ? $action['action'] : $action;
                $mail->line("→ " . $actionText);
            }
        }
        
        // CTA
        $mail->action('View Full Market Intelligence', url('/market/positioning'));
        
        $mail->line('Stay ahead of the market! 🚀');
        
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'readiness_score' => $this->insights['readiness_score'],
            'percentile_ranking' => $this->insights['percentile_ranking'],
            'trending_roles_count' => count($this->insights['trending_roles'] ?? []),
        ];
    }
}
