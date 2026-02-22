<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BackgroundCheck;
use App\Models\BackgroundCheckAdverseAction;
use App\Models\BackgroundCheckItem;
use App\Models\BackgroundCheckPackage;
use App\Models\BackgroundCheckWebhook;
use App\Models\User;
use App\Notifications\BackgroundCheckConsentRequest;
use App\Notifications\BackgroundCheckCompleted;
use App\Notifications\BackgroundCheckPreAdverseNotice;
use App\Notifications\BackgroundCheckFinalAdverseNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackgroundCheckService
{
    protected array $providerConfigs;

    public function __construct()
    {
        $this->providerConfigs = [
            'checkr' => [
                'api_key' => config('services.checkr.api_key'),
                'base_url' => config('services.checkr.base_url', 'https://api.checkr.com/v1'),
                'webhook_secret' => config('services.checkr.webhook_secret'),
            ],
            'sterling' => [
                'api_key' => config('services.sterling.api_key'),
                'client_id' => config('services.sterling.client_id'),
                'base_url' => config('services.sterling.base_url', 'https://api.sterlingcheck.com/v2'),
            ],
            'goodhire' => [
                'api_key' => config('services.goodhire.api_key'),
                'base_url' => config('services.goodhire.base_url', 'https://api.goodhire.com/v1'),
            ],
        ];
    }

    /**
     * Create a new background check request
     */
    public function createBackgroundCheck(
        int $companyId,
        int $candidateId,
        int $requestedBy,
        string $provider,
        array $checksRequested,
        ?int $applicationId = null,
        ?int $packageId = null
    ): BackgroundCheck {
        return DB::transaction(function () use ($companyId, $candidateId, $requestedBy, $provider, $checksRequested, $applicationId, $packageId) {
            $package = $packageId ? BackgroundCheckPackage::find($packageId) : null;

            $backgroundCheck = BackgroundCheck::create([
                'company_id' => $companyId,
                'candidate_id' => $candidateId,
                'application_id' => $applicationId,
                'package_id' => $packageId,
                'requested_by' => $requestedBy,
                'provider' => $provider,
                'status' => 'pending',
                'checks_requested' => $checksRequested,
                'estimated_completion_days' => $package?->estimated_days ?? 5,
                'cost' => $package?->price,
                'consent_expires_at' => now()->addDays(7),
            ]);

            // Create individual check items
            foreach ($checksRequested as $checkType) {
                BackgroundCheckItem::create([
                    'background_check_id' => $backgroundCheck->id,
                    'check_type' => $checkType,
                    'status' => 'pending',
                ]);
            }

            // Log activity
            $backgroundCheck->logActivity('created', 'Background check request created');

            return $backgroundCheck;
        });
    }

    /**
     * Send consent request to candidate
     */
    public function sendConsentRequest(BackgroundCheck $backgroundCheck): bool
    {
        try {
            $candidate = $backgroundCheck->candidate;
            
            $backgroundCheck->update([
                'status' => 'consent_pending',
                'consent_requested_at' => now(),
            ]);

            // Send notification to candidate
            $candidate->notify(new BackgroundCheckConsentRequest($backgroundCheck));

            $backgroundCheck->logActivity('consent_sent', 'Consent request email sent to candidate');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send consent request', [
                'background_check_id' => $backgroundCheck->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Record candidate consent
     */
    public function recordConsent(
        BackgroundCheck $backgroundCheck,
        string $ipAddress,
        string $userAgent
    ): bool {
        if ($backgroundCheck->isExpired()) {
            return false;
        }

        $backgroundCheck->update([
            'consent_given' => true,
            'consent_received_at' => now(),
            'consent_ip_address' => $ipAddress,
            'consent_user_agent' => $userAgent,
            'status' => 'consent_received',
        ]);

        $backgroundCheck->logActivity(
            'consent_received', 
            'Candidate provided consent',
            ['ip_address' => $ipAddress]
        );

        // Automatically start the background check
        $this->startBackgroundCheck($backgroundCheck);

        return true;
    }

    /**
     * Start the background check with the provider
     */
    public function startBackgroundCheck(BackgroundCheck $backgroundCheck): bool
    {
        if (!$backgroundCheck->hasConsent()) {
            Log::warning('Cannot start background check without consent', [
                'background_check_id' => $backgroundCheck->id,
            ]);
            return false;
        }

        try {
            $result = match($backgroundCheck->provider) {
                'checkr' => $this->startCheckrCheck($backgroundCheck),
                'sterling' => $this->startSterlingCheck($backgroundCheck),
                'goodhire' => $this->startGoodHireCheck($backgroundCheck),
                default => throw new \Exception("Unknown provider: {$backgroundCheck->provider}"),
            };

            if ($result['success']) {
                $backgroundCheck->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'provider_check_id' => $result['check_id'] ?? null,
                    'provider_candidate_id' => $result['candidate_id'] ?? null,
                ]);

                // Update individual check items
                $backgroundCheck->items()->update(['status' => 'in_progress', 'started_at' => now()]);

                $backgroundCheck->logActivity('started', 'Background check submitted to provider');

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to start background check', [
                'background_check_id' => $backgroundCheck->id,
                'provider' => $backgroundCheck->provider,
                'error' => $e->getMessage(),
            ]);

            $backgroundCheck->update(['status' => 'failed']);
            $backgroundCheck->logActivity('failed', 'Failed to start background check: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Start check with Checkr
     */
    protected function startCheckrCheck(BackgroundCheck $backgroundCheck): array
    {
        $config = $this->providerConfigs['checkr'];
        $candidate = $backgroundCheck->candidate;

        // First, create or get candidate in Checkr
        $candidateResponse = Http::withBasicAuth($config['api_key'], '')
            ->post("{$config['base_url']}/candidates", [
                'first_name' => $candidate->first_name ?? explode(' ', $candidate->name)[0],
                'last_name' => $candidate->last_name ?? explode(' ', $candidate->name)[1] ?? '',
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'dob' => $candidate->date_of_birth?->format('Y-m-d'),
                'ssn' => $candidate->ssn ?? null,
                'driver_license_number' => $candidate->driver_license_number ?? null,
                'driver_license_state' => $candidate->driver_license_state ?? null,
            ]);

        if (!$candidateResponse->successful()) {
            throw new \Exception('Failed to create Checkr candidate: ' . $candidateResponse->body());
        }

        $checkrCandidateId = $candidateResponse->json('id');

        // Determine package based on checks requested
        $package = $this->mapChecksToCheckrPackage($backgroundCheck->checks_requested);

        // Create the report (background check)
        $reportResponse = Http::withBasicAuth($config['api_key'], '')
            ->post("{$config['base_url']}/reports", [
                'candidate_id' => $checkrCandidateId,
                'package' => $package,
            ]);

        if (!$reportResponse->successful()) {
            throw new \Exception('Failed to create Checkr report: ' . $reportResponse->body());
        }

        return [
            'success' => true,
            'check_id' => $reportResponse->json('id'),
            'candidate_id' => $checkrCandidateId,
        ];
    }

    /**
     * Start check with Sterling
     */
    protected function startSterlingCheck(BackgroundCheck $backgroundCheck): array
    {
        $config = $this->providerConfigs['sterling'];
        $candidate = $backgroundCheck->candidate;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'X-Client-Id' => $config['client_id'],
        ])->post("{$config['base_url']}/screenings", [
            'candidate' => [
                'firstName' => $candidate->first_name ?? explode(' ', $candidate->name)[0],
                'lastName' => $candidate->last_name ?? explode(' ', $candidate->name)[1] ?? '',
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'dateOfBirth' => $candidate->date_of_birth?->format('Y-m-d'),
                'ssn' => $candidate->ssn ?? null,
            ],
            'packages' => $this->mapChecksToSterlingPackage($backgroundCheck->checks_requested),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Sterling screening: ' . $response->body());
        }

        return [
            'success' => true,
            'check_id' => $response->json('screeningId'),
            'candidate_id' => $response->json('candidateId'),
        ];
    }

    /**
     * Start check with GoodHire
     */
    protected function startGoodHireCheck(BackgroundCheck $backgroundCheck): array
    {
        $config = $this->providerConfigs['goodhire'];
        $candidate = $backgroundCheck->candidate;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
        ])->post("{$config['base_url']}/reports", [
            'candidate' => [
                'first_name' => $candidate->first_name ?? explode(' ', $candidate->name)[0],
                'last_name' => $candidate->last_name ?? explode(' ', $candidate->name)[1] ?? '',
                'email' => $candidate->email,
            ],
            'products' => $this->mapChecksToGoodHireProducts($backgroundCheck->checks_requested),
            'send_email' => false, // We handle our own emails
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to create GoodHire report: ' . $response->body());
        }

        return [
            'success' => true,
            'check_id' => $response->json('id'),
            'candidate_id' => $response->json('candidate_id'),
        ];
    }

    /**
     * Map check types to Checkr package
     */
    protected function mapChecksToCheckrPackage(array $checks): string
    {
        // Checkr uses predefined packages
        if (in_array('criminal', $checks) && in_array('employment', $checks) && in_array('education', $checks)) {
            return 'driver_pro';
        }
        if (in_array('criminal', $checks) && in_array('employment', $checks)) {
            return 'pro';
        }
        if (in_array('criminal', $checks)) {
            return 'basic';
        }
        return 'basic';
    }

    /**
     * Map check types to Sterling package
     */
    protected function mapChecksToSterlingPackage(array $checks): array
    {
        $packages = [];
        foreach ($checks as $check) {
            $packages[] = match($check) {
                'criminal' => 'CRIMINAL_SEARCH',
                'employment' => 'EMPLOYMENT_VERIFICATION',
                'education' => 'EDUCATION_VERIFICATION',
                'credit' => 'CREDIT_CHECK',
                'drug' => 'DRUG_SCREEN',
                'mvr' => 'MVR',
                default => strtoupper($check),
            };
        }
        return $packages;
    }

    /**
     * Map check types to GoodHire products
     */
    protected function mapChecksToGoodHireProducts(array $checks): array
    {
        $products = [];
        foreach ($checks as $check) {
            $products[] = match($check) {
                'criminal' => 'criminal_background',
                'employment' => 'employment_verification',
                'education' => 'education_verification',
                'credit' => 'credit_check',
                'drug' => 'drug_screening',
                'mvr' => 'motor_vehicle_report',
                'ssn_trace' => 'ssn_trace',
                'sex_offender' => 'sex_offender_registry',
                default => $check,
            };
        }
        return $products;
    }

    /**
     * Process webhook from provider
     */
    public function processWebhook(string $provider, array $payload): bool
    {
        // Store the webhook
        $webhook = BackgroundCheckWebhook::create([
            'provider' => $provider,
            'event_type' => $payload['type'] ?? $payload['event'] ?? 'unknown',
            'provider_check_id' => $this->extractCheckIdFromPayload($provider, $payload),
            'payload' => $payload,
        ]);

        try {
            $result = match($provider) {
                'checkr' => $this->processCheckrWebhook($payload),
                'sterling' => $this->processSterlingWebhook($payload),
                'goodhire' => $this->processGoodHireWebhook($payload),
                default => false,
            };

            $webhook->markAsProcessed($result ? 'Successfully processed' : 'Processing failed');
            return $result;
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);
            $webhook->markAsProcessed('Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract check ID from webhook payload
     */
    protected function extractCheckIdFromPayload(string $provider, array $payload): ?string
    {
        return match($provider) {
            'checkr' => $payload['data']['object']['id'] ?? $payload['data']['object']['report_id'] ?? null,
            'sterling' => $payload['screeningId'] ?? null,
            'goodhire' => $payload['report_id'] ?? null,
            default => null,
        };
    }

    /**
     * Process Checkr webhook
     */
    protected function processCheckrWebhook(array $payload): bool
    {
        $type = $payload['type'] ?? '';
        $data = $payload['data']['object'] ?? [];

        $backgroundCheck = BackgroundCheck::where('provider', 'checkr')
            ->where(function ($q) use ($data) {
                $q->where('provider_check_id', $data['id'] ?? '')
                  ->orWhere('provider_report_id', $data['id'] ?? '');
            })
            ->first();

        if (!$backgroundCheck) {
            return false;
        }

        return match($type) {
            'report.completed' => $this->handleCheckCompleted($backgroundCheck, $data),
            'report.suspended' => $this->handleCheckSuspended($backgroundCheck, $data),
            'report.upgraded' => $this->handleCheckUpgraded($backgroundCheck, $data),
            default => true,
        };
    }

    /**
     * Process Sterling webhook
     */
    protected function processSterlingWebhook(array $payload): bool
    {
        $backgroundCheck = BackgroundCheck::where('provider', 'sterling')
            ->where('provider_check_id', $payload['screeningId'] ?? '')
            ->first();

        if (!$backgroundCheck) {
            return false;
        }

        $status = $payload['status'] ?? '';

        if ($status === 'COMPLETED') {
            return $this->handleCheckCompleted($backgroundCheck, $payload);
        }

        return true;
    }

    /**
     * Process GoodHire webhook
     */
    protected function processGoodHireWebhook(array $payload): bool
    {
        $backgroundCheck = BackgroundCheck::where('provider', 'goodhire')
            ->where('provider_check_id', $payload['report_id'] ?? '')
            ->first();

        if (!$backgroundCheck) {
            return false;
        }

        $event = $payload['event'] ?? '';

        if ($event === 'report.completed') {
            return $this->handleCheckCompleted($backgroundCheck, $payload);
        }

        return true;
    }

    /**
     * Handle check completed
     */
    protected function handleCheckCompleted(BackgroundCheck $backgroundCheck, array $data): bool
    {
        $result = $this->determineOverallResult($backgroundCheck->provider, $data);
        $hasFlags = $result !== 'clear';

        $backgroundCheck->update([
            'status' => 'completed',
            'result' => $result,
            'completed_at' => now(),
            'report_summary' => $this->extractReportSummary($backgroundCheck->provider, $data),
            'has_flags' => $hasFlags,
            'flags' => $hasFlags ? $this->extractFlags($backgroundCheck->provider, $data) : null,
        ]);

        // Update individual check items
        $this->updateCheckItems($backgroundCheck, $data);

        $backgroundCheck->logActivity('completed', "Background check completed with result: {$result}");

        // Notify the requester
        $backgroundCheck->requester->notify(new BackgroundCheckCompleted($backgroundCheck));

        return true;
    }

    /**
     * Handle check suspended
     */
    protected function handleCheckSuspended(BackgroundCheck $backgroundCheck, array $data): bool
    {
        $backgroundCheck->update([
            'result' => 'suspended',
            'report_summary' => $data,
        ]);

        $backgroundCheck->logActivity('suspended', 'Background check suspended - additional information may be required');

        return true;
    }

    /**
     * Handle check upgraded
     */
    protected function handleCheckUpgraded(BackgroundCheck $backgroundCheck, array $data): bool
    {
        $backgroundCheck->logActivity('upgraded', 'Background check package upgraded');
        return true;
    }

    /**
     * Determine overall result from provider data
     */
    protected function determineOverallResult(string $provider, array $data): string
    {
        return match($provider) {
            'checkr' => match($data['status'] ?? '') {
                'clear' => 'clear',
                'consider' => 'consider',
                'suspended' => 'suspended',
                default => 'consider',
            },
            'sterling' => match($data['result'] ?? $data['adjudication'] ?? '') {
                'ELIGIBLE', 'CLEAR' => 'clear',
                'REVIEW', 'CONSIDER' => 'consider',
                default => 'consider',
            },
            'goodhire' => match($data['result'] ?? '') {
                'clear' => 'clear',
                'consider', 'review' => 'consider',
                default => 'consider',
            },
            default => 'consider',
        };
    }

    /**
     * Extract report summary from provider data
     */
    protected function extractReportSummary(string $provider, array $data): array
    {
        // Simplified - in production, parse provider-specific format
        return [
            'raw_status' => $data['status'] ?? $data['result'] ?? null,
            'completed_at' => $data['completed_at'] ?? now()->toISOString(),
            'checks_count' => count($data['checks'] ?? $data['screenings'] ?? []),
        ];
    }

    /**
     * Extract flags from provider data
     */
    protected function extractFlags(string $provider, array $data): array
    {
        $flags = [];

        // This would be customized per provider
        $checks = $data['checks'] ?? $data['screenings'] ?? $data['products'] ?? [];

        foreach ($checks as $check) {
            if (($check['status'] ?? '') === 'consider' || ($check['result'] ?? '') === 'consider') {
                $flags[] = [
                    'type' => $check['type'] ?? $check['name'] ?? 'unknown',
                    'status' => $check['status'] ?? $check['result'] ?? 'consider',
                    'details' => $check['details'] ?? $check['records'] ?? null,
                ];
            }
        }

        return $flags;
    }

    /**
     * Update individual check items from provider data
     */
    protected function updateCheckItems(BackgroundCheck $backgroundCheck, array $data): void
    {
        $checks = $data['checks'] ?? $data['screenings'] ?? $data['products'] ?? [];
        $completedTypes = [];

        foreach ($checks as $check) {
            $type = $this->normalizeCheckType($backgroundCheck->provider, $check['type'] ?? $check['name'] ?? '');
            
            $backgroundCheck->items()
                ->where('check_type', $type)
                ->update([
                    'status' => 'completed',
                    'result' => $this->normalizeCheckResult($check['status'] ?? $check['result'] ?? 'clear'),
                    'result_data' => $check,
                    'completed_at' => now(),
                ]);

            $completedTypes[] = $type;
        }

        $backgroundCheck->update(['checks_completed' => $completedTypes]);
    }

    /**
     * Normalize check type from provider format
     */
    protected function normalizeCheckType(string $provider, string $type): string
    {
        $typeMap = [
            'criminal_search' => 'criminal',
            'criminal_background' => 'criminal',
            'county_criminal_search' => 'criminal',
            'national_criminal_search' => 'criminal',
            'employment_verification' => 'employment',
            'education_verification' => 'education',
            'motor_vehicle_report' => 'mvr',
            'ssn_trace' => 'ssn_trace',
            'sex_offender_search' => 'sex_offender',
            'global_watchlist_search' => 'global_watchlist',
            'drug_screening' => 'drug',
        ];

        return $typeMap[strtolower($type)] ?? strtolower(str_replace(' ', '_', $type));
    }

    /**
     * Normalize check result
     */
    protected function normalizeCheckResult(string $result): string
    {
        return match(strtolower($result)) {
            'clear', 'pass', 'eligible', 'complete' => 'clear',
            'consider', 'review', 'pending_review' => 'consider',
            'adverse', 'fail', 'ineligible' => 'adverse',
            default => 'consider',
        };
    }

    /**
     * Cancel a background check
     */
    public function cancelBackgroundCheck(BackgroundCheck $backgroundCheck, string $reason): bool
    {
        if ($backgroundCheck->isCompleted()) {
            return false;
        }

        $backgroundCheck->update([
            'status' => 'cancelled',
            'internal_notes' => $backgroundCheck->internal_notes . "\nCancelled: " . $reason,
        ]);

        $backgroundCheck->logActivity('cancelled', "Background check cancelled: {$reason}");

        return true;
    }

    /**
     * Initiate adverse action process
     */
    public function initiateAdverseAction(
        BackgroundCheck $backgroundCheck,
        int $initiatedBy,
        string $reason,
        int $waitingPeriodDays = 5
    ): BackgroundCheckAdverseAction {
        $adverseAction = BackgroundCheckAdverseAction::create([
            'background_check_id' => $backgroundCheck->id,
            'initiated_by' => $initiatedBy,
            'pre_adverse_reason' => $reason,
            'waiting_period_days' => $waitingPeriodDays,
            'waiting_period_ends_at' => now()->addDays($waitingPeriodDays),
        ]);

        // Send pre-adverse notice to candidate
        $this->sendPreAdverseNotice($backgroundCheck, $adverseAction);

        $backgroundCheck->update(['adjudication' => 'pre_adverse']);
        $backgroundCheck->logActivity('pre_adverse_sent', 'Pre-adverse action notice sent to candidate');

        return $adverseAction;
    }

    /**
     * Send pre-adverse action notice
     */
    protected function sendPreAdverseNotice(BackgroundCheck $backgroundCheck, BackgroundCheckAdverseAction $adverseAction): void
    {
        $candidate = $backgroundCheck->candidate;
        $candidate->notify(new BackgroundCheckPreAdverseNotice($backgroundCheck, $adverseAction));

        $adverseAction->update(['pre_adverse_sent_at' => now()]);
    }

    /**
     * Record candidate dispute
     */
    public function recordDispute(BackgroundCheckAdverseAction $adverseAction, string $reason): void
    {
        $adverseAction->update([
            'candidate_disputed' => true,
            'dispute_reason' => $reason,
            'dispute_received_at' => now(),
        ]);

        $adverseAction->backgroundCheck->logActivity('dispute_received', 'Candidate submitted dispute');
    }

    /**
     * Send final adverse action notice
     */
    public function sendFinalAdverseAction(BackgroundCheckAdverseAction $adverseAction, string $reason): void
    {
        $backgroundCheck = $adverseAction->backgroundCheck;

        $adverseAction->update([
            'final_action_taken' => true,
            'final_adverse_reason' => $reason,
            'final_adverse_sent_at' => now(),
            'outcome' => 'upheld',
        ]);

        $backgroundCheck->update(['adjudication' => 'adverse']);

        // Send final adverse notice
        $backgroundCheck->candidate->notify(new BackgroundCheckFinalAdverseNotice($backgroundCheck, $adverseAction));

        $backgroundCheck->logActivity('adverse_action_sent', 'Final adverse action notice sent');
    }

    /**
     * Withdraw adverse action
     */
    public function withdrawAdverseAction(BackgroundCheckAdverseAction $adverseAction, string $notes = null): void
    {
        $adverseAction->update([
            'outcome' => 'withdrawn',
            'outcome_notes' => $notes,
        ]);

        $backgroundCheck = $adverseAction->backgroundCheck;
        $backgroundCheck->update(['adjudication' => 'approved']);
        $backgroundCheck->logActivity('adverse_action_withdrawn', 'Adverse action withdrawn');
    }

    /**
     * Download and store report PDF
     */
    public function downloadReport(BackgroundCheck $backgroundCheck): ?string
    {
        try {
            $pdfContent = match($backgroundCheck->provider) {
                'checkr' => $this->downloadCheckrReport($backgroundCheck),
                'sterling' => $this->downloadSterlingReport($backgroundCheck),
                'goodhire' => $this->downloadGoodHireReport($backgroundCheck),
                default => null,
            };

            if ($pdfContent) {
                $path = "background-checks/{$backgroundCheck->uuid}/report.pdf";
                Storage::disk('private')->put($path, $pdfContent);

                $backgroundCheck->update(['report_pdf_path' => $path]);
                $backgroundCheck->logActivity('report_downloaded', 'Report PDF downloaded and stored');

                return $path;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to download background check report', [
                'background_check_id' => $backgroundCheck->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Download Checkr report
     */
    protected function downloadCheckrReport(BackgroundCheck $backgroundCheck): ?string
    {
        $config = $this->providerConfigs['checkr'];
        
        $response = Http::withBasicAuth($config['api_key'], '')
            ->get("{$config['base_url']}/reports/{$backgroundCheck->provider_check_id}/pdf");

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Download Sterling report
     */
    protected function downloadSterlingReport(BackgroundCheck $backgroundCheck): ?string
    {
        $config = $this->providerConfigs['sterling'];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'X-Client-Id' => $config['client_id'],
        ])->get("{$config['base_url']}/screenings/{$backgroundCheck->provider_check_id}/report");

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Download GoodHire report
     */
    protected function downloadGoodHireReport(BackgroundCheck $backgroundCheck): ?string
    {
        $config = $this->providerConfigs['goodhire'];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
        ])->get("{$config['base_url']}/reports/{$backgroundCheck->provider_check_id}/pdf");

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Get statistics for a company
     */
    public function getCompanyStatistics(int $companyId): array
    {
        $checks = BackgroundCheck::where('company_id', $companyId);

        return [
            'total' => $checks->count(),
            'pending' => (clone $checks)->pending()->count(),
            'completed' => (clone $checks)->completed()->count(),
            'clear' => (clone $checks)->where('result', 'clear')->count(),
            'requires_review' => (clone $checks)->requiresReview()->count(),
            'average_completion_days' => (clone $checks)
                ->completed()
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(DATEDIFF(completed_at, started_at)) as avg_days')
                ->value('avg_days') ?? 0,
            'by_provider' => (clone $checks)
                ->selectRaw('provider, COUNT(*) as count')
                ->groupBy('provider')
                ->pluck('count', 'provider'),
        ];
    }
}
