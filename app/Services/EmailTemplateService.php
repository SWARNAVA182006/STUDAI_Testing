<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailAiCustomization;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateAnalytics;
use App\Models\EmailTemplateCategory;
use App\Models\EmailTemplateVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Traits\InteractsWithAI;

class EmailTemplateService
{
    use InteractsWithAI;
    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Available template variables.
     */
    protected array $availableVariables = [
        'candidate_name' => 'Full name of the candidate',
        'candidate_first_name' => 'First name of the candidate',
        'candidate_email' => 'Email address of the candidate',
        'job_title' => 'Title of the job position',
        'company_name' => 'Name of the company',
        'company_logo' => 'URL to company logo',
        'interviewer_name' => 'Name of the interviewer',
        'interview_date' => 'Date of the interview',
        'interview_time' => 'Time of the interview',
        'interview_location' => 'Location/link for interview',
        'interview_type' => 'Type of interview (phone, video, in-person)',
        'offer_salary' => 'Offered salary amount',
        'offer_start_date' => 'Proposed start date',
        'offer_deadline' => 'Deadline to accept offer',
        'sender_name' => 'Name of the person sending',
        'sender_title' => 'Job title of the sender',
        'sender_email' => 'Email of the sender',
        'sender_phone' => 'Phone number of the sender',
        'application_date' => 'Date of application',
        'rejection_reason' => 'Reason for rejection (optional)',
        'next_steps' => 'Next steps in the process',
        'portal_link' => 'Link to candidate portal',
        'unsubscribe_link' => 'Link to unsubscribe',
    ];

    /**
     * Get all categories with templates.
     */
    public function getCategories(): Collection
    {
        return Cache::remember('email_template_categories', $this->cacheTtl, function () {
            return EmailTemplateCategory::active()
                ->ordered()
                ->with(['templates' => function ($query) {
                    $query->active()->where('is_public', true)->orWhere('type', 'system');
                }])
                ->get();
        });
    }

    /**
     * Get templates for a user.
     */
    public function getTemplatesForUser(int $userId, ?int $categoryId = null): Collection
    {
        $query = EmailTemplate::active()
            ->forUser($userId)
            ->with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get a single template with variables parsed.
     */
    public function getTemplate(int $templateId): ?EmailTemplate
    {
        return EmailTemplate::with(['category', 'versions' => function ($q) {
            $q->orderByDesc('version_number')->limit(5);
        }])->find($templateId);
    }

    /**
     * Create a new template.
     */
    public function createTemplate(array $data): EmailTemplate
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['variables'] = $this->extractVariables($data['body_html'] ?? '');

        $template = EmailTemplate::create($data);

        // Create initial version
        $this->createVersion($template, $data['user_id'] ?? null, 'Initial version');

        $this->clearCache();

        return $template;
    }

    /**
     * Update a template.
     */
    public function updateTemplate(EmailTemplate $template, array $data, ?int $userId = null, ?string $changeNotes = null): EmailTemplate
    {
        // Check if content changed to create version
        $contentChanged = ($data['subject'] ?? $template->subject) !== $template->subject
            || ($data['body_html'] ?? $template->body_html) !== $template->body_html;

        if ($contentChanged) {
            $this->createVersion($template, $userId, $changeNotes ?? 'Updated template');
        }

        $data['variables'] = $this->extractVariables($data['body_html'] ?? $template->body_html);

        $template->update($data);
        $this->clearCache();

        return $template->fresh();
    }

    /**
     * Create a version of the template.
     */
    protected function createVersion(EmailTemplate $template, ?int $userId = null, ?string $changeNotes = null): EmailTemplateVersion
    {
        $latestVersion = $template->versions()->max('version_number') ?? 0;

        return EmailTemplateVersion::create([
            'template_id' => $template->id,
            'user_id' => $userId,
            'version_number' => $latestVersion + 1,
            'subject' => $template->subject,
            'body_html' => $template->body_html,
            'body_text' => $template->body_text,
            'change_notes' => $changeNotes,
        ]);
    }

    /**
     * Duplicate a template.
     */
    public function duplicateTemplate(EmailTemplate $template, int $userId): EmailTemplate
    {
        $data = $template->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['usage_count']);

        $data['name'] = $template->name . ' (Copy)';
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['user_id'] = $userId;
        $data['type'] = 'custom';
        $data['is_default'] = false;

        return EmailTemplate::create($data);
    }

    /**
     * Parse template with variables.
     */
    public function parseTemplate(EmailTemplate $template, array $data = []): array
    {
        return $template->parse($data);
    }

    /**
     * Send an email using a template.
     */
    public function sendEmail(
        EmailTemplate $template,
        string $recipientEmail,
        string $recipientName,
        array $variables,
        int $userId,
        ?int $companyId = null
    ): EmailSend {
        $parsed = $template->parse($variables);

        // Create send record
        $emailSend = EmailSend::create([
            'template_id' => $template->id,
            'user_id' => $userId,
            'company_id' => $companyId,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $parsed['subject'],
            'status' => 'queued',
            'metadata' => ['variables' => $variables],
        ]);

        try {
            Mail::send([], [], function (Message $message) use ($parsed, $recipientEmail, $recipientName) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($parsed['subject'])
                    ->html($parsed['body_html']);

                if (!empty($parsed['body_text'])) {
                    $message->text($parsed['body_text']);
                }
            });

            $emailSend->markAsSent();
            $template->incrementUsage();

            // Update analytics
            $this->updateAnalytics($template->id, 'send');
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'template_id' => $template->id,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ]);

            $emailSend->markAsFailed($e->getMessage());
            $this->updateAnalytics($template->id, 'failure');
        }

        return $emailSend;
    }

    /**
     * AI customize a template.
     */
    public function aiCustomize(
        EmailTemplate $template,
        int $userId,
        array $params = []
    ): array {
        $tone = $params['tone'] ?? 'professional';
        $focus = $params['focus'] ?? null;
        $length = $params['length'] ?? 'same';
        $context = $params['context'] ?? [];

        $prompt = $this->buildCustomizationPrompt($template, $tone, $focus, $length, $context);

        try {
            $customizedContent = $this->ai(
                $prompt,
                'You are an expert email copywriter specializing in HR and recruitment communications. Your task is to customize email templates while maintaining professionalism and warmth. Always preserve the {{variable}} placeholders exactly as they appear. Return only the customized email content without explanations.',
                ['temperature' => 0.7]
            );

            $tokensUsed = 0; // Tracked centrally by AIService

            // Store the customization
            $customization = EmailAiCustomization::create([
                'template_id' => $template->id,
                'user_id' => $userId,
                'original_content' => $template->body_html,
                'customized_content' => $customizedContent,
                'customization_params' => $params,
                'prompt_used' => $prompt,
                'tokens_used' => $tokensUsed,
            ]);

            return [
                'success' => true,
                'customized_content' => $customizedContent,
                'customization_id' => $customization->id,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Exception $e) {
            Log::error('AI customization failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to customize template. Please try again.',
            ];
        }
    }

    /**
     * Build AI customization prompt.
     */
    protected function buildCustomizationPrompt(
        EmailTemplate $template,
        string $tone,
        ?string $focus,
        string $length,
        array $context
    ): string {
        $prompt = "Please customize the following email template:\n\n";
        $prompt .= "ORIGINAL SUBJECT: {$template->subject}\n\n";
        $prompt .= "ORIGINAL BODY:\n{$template->body_html}\n\n";
        $prompt .= "CUSTOMIZATION INSTRUCTIONS:\n";
        $prompt .= "- Tone: {$tone}\n";

        if ($focus) {
            $prompt .= "- Focus on: {$focus}\n";
        }

        $prompt .= "- Length: Keep the email {$length} length\n";

        if (!empty($context)) {
            $prompt .= "- Additional context:\n";
            foreach ($context as $key => $value) {
                $prompt .= "  - {$key}: {$value}\n";
            }
        }

        $prompt .= "\nIMPORTANT: Preserve all {{variable}} placeholders exactly as they appear.\n";
        $prompt .= "Return the customized email in HTML format, starting with the subject on the first line.";

        return $prompt;
    }

    /**
     * Get template analytics.
     */
    public function getTemplateAnalytics(int $templateId, int $days = 30): array
    {
        $analytics = EmailTemplateAnalytics::where('template_id', $templateId)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        $totals = [
            'sends' => $analytics->sum('sends'),
            'deliveries' => $analytics->sum('deliveries'),
            'opens' => $analytics->sum('unique_opens'),
            'clicks' => $analytics->sum('unique_clicks'),
            'bounces' => $analytics->sum('bounces'),
        ];

        $totals['open_rate'] = $totals['deliveries'] > 0
            ? round(($totals['opens'] / $totals['deliveries']) * 100, 2)
            : 0;

        $totals['click_rate'] = $totals['opens'] > 0
            ? round(($totals['clicks'] / $totals['opens']) * 100, 2)
            : 0;

        return [
            'daily' => $analytics,
            'totals' => $totals,
        ];
    }

    /**
     * Get overall email analytics for a user.
     */
    public function getUserAnalytics(int $userId, int $days = 30): array
    {
        $sends = EmailSend::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $byStatus = $sends->groupBy('status')->map->count();
        $byTemplate = $sends->groupBy('template_id')->map->count();

        return [
            'total_sent' => $sends->count(),
            'by_status' => $byStatus,
            'by_template' => $byTemplate,
            'open_rate' => $this->calculateRate($sends, ['opened', 'clicked'], ['delivered', 'opened', 'clicked']),
            'click_rate' => $this->calculateRate($sends, ['clicked'], ['opened', 'clicked']),
        ];
    }

    /**
     * Calculate rate from collection.
     */
    protected function calculateRate(Collection $sends, array $numeratorStatuses, array $denominatorStatuses): float
    {
        $denominator = $sends->whereIn('status', $denominatorStatuses)->count();
        if ($denominator === 0) {
            return 0;
        }

        $numerator = $sends->whereIn('status', $numeratorStatuses)->count();
        return round(($numerator / $denominator) * 100, 2);
    }

    /**
     * Update analytics for a template.
     */
    protected function updateAnalytics(int $templateId, string $event): void
    {
        $analytics = EmailTemplateAnalytics::firstOrCreate([
            'template_id' => $templateId,
            'date' => now()->toDateString(),
        ]);

        switch ($event) {
            case 'send':
                $analytics->increment('sends');
                break;
            case 'delivery':
                $analytics->increment('deliveries');
                break;
            case 'open':
                $analytics->increment('opens');
                $analytics->increment('unique_opens');
                break;
            case 'click':
                $analytics->increment('clicks');
                $analytics->increment('unique_clicks');
                break;
            case 'bounce':
                $analytics->increment('bounces');
                break;
            case 'failure':
                $analytics->increment('failures');
                break;
        }

        $analytics->calculateRates();
    }

    /**
     * Extract variables from template content.
     */
    protected function extractVariables(string $content): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $content, $matches);
        $foundVariables = array_unique($matches[1] ?? []);

        $variables = [];
        foreach ($foundVariables as $var) {
            $variables[$var] = $this->availableVariables[$var] ?? 'Custom variable';
        }

        return $variables;
    }

    /**
     * Get all available variables.
     */
    public function getAvailableVariables(): array
    {
        return $this->availableVariables;
    }

    /**
     * Generate unique slug.
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (EmailTemplate::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Clear template cache.
     */
    public function clearCache(): void
    {
        Cache::forget('email_template_categories');
    }

    /**
     * Get default templates grouped by category.
     */
    public function getDefaultTemplates(): array
    {
        return [
            'interview' => [
                [
                    'name' => 'Interview Invitation',
                    'subject' => 'Interview Invitation: {{job_title}} at {{company_name}}',
                    'body_html' => $this->getInterviewInvitationTemplate(),
                    'tone' => 'professional',
                ],
                [
                    'name' => 'Interview Reminder',
                    'subject' => 'Reminder: Your Interview Tomorrow - {{job_title}}',
                    'body_html' => $this->getInterviewReminderTemplate(),
                    'tone' => 'friendly',
                ],
                [
                    'name' => 'Interview Reschedule',
                    'subject' => 'Interview Reschedule Request - {{job_title}}',
                    'body_html' => $this->getInterviewRescheduleTemplate(),
                    'tone' => 'professional',
                ],
            ],
            'rejection' => [
                [
                    'name' => 'Application Rejection - Standard',
                    'subject' => 'Update on Your Application for {{job_title}}',
                    'body_html' => $this->getRejectionStandardTemplate(),
                    'tone' => 'professional',
                ],
                [
                    'name' => 'Post-Interview Rejection',
                    'subject' => 'Thank You for Interviewing with {{company_name}}',
                    'body_html' => $this->getRejectionAfterInterviewTemplate(),
                    'tone' => 'friendly',
                ],
            ],
            'offer' => [
                [
                    'name' => 'Job Offer Letter',
                    'subject' => 'Job Offer: {{job_title}} at {{company_name}}',
                    'body_html' => $this->getOfferLetterTemplate(),
                    'tone' => 'professional',
                ],
                [
                    'name' => 'Offer Acceptance Confirmation',
                    'subject' => 'Welcome to {{company_name}}!',
                    'body_html' => $this->getOfferAcceptanceTemplate(),
                    'tone' => 'friendly',
                ],
            ],
            'follow-up' => [
                [
                    'name' => 'Application Received',
                    'subject' => 'Application Received: {{job_title}} at {{company_name}}',
                    'body_html' => $this->getApplicationReceivedTemplate(),
                    'tone' => 'professional',
                ],
                [
                    'name' => 'Status Update',
                    'subject' => 'Update on Your Application - {{company_name}}',
                    'body_html' => $this->getStatusUpdateTemplate(),
                    'tone' => 'friendly',
                ],
            ],
            'onboarding' => [
                [
                    'name' => 'Welcome to the Team',
                    'subject' => 'Welcome to {{company_name}}, {{candidate_first_name}}!',
                    'body_html' => $this->getWelcomeTemplate(),
                    'tone' => 'friendly',
                ],
                [
                    'name' => 'First Day Information',
                    'subject' => 'Everything You Need for Your First Day',
                    'body_html' => $this->getFirstDayTemplate(),
                    'tone' => 'professional',
                ],
            ],
            'reference' => [
                [
                    'name' => 'Reference Request',
                    'subject' => 'Reference Request for {{candidate_name}}',
                    'body_html' => $this->getReferenceRequestTemplate(),
                    'tone' => 'professional',
                ],
            ],
        ];
    }

    // Template content methods
    protected function getInterviewInvitationTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f97316;">Interview Invitation</h2>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>Thank you for your interest in the <strong>{{job_title}}</strong> position at {{company_name}}. We were impressed by your application and would like to invite you for an interview.</p>
    
    <div style="background-color: #fff7ed; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #ea580c;">Interview Details</h3>
        <p><strong>Date:</strong> {{interview_date}}</p>
        <p><strong>Time:</strong> {{interview_time}}</p>
        <p><strong>Type:</strong> {{interview_type}}</p>
        <p><strong>Location/Link:</strong> {{interview_location}}</p>
        <p><strong>With:</strong> {{interviewer_name}}</p>
    </div>
    
    <p>Please confirm your attendance by replying to this email. If the scheduled time doesn't work for you, please let us know your availability.</p>
    
    <p>We look forward to speaking with you!</p>
    
    <p>Best regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getInterviewReminderTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f97316;">Interview Reminder</h2>
    
    <p>Hi {{candidate_first_name}},</p>
    
    <p>This is a friendly reminder about your upcoming interview for the <strong>{{job_title}}</strong> position.</p>
    
    <div style="background-color: #fff7ed; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <p><strong>📅 When:</strong> {{interview_date}} at {{interview_time}}</p>
        <p><strong>📍 Where:</strong> {{interview_location}}</p>
        <p><strong>👤 With:</strong> {{interviewer_name}}</p>
    </div>
    
    <p>A few tips for your interview:</p>
    <ul>
        <li>Test your equipment if it's a video interview</li>
        <li>Have a copy of your resume handy</li>
        <li>Prepare questions about the role and company</li>
    </ul>
    
    <p>Good luck! We're excited to meet you.</p>
    
    <p>Best,<br>
    {{sender_name}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getInterviewRescheduleTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f97316;">Interview Reschedule</h2>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>We need to reschedule your interview for the <strong>{{job_title}}</strong> position. We apologize for any inconvenience this may cause.</p>
    
    <p>Please let us know your availability for the following dates and times, and we will do our best to accommodate your schedule.</p>
    
    <p>If you have any questions, please don't hesitate to reach out.</p>
    
    <p>Best regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getRejectionStandardTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <p>Dear {{candidate_name}},</p>
    
    <p>Thank you for your interest in the <strong>{{job_title}}</strong> position at {{company_name}} and for taking the time to apply.</p>
    
    <p>After careful consideration, we have decided to move forward with other candidates whose experience more closely aligns with our current needs. This was a difficult decision as we received many qualified applications.</p>
    
    <p>We encourage you to apply for future positions that match your skills and experience. We will keep your application on file for consideration.</p>
    
    <p>We wish you the best in your job search and future career endeavors.</p>
    
    <p>Best regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getRejectionAfterInterviewTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <p>Dear {{candidate_name}},</p>
    
    <p>Thank you for taking the time to interview with us for the <strong>{{job_title}}</strong> position. We enjoyed learning more about your background and experience.</p>
    
    <p>After thoughtful deliberation, we have decided to proceed with another candidate. This was a challenging decision, as you clearly have valuable skills and experience to offer.</p>
    
    <p>We were genuinely impressed by your qualifications and would like to keep your information on file for future opportunities that may be a better fit.</p>
    
    <p>Thank you again for your interest in {{company_name}}. We wish you all the best in your career journey.</p>
    
    <p>Warm regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getOfferLetterTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #10b981;">Congratulations! 🎉</h2>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>We are thrilled to offer you the position of <strong>{{job_title}}</strong> at {{company_name}}!</p>
    
    <div style="background-color: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #059669;">Offer Details</h3>
        <p><strong>Position:</strong> {{job_title}}</p>
        <p><strong>Salary:</strong> {{offer_salary}}</p>
        <p><strong>Start Date:</strong> {{offer_start_date}}</p>
        <p><strong>Response Deadline:</strong> {{offer_deadline}}</p>
    </div>
    
    <p>This offer is contingent upon successful completion of background verification and any other pre-employment requirements.</p>
    
    <p>Please review the attached formal offer letter with complete details about benefits, policies, and terms of employment.</p>
    
    <p>To accept this offer, please reply to this email by {{offer_deadline}}. If you have any questions, don't hesitate to reach out.</p>
    
    <p>We are excited about the possibility of you joining our team!</p>
    
    <p>Best regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getOfferAcceptanceTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #10b981;">Welcome to the Team! 🎉</h2>
    
    <p>Dear {{candidate_first_name}},</p>
    
    <p>We're delighted to confirm that we received your acceptance for the <strong>{{job_title}}</strong> position. Welcome to {{company_name}}!</p>
    
    <p>Here's what happens next:</p>
    
    <ol>
        <li>Our HR team will send you onboarding documents within the next few days</li>
        <li>You'll receive access to our employee portal</li>
        <li>Your manager will reach out with first-day details</li>
    </ol>
    
    <p><strong>Your start date:</strong> {{offer_start_date}}</p>
    
    <p>In the meantime, if you have any questions, feel free to reach out to {{sender_email}}.</p>
    
    <p>We can't wait to have you on board!</p>
    
    <p>Best,<br>
    {{sender_name}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getApplicationReceivedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f97316;">Application Received</h2>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>Thank you for applying for the <strong>{{job_title}}</strong> position at {{company_name}}. We have received your application and appreciate your interest in joining our team.</p>
    
    <p>Our recruiting team is currently reviewing applications. We will be in touch if your qualifications match our requirements.</p>
    
    <p>In the meantime, you can track your application status by logging into our candidate portal:</p>
    
    <p style="text-align: center;">
        <a href="{{portal_link}}" style="display: inline-block; background-color: #f97316; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">View Application Status</a>
    </p>
    
    <p>Thank you for considering {{company_name}} for your career. We wish you all the best!</p>
    
    <p>Best regards,<br>
    The {{company_name}} Recruiting Team</p>
</div>
HTML;
    }

    protected function getStatusUpdateTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <p>Hi {{candidate_first_name}},</p>
    
    <p>We wanted to give you an update on your application for the <strong>{{job_title}}</strong> position.</p>
    
    <p>{{next_steps}}</p>
    
    <p>If you have any questions, please don't hesitate to reach out.</p>
    
    <p>Best,<br>
    {{sender_name}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getWelcomeTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f97316;">Welcome to {{company_name}}! 🎉</h2>
    
    <p>Dear {{candidate_first_name}},</p>
    
    <p>On behalf of the entire team, I want to officially welcome you to {{company_name}}! We're thrilled to have you join us as our new <strong>{{job_title}}</strong>.</p>
    
    <p>Your skills and experience will be valuable additions to our team, and we're excited to see the contributions you'll make.</p>
    
    <p>Before your first day on {{offer_start_date}}, please:</p>
    <ul>
        <li>Complete the onboarding documents sent to your email</li>
        <li>Review the employee handbook</li>
        <li>Set up your employee portal account</li>
    </ul>
    
    <p>If you have any questions before you start, please reach out to {{sender_email}} or {{sender_phone}}.</p>
    
    <p>We can't wait to meet you!</p>
    
    <p>Warm regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getFirstDayTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f97316;">Your First Day at {{company_name}}</h2>
    
    <p>Hi {{candidate_first_name}},</p>
    
    <p>We're looking forward to your first day on {{offer_start_date}}! Here's everything you need to know:</p>
    
    <div style="background-color: #fff7ed; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #ea580c;">📍 Where to Go</h3>
        <p>{{interview_location}}</p>
        <p>Ask for {{interviewer_name}} at reception.</p>
    </div>
    
    <h3>What to Bring:</h3>
    <ul>
        <li>Government-issued ID</li>
        <li>Completed onboarding paperwork</li>
        <li>Banking information for direct deposit</li>
    </ul>
    
    <h3>First Day Schedule:</h3>
    <ul>
        <li>9:00 AM - Welcome and orientation</li>
        <li>10:30 AM - Team introductions</li>
        <li>12:00 PM - Lunch with your team</li>
        <li>2:00 PM - IT setup and training</li>
    </ul>
    
    <p>If you have any questions before your first day, contact {{sender_email}}.</p>
    
    <p>See you soon!</p>
    
    <p>Best,<br>
    {{sender_name}}<br>
    {{company_name}}</p>
</div>
HTML;
    }

    protected function getReferenceRequestTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <p>Dear {{interviewer_name}},</p>
    
    <p><strong>{{candidate_name}}</strong> has applied for the position of <strong>{{job_title}}</strong> at {{company_name}} and has listed you as a professional reference.</p>
    
    <p>We would greatly appreciate if you could take a few minutes to provide feedback on your experience working with {{candidate_name}}.</p>
    
    <p>Please let us know:</p>
    <ul>
        <li>Your professional relationship with the candidate</li>
        <li>Their key strengths and areas for growth</li>
        <li>Their reliability and work ethic</li>
        <li>Whether you would recommend them for this role</li>
    </ul>
    
    <p>Your response will be kept confidential and will only be used to assist in our hiring decision.</p>
    
    <p>You can reply directly to this email or contact me at {{sender_phone}} if you prefer to discuss by phone.</p>
    
    <p>Thank you for your time and assistance.</p>
    
    <p>Best regards,<br>
    {{sender_name}}<br>
    {{sender_title}}<br>
    {{company_name}}<br>
    {{sender_email}}</p>
</div>
HTML;
    }
}
