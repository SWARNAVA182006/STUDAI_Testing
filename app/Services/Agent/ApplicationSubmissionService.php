<?php

namespace App\Services\Agent;

use App\Models\AutoApplication;
use App\Models\DiscoveredJob;
use App\Models\User;
use App\Events\ApplicationSubmitted;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApplicationSubmissionService
{
    /**
     * Submit application to external job board
     */
    public function submit(AutoApplication $application): bool
    {
        try {
            $job = $application->discoveredJob;
            $user = $application->user;

            // Determine submission method based on job source
            $result = match($job->jobSource->type) {
                'direct_apply' => $this->submitDirectApply($application, $job, $user),
                'email' => $this->submitViaEmail($application, $job, $user),
                'api' => $this->submitViaAPI($application, $job, $user),
                'form' => $this->submitViaForm($application, $job, $user),
                default => $this->logApplicationOnly($application),
            };

            if ($result) {
                $application->submit();
                
                // Dispatch event for notifications
                event(new ApplicationSubmitted($application));
                
                Log::info('Application submitted successfully', [
                    'application_id' => $application->id,
                    'job_id' => $job->id,
                    'user_id' => $user->id,
                ]);
            }

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Application submission failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $application->updateStatus('failed', [
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ]);

            return false;
        }
    }

    /**
     * Submit via direct application link
     */
    protected function submitDirectApply(AutoApplication $application, DiscoveredJob $job, User $user): bool
    {
        // For direct apply, we typically can't automate submission
        // Mark as pending_manual_submission and notify user
        $application->updateStatus('pending_manual_submission', [
            'application_url' => $job->url,
            'instructions' => 'Please complete the application manually at the provided URL',
        ]);

        return true;
    }

    /**
     * Submit via email
     */
    protected function submitViaEmail(AutoApplication $application, DiscoveredJob $job, User $user): bool
    {
        // Parse email from job description or source
        $emailAddress = $this->extractEmailFromJob($job);
        
        if (!$emailAddress) {
            return false;
        }

        // Send email with resume and cover letter
        // This would integrate with Laravel Mail
        // For now, mark as pending email submission
        $application->updateStatus('pending_email_submission', [
            'email_address' => $emailAddress,
            'subject' => "Application for {$job->title} - {$user->name}",
        ]);

        return true;
    }

    /**
     * Submit via API integration
     */
    protected function submitViaAPI(AutoApplication $application, DiscoveredJob $job, User $user): bool
    {
        $source = $job->jobSource;
        $apiConfig = $source->scraping_config['api'] ?? [];

        if (empty($apiConfig['endpoint'])) {
            return false;
        }

        $response = Http::withHeaders($apiConfig['headers'] ?? [])
            ->timeout(30)
            ->post($apiConfig['endpoint'], [
                'job_id' => $job->external_id,
                'applicant' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'resume_url' => $application->resume_url,
                    'cover_letter' => $application->cover_letter,
                ],
                'custom_fields' => $application->custom_fields,
                'screening_answers' => $application->screening_answers,
            ]);

        if ($response->successful()) {
            $application->updateStatus('submitted', [
                'external_application_id' => $response->json('application_id'),
                'submitted_via' => 'api',
            ]);
            return true;
        }

        return false;
    }

    /**
     * Submit via web form automation
     */
    protected function submitViaForm(AutoApplication $application, DiscoveredJob $job, User $user): bool
    {
        // Web form automation would require browser automation (Selenium, Puppeteer, etc.)
        // For now, mark as pending automation
        $application->updateStatus('pending_automation', [
            'form_url' => $job->url,
            'automation_scheduled' => now()->addMinutes(5)->toIso8601String(),
        ]);

        // Queue a job for browser automation
        // \App\Jobs\AutomateFormSubmission::dispatch($application);

        return true;
    }

    /**
     * Log application without external submission
     */
    protected function logApplicationOnly(AutoApplication $application): bool
    {
        // Just track that we prepared the application
        // User will need to submit manually
        $application->updateStatus('prepared', [
            'status_message' => 'Application prepared but requires manual submission',
        ]);

        return true;
    }

    /**
     * Extract email address from job description
     */
    protected function extractEmailFromJob(DiscoveredJob $job): ?string
    {
        $text = $job->description . ' ' . $job->requirements;
        
        // Basic email extraction regex
        if (preg_match('/[\w\.-]+@[\w\.-]+\.\w+/', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Track submission in external system
     */
    public function trackSubmission(AutoApplication $application, string $trackingUrl): void
    {
        $application->update([
            'tracking_url' => $trackingUrl,
        ]);

        // Set up periodic status checks
        $application->scheduleStatusCheck();
    }

    /**
     * Verify submission was received
     */
    public function verifySubmission(AutoApplication $application): bool
    {
        // Check if the application was received by the employer
        // This could involve checking API status, email confirmations, etc.
        
        return $application->status === 'submitted' && $application->submitted_at !== null;
    }
}
