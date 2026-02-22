<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BenefitsPackage;
use App\Models\CounterOffer;
use App\Models\OfferComparison;
use App\Models\OfferLetter;
use App\Models\OfferLetterTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenAI\Laravel\Facades\OpenAI;

class OfferLetterService
{
    /**
     * Create a new offer letter
     */
    public function createOfferLetter(array $data): OfferLetter
    {
        // Get template and render content if template is provided
        if (!empty($data['template_id']) && empty($data['letter_content'])) {
            $template = OfferLetterTemplate::find($data['template_id']);
            if ($template) {
                $data['letter_content'] = $this->renderTemplate($template, $data);
            }
        }

        $offer = OfferLetter::create($data);
        $offer->logActivity('created', 'Offer letter created');

        return $offer;
    }

    /**
     * Render template with data
     */
    public function renderTemplate(OfferLetterTemplate $template, array $data): string
    {
        $variables = [
            'candidate_name' => $data['candidate_name'] ?? '',
            'job_title' => $data['job_title'] ?? '',
            'department' => $data['department'] ?? '',
            'base_salary' => number_format((float) ($data['base_salary'] ?? 0), 2),
            'salary_period' => $data['salary_period'] ?? 'annually',
            'currency' => $data['currency'] ?? 'USD',
            'signing_bonus' => number_format((float) ($data['signing_bonus'] ?? 0), 2),
            'start_date' => $data['start_date'] ?? '',
            'offer_expiry_date' => $data['offer_expiry_date'] ?? '',
            'work_location' => $data['work_location'] ?? '',
            'work_arrangement' => $data['work_arrangement'] ?? '',
            'reporting_to' => $data['reporting_to'] ?? '',
            'employment_type' => $data['employment_type'] ?? 'full-time',
            'equity_shares' => $data['equity_shares'] ?? '',
            'equity_type' => $data['equity_type'] ?? '',
            'vesting_schedule' => $data['vesting_schedule'] ?? '',
            'annual_bonus_target' => $data['annual_bonus_target'] ?? '',
            'bonus_structure' => $data['bonus_structure'] ?? '',
            'company_name' => $data['company_name'] ?? '',
            'today_date' => now()->format('F j, Y'),
        ];

        return $template->render($variables);
    }

    /**
     * Update offer letter
     */
    public function updateOfferLetter(OfferLetter $offer, array $data): OfferLetter
    {
        $offer->update($data);
        $offer->logActivity('updated', 'Offer letter updated');

        return $offer->fresh();
    }

    /**
     * Send offer letter to candidate
     */
    public function sendOffer(OfferLetter $offer, ?string $customMessage = null): bool
    {
        try {
            // Generate PDF
            $pdf = $this->generatePdf($offer);

            // Send email
            Mail::send('emails.offer-letter', [
                'offer' => $offer,
                'customMessage' => $customMessage,
            ], function ($message) use ($offer, $pdf) {
                $message->to($offer->candidate->email, $offer->candidate->name)
                    ->subject('Job Offer from ' . ($offer->company->name ?? 'Company'))
                    ->attachData($pdf->output(), 'offer-letter.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            $offer->markAsSent();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send offer letter', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate PDF for offer letter
     */
    public function generatePdf(OfferLetter $offer): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.offer-letter', [
            'offer' => $offer,
        ])->setPaper('letter');
    }

    /**
     * Create counter offer
     */
    public function createCounterOffer(OfferLetter $offer, array $data): CounterOffer
    {
        $roundNumber = $offer->counterOffers()->max('round_number') ?? 0;

        $counterOffer = $offer->counterOffers()->create([
            'initiated_by' => auth()->id(),
            'round_number' => $roundNumber + 1,
            'requested_salary' => $data['requested_salary'] ?? null,
            'requested_signing_bonus' => $data['requested_signing_bonus'] ?? null,
            'requested_start_date' => $data['requested_start_date'] ?? null,
            'requested_equity_shares' => $data['requested_equity_shares'] ?? null,
            'requested_benefits' => $data['requested_benefits'] ?? null,
            'other_requests' => $data['other_requests'] ?? null,
            'justification' => $data['justification'] ?? null,
            'status' => 'pending',
        ]);

        $offer->update(['status' => 'counter_offered']);
        $offer->logActivity('counter_offered', 'Counter offer submitted', [
            'round' => $counterOffer->round_number,
            'requested_salary' => $counterOffer->requested_salary,
        ]);

        return $counterOffer;
    }

    /**
     * Respond to counter offer
     */
    public function respondToCounterOffer(
        CounterOffer $counterOffer,
        string $action,
        ?array $acceptedTerms = null,
        ?string $response = null
    ): CounterOffer {
        switch ($action) {
            case 'accept':
                $counterOffer->accept($response);
                break;
            case 'partial':
                $counterOffer->partiallyAccept($acceptedTerms ?? [], $response);
                break;
            case 'reject':
                $counterOffer->reject($response);
                break;
        }

        return $counterOffer->fresh();
    }

    // Digital Signature Integration

    /**
     * Request digital signature via DocuSign
     */
    public function requestDocuSignSignature(OfferLetter $offer): ?string
    {
        $config = config('services.docusign');
        
        if (!$config || !$config['enabled']) {
            Log::warning('DocuSign is not configured');
            return null;
        }

        try {
            // Generate PDF
            $pdf = $this->generatePdf($offer);
            $pdfBase64 = base64_encode($pdf->output());

            // Create DocuSign envelope
            $response = Http::withToken($config['access_token'])
                ->post($config['base_url'] . '/envelopes', [
                    'emailSubject' => 'Please sign your offer letter',
                    'documents' => [
                        [
                            'documentBase64' => $pdfBase64,
                            'name' => 'Offer Letter',
                            'fileExtension' => 'pdf',
                            'documentId' => '1',
                        ],
                    ],
                    'recipients' => [
                        'signers' => [
                            [
                                'email' => $offer->candidate->email,
                                'name' => $offer->candidate->name,
                                'recipientId' => '1',
                                'routingOrder' => '1',
                                'tabs' => [
                                    'signHereTabs' => [
                                        [
                                            'documentId' => '1',
                                            'pageNumber' => '1',
                                            'xPosition' => '200',
                                            'yPosition' => '600',
                                        ],
                                    ],
                                    'dateSignedTabs' => [
                                        [
                                            'documentId' => '1',
                                            'pageNumber' => '1',
                                            'xPosition' => '200',
                                            'yPosition' => '650',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'status' => 'sent',
                ]);

            if ($response->successful()) {
                $envelopeId = $response->json('envelopeId');
                
                $offer->update([
                    'signature_provider' => 'docusign',
                    'signature_document_id' => $envelopeId,
                    'signature_status' => 'sent',
                ]);

                $offer->logActivity('signature_requested', 'Digital signature requested via DocuSign', [
                    'envelope_id' => $envelopeId,
                ]);

                return $envelopeId;
            }

            Log::error('DocuSign envelope creation failed', [
                'offer_id' => $offer->id,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('DocuSign integration error', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Request digital signature via HelloSign
     */
    public function requestHelloSignSignature(OfferLetter $offer): ?string
    {
        $config = config('services.hellosign');
        
        if (!$config || !$config['enabled']) {
            Log::warning('HelloSign is not configured');
            return null;
        }

        try {
            // Generate PDF and save temporarily
            $pdf = $this->generatePdf($offer);
            $tempPath = storage_path('app/temp/offer-' . $offer->uuid . '.pdf');
            $pdf->save($tempPath);

            $response = Http::withToken($config['api_key'])
                ->attach('file[0]', file_get_contents($tempPath), 'offer-letter.pdf')
                ->post($config['base_url'] . '/signature_request/send', [
                    'title' => 'Offer Letter - ' . $offer->job_title,
                    'subject' => 'Please sign your offer letter',
                    'message' => 'Please review and sign the attached offer letter.',
                    'signers[0][email_address]' => $offer->candidate->email,
                    'signers[0][name]' => $offer->candidate->name,
                    'signers[0][order]' => 0,
                ]);

            // Clean up temp file
            @unlink($tempPath);

            if ($response->successful()) {
                $signatureRequestId = $response->json('signature_request.signature_request_id');
                
                $offer->update([
                    'signature_provider' => 'hellosign',
                    'signature_document_id' => $signatureRequestId,
                    'signature_status' => 'sent',
                ]);

                $offer->logActivity('signature_requested', 'Digital signature requested via HelloSign', [
                    'signature_request_id' => $signatureRequestId,
                ]);

                return $signatureRequestId;
            }

            Log::error('HelloSign signature request failed', [
                'offer_id' => $offer->id,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('HelloSign integration error', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check signature status
     */
    public function checkSignatureStatus(OfferLetter $offer): ?string
    {
        if (!$offer->signature_document_id || !$offer->signature_provider) {
            return null;
        }

        try {
            $status = match ($offer->signature_provider) {
                'docusign' => $this->checkDocuSignStatus($offer),
                'hellosign' => $this->checkHelloSignStatus($offer),
                default => null,
            };

            if ($status && $status !== $offer->signature_status) {
                $offer->update(['signature_status' => $status]);

                if ($status === 'completed') {
                    $offer->update(['signed_at' => now()]);
                    $offer->logActivity('signed', 'Document signed digitally');
                }
            }

            return $status;
        } catch (\Exception $e) {
            Log::error('Failed to check signature status', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function checkDocuSignStatus(OfferLetter $offer): ?string
    {
        $config = config('services.docusign');
        
        $response = Http::withToken($config['access_token'])
            ->get($config['base_url'] . '/envelopes/' . $offer->signature_document_id);

        if ($response->successful()) {
            $status = $response->json('status');
            return match ($status) {
                'completed' => 'completed',
                'declined' => 'declined',
                'voided' => 'voided',
                default => $status,
            };
        }

        return null;
    }

    protected function checkHelloSignStatus(OfferLetter $offer): ?string
    {
        $config = config('services.hellosign');
        
        $response = Http::withToken($config['api_key'])
            ->get($config['base_url'] . '/signature_request/' . $offer->signature_document_id);

        if ($response->successful()) {
            $data = $response->json('signature_request');
            
            if ($data['is_complete']) {
                return 'completed';
            }
            if ($data['is_declined']) {
                return 'declined';
            }
            return 'pending';
        }

        return null;
    }

    // Offer Comparison

    /**
     * Create offer comparison
     */
    public function createComparison(int $userId, array $offerIds, ?string $name = null): OfferComparison
    {
        return OfferComparison::create([
            'user_id' => $userId,
            'name' => $name ?? 'Comparison ' . now()->format('M j, Y'),
            'offer_ids' => $offerIds,
        ]);
    }

    /**
     * Generate comparison report
     */
    public function generateComparisonReport(OfferComparison $comparison): array
    {
        $offers = $comparison->getOffers();

        return [
            'offers' => $comparison->comparison_data,
            'salary_comparison' => $comparison->salary_comparison,
            'recommendation' => $comparison->getRecommendation(),
            'criteria_analysis' => $this->analyzeCriteria($offers),
        ];
    }

    protected function analyzeCriteria($offers): array
    {
        $criteria = [
            'best_salary' => $offers->sortByDesc('base_salary')->first(),
            'best_total_comp' => $offers->sortByDesc('total_compensation')->first(),
            'best_equity' => $offers->sortByDesc('equity_shares')->first(),
            'earliest_start' => $offers->sortBy('start_date')->first(),
            'best_flexibility' => $offers->first(fn($o) => $o->work_arrangement === 'remote'),
        ];

        return array_map(function ($offer) {
            return $offer ? [
                'id' => $offer->id,
                'company' => $offer->company->name ?? 'Unknown',
                'job_title' => $offer->job_title,
            ] : null;
        }, $criteria);
    }

    // AI-Powered Features

    /**
     * Generate AI-powered offer analysis
     */
    public function analyzeOfferWithAI(OfferLetter $offer): ?array
    {
        try {
            $prompt = $this->buildOfferAnalysisPrompt($offer);

            $response = OpenAI::chat()->create([
                'model' => config('ai.default_model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert career advisor specializing in job offer analysis and salary negotiation. Provide detailed, actionable insights.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1500,
            ]);

            $content = $response->choices[0]->message->content;

            return $this->parseAIResponse($content);
        } catch (\Exception $e) {
            Log::error('AI offer analysis failed', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function buildOfferAnalysisPrompt(OfferLetter $offer): string
    {
        $benefitsValue = $offer->benefitsPackage?->total_value ?? 0;

        return <<<PROMPT
Analyze this job offer and provide insights:

Position: {$offer->job_title}
Department: {$offer->department}
Employment Type: {$offer->employment_type}
Work Arrangement: {$offer->work_arrangement}
Location: {$offer->work_location}

Compensation:
- Base Salary: {$offer->currency} {$offer->base_salary} ({$offer->salary_period})
- Annualized Salary: {$offer->currency} {$offer->getAnnualizedSalary()}
- Signing Bonus: {$offer->currency} {$offer->signing_bonus}
- Annual Bonus Target: {$offer->annual_bonus_target}%
- Total First Year Compensation: {$offer->currency} {$offer->total_compensation}

Equity:
- Shares: {$offer->equity_shares}
- Type: {$offer->equity_type}
- Vesting: {$offer->vesting_schedule}

Benefits Package Value: {$offer->currency} {$benefitsValue}

Start Date: {$offer->start_date?->format('Y-m-d')}
Offer Expires: {$offer->offer_expiry_date?->format('Y-m-d')}

Please provide:
1. Overall assessment of the offer (strength rating 1-10)
2. Key strengths of this offer
3. Potential concerns or red flags
4. Negotiation opportunities
5. Questions the candidate should ask
6. Market competitiveness assessment
7. Recommended counter-offer strategy (if applicable)

Format your response as structured sections.
PROMPT;
    }

    protected function parseAIResponse(string $content): array
    {
        // Parse the structured response
        $sections = [
            'overall_assessment' => '',
            'strengths' => [],
            'concerns' => [],
            'negotiation_opportunities' => [],
            'questions' => [],
            'market_assessment' => '',
            'counter_offer_strategy' => '',
        ];

        // Simple parsing - in production, you'd use more sophisticated parsing
        $sections['raw_analysis'] = $content;

        return $sections;
    }

    /**
     * Generate counter-offer suggestions
     */
    public function suggestCounterOffer(OfferLetter $offer): ?array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.default_model', 'gpt-4'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert salary negotiation advisor. Provide specific, actionable counter-offer suggestions with realistic target amounts.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Based on this offer: {$offer->job_title} at {$offer->currency} " .
                            number_format((float) $offer->base_salary) .
                            " {$offer->salary_period}, suggest a reasonable counter-offer with justification. " .
                            "Consider market rates and provide specific numbers.",
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            return [
                'suggestions' => $response->choices[0]->message->content,
                'recommended_salary' => (float) $offer->base_salary * 1.15, // 15% increase suggestion
                'recommended_signing_bonus' => (float) ($offer->signing_bonus ?? 0) * 1.2,
            ];
        } catch (\Exception $e) {
            Log::error('AI counter-offer suggestion failed', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // Benefits Package Management

    /**
     * Create benefits package
     */
    public function createBenefitsPackage(int $companyId, array $data): BenefitsPackage
    {
        return BenefitsPackage::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'benefits' => $data['benefits'],
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Get standard benefits template
     */
    public function getStandardBenefitsTemplate(): array
    {
        return [
            [
                'category' => 'Health & Wellness',
                'name' => 'Medical Insurance',
                'description' => 'Comprehensive medical, dental, and vision coverage',
                'annual_value' => 15000,
            ],
            [
                'category' => 'Health & Wellness',
                'name' => 'Life Insurance',
                'description' => '2x annual salary life insurance',
                'annual_value' => 500,
            ],
            [
                'category' => 'Time Off',
                'name' => 'Paid Time Off',
                'description' => '20 days PTO + holidays',
                'annual_value' => 0,
            ],
            [
                'category' => 'Time Off',
                'name' => 'Sick Leave',
                'description' => 'Unlimited sick leave',
                'annual_value' => 0,
            ],
            [
                'category' => 'Retirement',
                'name' => '401(k) Match',
                'description' => '4% employer match',
                'annual_value' => 8000,
            ],
            [
                'category' => 'Professional Development',
                'name' => 'Learning Budget',
                'description' => 'Annual learning and development allowance',
                'annual_value' => 2000,
            ],
            [
                'category' => 'Work Flexibility',
                'name' => 'Remote Work',
                'description' => 'Flexible remote work policy',
                'annual_value' => 0,
            ],
            [
                'category' => 'Equipment',
                'name' => 'Home Office Stipend',
                'description' => 'One-time home office setup allowance',
                'annual_value' => 1000,
            ],
        ];
    }

    // Template Management

    /**
     * Get system templates
     */
    public function getSystemTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return OfferLetterTemplate::system()->active()->get();
    }

    /**
     * Create default system templates
     */
    public function createDefaultTemplates(): void
    {
        $templates = [
            [
                'name' => 'Standard Offer Letter',
                'slug' => 'standard-offer',
                'description' => 'A professional, comprehensive offer letter template',
                'type' => 'system',
                'is_default' => true,
                'content_html' => $this->getStandardOfferTemplate(),
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'department',
                    'base_salary',
                    'currency',
                    'salary_period',
                    'start_date',
                    'offer_expiry_date',
                    'company_name',
                ],
            ],
            [
                'name' => 'Executive Offer Letter',
                'slug' => 'executive-offer',
                'description' => 'Detailed offer letter for executive positions',
                'type' => 'system',
                'content_html' => $this->getExecutiveOfferTemplate(),
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'department',
                    'base_salary',
                    'currency',
                    'signing_bonus',
                    'equity_shares',
                    'vesting_schedule',
                    'start_date',
                ],
            ],
            [
                'name' => 'Simple Offer Letter',
                'slug' => 'simple-offer',
                'description' => 'A brief, straightforward offer letter',
                'type' => 'system',
                'content_html' => $this->getSimpleOfferTemplate(),
                'variables' => [
                    'candidate_name',
                    'job_title',
                    'base_salary',
                    'start_date',
                ],
            ],
        ];

        foreach ($templates as $template) {
            OfferLetterTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    protected function getStandardOfferTemplate(): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: right; margin-bottom: 30px;">
        <p>{{today_date}}</p>
    </div>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>We are pleased to offer you the position of <strong>{{job_title}}</strong> at {{company_name}}. 
    We were impressed by your qualifications and believe you will be a valuable addition to our team.</p>
    
    <h3 style="color: #333; margin-top: 30px;">Position Details</h3>
    <ul>
        <li><strong>Title:</strong> {{job_title}}</li>
        <li><strong>Department:</strong> {{department}}</li>
        <li><strong>Work Location:</strong> {{work_location}}</li>
        <li><strong>Work Arrangement:</strong> {{work_arrangement}}</li>
        <li><strong>Reports To:</strong> {{reporting_to}}</li>
        <li><strong>Start Date:</strong> {{start_date}}</li>
    </ul>
    
    <h3 style="color: #333; margin-top: 30px;">Compensation</h3>
    <ul>
        <li><strong>Base Salary:</strong> {{currency}} {{base_salary}} per {{salary_period}}</li>
        <li><strong>Signing Bonus:</strong> {{currency}} {{signing_bonus}} (if applicable)</li>
        <li><strong>Annual Bonus Target:</strong> {{annual_bonus_target}}% of base salary</li>
    </ul>
    
    <p style="margin-top: 30px;">This offer is contingent upon successful completion of background check and verification 
    of your eligibility to work. Please indicate your acceptance by signing below.</p>
    
    <p><strong>This offer expires on {{offer_expiry_date}}.</strong></p>
    
    <p style="margin-top: 40px;">We look forward to welcoming you to our team!</p>
    
    <p>Sincerely,<br>
    {{company_name}}</p>
    
    <div style="margin-top: 60px; border-top: 1px solid #ccc; padding-top: 20px;">
        <p><strong>Acceptance</strong></p>
        <p>I, {{candidate_name}}, accept this offer of employment.</p>
        <p style="margin-top: 40px;">
            Signature: _________________________________ Date: _____________
        </p>
    </div>
</div>
HTML;
    }

    protected function getExecutiveOfferTemplate(): string
    {
        return <<<HTML
<div style="font-family: 'Times New Roman', serif; max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="color: #1a365d; margin-bottom: 5px;">EXECUTIVE EMPLOYMENT OFFER</h1>
        <p style="color: #666;">Confidential</p>
    </div>
    
    <p style="text-align: right;">{{today_date}}</p>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>On behalf of {{company_name}}, I am delighted to extend this offer of employment for the position of 
    <strong>{{job_title}}</strong>. This letter outlines the terms and conditions of your employment.</p>
    
    <h2 style="color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 10px;">1. Position and Responsibilities</h2>
    <p>You will serve as {{job_title}} in our {{department}} division, reporting to {{reporting_to}}.</p>
    
    <h2 style="color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 10px;">2. Compensation Package</h2>
    
    <h3>Base Salary</h3>
    <p>Your annual base salary will be {{currency}} {{base_salary}}, payable in accordance with the Company's 
    standard payroll practices.</p>
    
    <h3>Signing Bonus</h3>
    <p>You will receive a one-time signing bonus of {{currency}} {{signing_bonus}}, payable within 30 days 
    of your start date.</p>
    
    <h3>Annual Performance Bonus</h3>
    <p>You will be eligible for an annual performance bonus with a target of {{annual_bonus_target}}% of 
    your base salary, based on individual and company performance.</p>
    
    <h3>Equity Compensation</h3>
    <p>Subject to board approval, you will be granted {{equity_shares}} shares of {{equity_type}}.<br>
    Vesting Schedule: {{vesting_schedule}}</p>
    
    <h2 style="color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 10px;">3. Start Date</h2>
    <p>Your anticipated start date is {{start_date}}.</p>
    
    <h2 style="color: #1a365d; border-bottom: 2px solid #1a365d; padding-bottom: 10px;">4. Benefits</h2>
    <p>You will be eligible for our comprehensive executive benefits package including health insurance, 
    401(k) with company match, and executive perks.</p>
    
    <p style="margin-top: 40px;"><strong>This offer expires on {{offer_expiry_date}}.</strong></p>
    
    <div style="margin-top: 60px;">
        <p>Sincerely,</p>
        <p style="margin-top: 40px;">_________________________________<br>
        Chief Executive Officer<br>
        {{company_name}}</p>
    </div>
    
    <div style="margin-top: 60px; border-top: 2px solid #1a365d; padding-top: 20px;">
        <p><strong>ACCEPTANCE</strong></p>
        <p>I hereby accept this offer of employment and agree to the terms outlined above.</p>
        <p style="margin-top: 40px;">
            _________________________________<br>
            {{candidate_name}}<br><br>
            Date: _____________
        </p>
    </div>
</div>
HTML;
    }

    protected function getSimpleOfferTemplate(): string
    {
        return <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 30px;">
    <p>{{today_date}}</p>
    
    <p>Dear {{candidate_name}},</p>
    
    <p>We are happy to offer you the position of <strong>{{job_title}}</strong> at our company.</p>
    
    <p><strong>Salary:</strong> {{currency}} {{base_salary}} per year<br>
    <strong>Start Date:</strong> {{start_date}}</p>
    
    <p>Please sign below to accept this offer.</p>
    
    <p>Best regards,<br>
    HR Department</p>
    
    <p style="margin-top: 50px;">
        Signature: _________________ Date: _________
    </p>
</div>
HTML;
    }

    // Statistics and Analytics

    /**
     * Get offer statistics for company
     */
    public function getCompanyStatistics(int $companyId): array
    {
        $offers = OfferLetter::forCompany($companyId);

        return [
            'total_offers' => $offers->count(),
            'pending' => $offers->clone()->pending()->count(),
            'accepted' => $offers->clone()->byStatus('accepted')->count(),
            'declined' => $offers->clone()->byStatus('declined')->count(),
            'withdrawn' => $offers->clone()->byStatus('withdrawn')->count(),
            'acceptance_rate' => $this->calculateAcceptanceRate($companyId),
            'avg_time_to_accept' => $this->calculateAvgTimeToAccept($companyId),
            'avg_counter_offers' => $this->calculateAvgCounterOffers($companyId),
        ];
    }

    protected function calculateAcceptanceRate(int $companyId): float
    {
        $total = OfferLetter::forCompany($companyId)
            ->whereIn('status', ['accepted', 'declined'])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $accepted = OfferLetter::forCompany($companyId)
            ->byStatus('accepted')
            ->count();

        return round(($accepted / $total) * 100, 2);
    }

    protected function calculateAvgTimeToAccept(int $companyId): ?float
    {
        $offers = OfferLetter::forCompany($companyId)
            ->byStatus('accepted')
            ->whereNotNull('sent_at')
            ->whereNotNull('responded_at')
            ->get();

        if ($offers->isEmpty()) {
            return null;
        }

        $totalDays = $offers->sum(function ($offer) {
            return $offer->sent_at->diffInDays($offer->responded_at);
        });

        return round($totalDays / $offers->count(), 1);
    }

    protected function calculateAvgCounterOffers(int $companyId): float
    {
        $offers = OfferLetter::forCompany($companyId)
            ->withCount('counterOffers')
            ->get();

        if ($offers->isEmpty()) {
            return 0;
        }

        return round($offers->avg('counter_offers_count'), 2);
    }
}
