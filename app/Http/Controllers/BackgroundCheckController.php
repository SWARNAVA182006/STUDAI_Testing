<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BackgroundCheck;
use App\Models\BackgroundCheckPackage;
use App\Models\User;
use App\Services\BackgroundCheckService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class BackgroundCheckController extends Controller
{
    public function __construct(
        protected BackgroundCheckService $backgroundCheckService
    ) {}

    /**
     * Display list of background checks for employer
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        $query = BackgroundCheck::with(['candidate', 'package', 'requester'])
            ->where('company_id', $companyId);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by result
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        // Search by candidate name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('candidate', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $backgroundChecks = $query->orderByDesc('created_at')->paginate(15);

        $statistics = $this->backgroundCheckService->getCompanyStatistics($companyId);

        return view('background-checks.index', compact('backgroundChecks', 'statistics'));
    }

    /**
     * Show form to create a new background check
     */
    public function create(Request $request): View
    {
        $user = auth()->user();
        
        // Get available packages
        $packages = BackgroundCheckPackage::active()
            ->forCompany($user->company_id)
            ->get();

        // Get candidate if specified
        $candidate = null;
        if ($request->filled('candidate_id')) {
            $candidate = User::find($request->candidate_id);
        }

        // Get application if specified
        $applicationId = $request->application_id;

        return view('background-checks.create', compact('packages', 'candidate', 'applicationId'));
    }

    /**
     * Store a new background check request
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:users,id',
            'application_id' => 'nullable|exists:applications,id',
            'package_id' => 'nullable|exists:background_check_packages,id',
            'provider' => 'required|in:checkr,sterling,goodhire',
            'checks' => 'required|array|min:1',
            'checks.*' => 'string|in:criminal,employment,education,credit,drug,mvr,identity,ssn_trace,sex_offender,global_watchlist',
            'send_consent_now' => 'boolean',
        ]);

        $user = auth()->user();

        try {
            $backgroundCheck = $this->backgroundCheckService->createBackgroundCheck(
                companyId: $user->company_id,
                candidateId: $validated['candidate_id'],
                requestedBy: $user->id,
                provider: $validated['provider'],
                checksRequested: $validated['checks'],
                applicationId: $validated['application_id'] ?? null,
                packageId: $validated['package_id'] ?? null
            );

            // Send consent request if requested
            if ($request->boolean('send_consent_now', true)) {
                $this->backgroundCheckService->sendConsentRequest($backgroundCheck);
            }

            return response()->json([
                'success' => true,
                'message' => 'Background check created successfully',
                'data' => [
                    'id' => $backgroundCheck->id,
                    'uuid' => $backgroundCheck->uuid,
                    'status' => $backgroundCheck->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create background check: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific background check
     */
    public function show(BackgroundCheck $backgroundCheck): View
    {
        Gate::authorize('view', $backgroundCheck);

        $backgroundCheck->load(['candidate', 'company', 'package', 'requester', 'items', 'activities', 'adverseAction']);

        return view('background-checks.show', compact('backgroundCheck'));
    }

    /**
     * Send consent request to candidate
     */
    public function sendConsent(BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        if (!$backgroundCheck->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Consent request can only be sent for pending checks',
            ], 400);
        }

        $success = $this->backgroundCheckService->sendConsentRequest($backgroundCheck);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Consent request sent successfully' : 'Failed to send consent request',
        ]);
    }

    /**
     * Resend consent request
     */
    public function resendConsent(BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        if (!$backgroundCheck->isAwaitingConsent()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resend consent for this check',
            ], 400);
        }

        // Extend expiry
        $backgroundCheck->update([
            'consent_expires_at' => now()->addDays(7),
            'consent_token' => \Str::random(64),
        ]);

        $success = $this->backgroundCheckService->sendConsentRequest($backgroundCheck);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Consent request resent successfully' : 'Failed to resend consent request',
        ]);
    }

    /**
     * Cancel a background check
     */
    public function cancel(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $success = $this->backgroundCheckService->cancelBackgroundCheck(
            $backgroundCheck,
            $validated['reason']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Background check cancelled' : 'Cannot cancel this background check',
        ]);
    }

    /**
     * Download the background check report
     */
    public function downloadReport(BackgroundCheck $backgroundCheck)
    {
        Gate::authorize('view', $backgroundCheck);

        if (!$backgroundCheck->isCompleted()) {
            abort(404, 'Report not available');
        }

        // Download from provider if not already stored
        if (!$backgroundCheck->report_pdf_path) {
            $this->backgroundCheckService->downloadReport($backgroundCheck);
            $backgroundCheck->refresh();
        }

        if ($backgroundCheck->report_pdf_path) {
            return \Storage::disk('private')->download(
                $backgroundCheck->report_pdf_path,
                "background-check-{$backgroundCheck->uuid}.pdf"
            );
        }

        abort(404, 'Report not available');
    }

    /**
     * Initiate adverse action
     */
    public function initiateAdverseAction(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
            'waiting_period_days' => 'nullable|integer|min:5|max:14',
        ]);

        if (!$backgroundCheck->isCompleted() || $backgroundCheck->isClear()) {
            return response()->json([
                'success' => false,
                'message' => 'Adverse action can only be initiated for completed checks with findings',
            ], 400);
        }

        try {
            $adverseAction = $this->backgroundCheckService->initiateAdverseAction(
                $backgroundCheck,
                auth()->id(),
                $validated['reason'],
                $validated['waiting_period_days'] ?? 5
            );

            return response()->json([
                'success' => true,
                'message' => 'Pre-adverse action notice sent to candidate',
                'data' => [
                    'waiting_period_ends_at' => $adverseAction->waiting_period_ends_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate adverse action: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send final adverse action
     */
    public function sendFinalAdverseAction(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $adverseAction = $backgroundCheck->adverseAction;

        if (!$adverseAction || !$adverseAction->canSendFinalAction()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send final adverse action at this time',
            ], 400);
        }

        try {
            $this->backgroundCheckService->sendFinalAdverseAction($adverseAction, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Final adverse action notice sent',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send final adverse action: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Withdraw adverse action
     */
    public function withdrawAdverseAction(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $adverseAction = $backgroundCheck->adverseAction;

        if (!$adverseAction || $adverseAction->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot withdraw adverse action',
            ], 400);
        }

        $this->backgroundCheckService->withdrawAdverseAction($adverseAction, $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Adverse action withdrawn',
        ]);
    }

    /**
     * Update internal notes
     */
    public function updateNotes(Request $request, BackgroundCheck $backgroundCheck): JsonResponse
    {
        Gate::authorize('update', $backgroundCheck);

        $validated = $request->validate([
            'internal_notes' => 'nullable|string|max:5000',
        ]);

        $backgroundCheck->update(['internal_notes' => $validated['internal_notes']]);
        $backgroundCheck->logActivity('notes_updated', 'Internal notes updated');

        return response()->json([
            'success' => true,
            'message' => 'Notes updated successfully',
        ]);
    }

    /**
     * Get available packages
     */
    public function packages(): JsonResponse
    {
        $user = auth()->user();

        $packages = BackgroundCheckPackage::active()
            ->forCompany($user->company_id)
            ->get()
            ->map(fn($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'provider' => $package->provider,
                'provider_name' => $package->provider_name,
                'checks_included' => $package->checks_included,
                'checks_list' => $package->checks_list,
                'price' => $package->formatted_price,
                'estimated_days' => $package->estimated_days,
            ]);

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Get statistics
     */
    public function statistics(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->backgroundCheckService->getCompanyStatistics($user->company_id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // ========================
    // CANDIDATE ROUTES
    // ========================

    /**
     * Show consent form for candidate
     */
    public function showConsentForm(string $token): View
    {
        $backgroundCheck = BackgroundCheck::where('consent_token', $token)
            ->with(['company', 'package'])
            ->firstOrFail();

        if ($backgroundCheck->consent_given) {
            return view('background-checks.consent-already-given', compact('backgroundCheck'));
        }

        if ($backgroundCheck->isExpired()) {
            return view('background-checks.consent-expired', compact('backgroundCheck'));
        }

        return view('background-checks.consent', compact('backgroundCheck'));
    }

    /**
     * Process consent submission
     */
    public function submitConsent(Request $request, string $token): View
    {
        $backgroundCheck = BackgroundCheck::where('consent_token', $token)->firstOrFail();

        if ($backgroundCheck->consent_given) {
            return view('background-checks.consent-already-given', compact('backgroundCheck'));
        }

        if ($backgroundCheck->isExpired()) {
            return view('background-checks.consent-expired', compact('backgroundCheck'));
        }

        $request->validate([
            'agree_to_check' => 'required|accepted',
            'agree_to_terms' => 'required|accepted',
            'signature' => 'required|string|min:2',
        ]);

        $success = $this->backgroundCheckService->recordConsent(
            $backgroundCheck,
            $request->ip(),
            $request->userAgent()
        );

        if ($success) {
            return view('background-checks.consent-success', compact('backgroundCheck'));
        }

        return back()->withErrors(['error' => 'Failed to process consent. Please try again.']);
    }

    /**
     * Candidate dispute form
     */
    public function showDisputeForm(string $token): View
    {
        $backgroundCheck = BackgroundCheck::where('consent_token', $token)
            ->with(['company', 'adverseAction'])
            ->firstOrFail();

        $adverseAction = $backgroundCheck->adverseAction;

        if (!$adverseAction || !$adverseAction->isInWaitingPeriod()) {
            abort(404, 'Dispute period has ended');
        }

        return view('background-checks.dispute', compact('backgroundCheck', 'adverseAction'));
    }

    /**
     * Submit dispute
     */
    public function submitDispute(Request $request, string $token): View
    {
        $backgroundCheck = BackgroundCheck::where('consent_token', $token)->firstOrFail();

        $adverseAction = $backgroundCheck->adverseAction;

        if (!$adverseAction || !$adverseAction->isInWaitingPeriod()) {
            abort(404, 'Dispute period has ended');
        }

        $validated = $request->validate([
            'dispute_reason' => 'required|string|max:5000',
        ]);

        $this->backgroundCheckService->recordDispute($adverseAction, $validated['dispute_reason']);

        return view('background-checks.dispute-submitted', compact('backgroundCheck'));
    }

    // ========================
    // WEBHOOK ROUTES
    // ========================

    /**
     * Handle Checkr webhook
     */
    public function checkrWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature if configured
        $signature = $request->header('X-Checkr-Signature');
        
        $this->backgroundCheckService->processWebhook('checkr', $request->all());

        return response()->json(['received' => true]);
    }

    /**
     * Handle Sterling webhook
     */
    public function sterlingWebhook(Request $request): JsonResponse
    {
        $this->backgroundCheckService->processWebhook('sterling', $request->all());

        return response()->json(['received' => true]);
    }

    /**
     * Handle GoodHire webhook
     */
    public function goodhireWebhook(Request $request): JsonResponse
    {
        $this->backgroundCheckService->processWebhook('goodhire', $request->all());

        return response()->json(['received' => true]);
    }
}
