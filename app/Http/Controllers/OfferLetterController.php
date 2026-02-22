<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BenefitsPackage;
use App\Models\CounterOffer;
use App\Models\OfferComparison;
use App\Models\OfferLetter;
use App\Models\OfferLetterTemplate;
use App\Services\OfferLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfferLetterController extends Controller
{
    public function __construct(
        protected OfferLetterService $offerLetterService
    ) {}

    /**
     * Display offer letters listing
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = OfferLetter::with(['company', 'candidate', 'job']);

        // Filter based on user role
        if ($user->hasRole('employer') || $user->hasRole('recruiter')) {
            $query->forCompany($user->company_id);
        } else {
            $query->forCandidate($user->id);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('job_title', 'like', "%{$request->search}%")
                  ->orWhereHas('candidate', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            });
        }

        $offers = $query->latest()->paginate(15);

        return view('offer-letters.index', compact('offers'));
    }

    /**
     * Show create offer form
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $templates = OfferLetterTemplate::forCompany($companyId)->active()->get();
        $benefitsPackages = BenefitsPackage::forCompany($companyId)->active()->get();

        return view('offer-letters.create', compact('templates', 'benefitsPackages'));
    }

    /**
     * Store new offer letter
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'nullable|exists:offer_letter_templates,id',
            'candidate_id' => 'required|exists:users,id',
            'job_id' => 'nullable|exists:job_postings,id',
            'benefits_package_id' => 'nullable|exists:benefits_packages,id',
            'job_title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'required|string|in:full-time,part-time,contract,internship',
            'work_location' => 'nullable|string|max:255',
            'work_arrangement' => 'required|in:on-site,remote,hybrid',
            'reporting_to' => 'nullable|string|max:255',
            'base_salary' => 'required|numeric|min:0',
            'salary_period' => 'required|in:hourly,weekly,bi-weekly,monthly,annually',
            'currency' => 'required|string|size:3',
            'signing_bonus' => 'nullable|numeric|min:0',
            'annual_bonus_target' => 'nullable|numeric|min:0|max:100',
            'bonus_structure' => 'nullable|string',
            'equity_shares' => 'nullable|integer|min:0',
            'equity_type' => 'nullable|string|max:100',
            'vesting_schedule' => 'nullable|string',
            'start_date' => 'required|date|after:today',
            'offer_expiry_date' => 'required|date|after:today',
            'response_deadline' => 'nullable|date|after:today|before_or_equal:offer_expiry_date',
            'letter_content' => 'nullable|string',
            'custom_terms' => 'nullable|array',
            'special_conditions' => 'nullable|string',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;

        // Get candidate name for template rendering
        $candidate = \App\Models\User::find($validated['candidate_id']);
        $validated['candidate_name'] = $candidate->name;
        $validated['company_name'] = $user->company->name ?? '';

        $offer = $this->offerLetterService->createOfferLetter($validated);

        return response()->json([
            'success' => true,
            'message' => 'Offer letter created successfully',
            'offer' => $offer,
            'redirect' => route('offer-letters.show', $offer),
        ]);
    }

    /**
     * Display single offer letter
     */
    public function show(OfferLetter $offerLetter): View
    {
        $this->authorize('view', $offerLetter);

        $offerLetter->load([
            'company',
            'candidate',
            'job',
            'template',
            'benefitsPackage',
            'counterOffers' => fn($q) => $q->latest(),
            'activities' => fn($q) => $q->latest()->limit(20),
        ]);

        // Mark as viewed if candidate is viewing
        if (Auth::id() === $offerLetter->candidate_id && !$offerLetter->viewed_at) {
            $offerLetter->markAsViewed();
        }

        return view('offer-letters.show', compact('offerLetter'));
    }

    /**
     * Show edit form
     */
    public function edit(OfferLetter $offerLetter): View
    {
        $this->authorize('update', $offerLetter);

        if (!$offerLetter->isDraft()) {
            abort(403, 'Only draft offers can be edited');
        }

        $user = Auth::user();
        $templates = OfferLetterTemplate::forCompany($user->company_id)->active()->get();
        $benefitsPackages = BenefitsPackage::forCompany($user->company_id)->active()->get();

        return view('offer-letters.edit', compact('offerLetter', 'templates', 'benefitsPackages'));
    }

    /**
     * Update offer letter
     */
    public function update(Request $request, OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('update', $offerLetter);

        if (!$offerLetter->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft offers can be edited',
            ], 403);
        }

        $validated = $request->validate([
            'template_id' => 'nullable|exists:offer_letter_templates,id',
            'benefits_package_id' => 'nullable|exists:benefits_packages,id',
            'job_title' => 'sometimes|required|string|max:255',
            'department' => 'nullable|string|max:255',
            'employment_type' => 'sometimes|required|string',
            'work_location' => 'nullable|string|max:255',
            'work_arrangement' => 'sometimes|required|in:on-site,remote,hybrid',
            'reporting_to' => 'nullable|string|max:255',
            'base_salary' => 'sometimes|required|numeric|min:0',
            'salary_period' => 'sometimes|required|in:hourly,weekly,bi-weekly,monthly,annually',
            'currency' => 'sometimes|required|string|size:3',
            'signing_bonus' => 'nullable|numeric|min:0',
            'annual_bonus_target' => 'nullable|numeric|min:0|max:100',
            'equity_shares' => 'nullable|integer|min:0',
            'equity_type' => 'nullable|string|max:100',
            'vesting_schedule' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'offer_expiry_date' => 'sometimes|required|date',
            'letter_content' => 'nullable|string',
            'custom_terms' => 'nullable|array',
            'special_conditions' => 'nullable|string',
        ]);

        $offer = $this->offerLetterService->updateOfferLetter($offerLetter, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Offer letter updated successfully',
            'offer' => $offer,
        ]);
    }

    /**
     * Send offer letter to candidate
     */
    public function send(Request $request, OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('update', $offerLetter);

        if (!$offerLetter->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer has already been sent',
            ], 400);
        }

        $customMessage = $request->input('message');

        $success = $this->offerLetterService->sendOffer($offerLetter, $customMessage);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Offer letter sent successfully' : 'Failed to send offer letter',
        ]);
    }

    /**
     * Download offer letter as PDF
     */
    public function download(OfferLetter $offerLetter)
    {
        $this->authorize('view', $offerLetter);

        $pdf = $this->offerLetterService->generatePdf($offerLetter);

        return $pdf->download('offer-letter-' . $offerLetter->uuid . '.pdf');
    }

    /**
     * Accept offer
     */
    public function accept(Request $request, OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('respond', $offerLetter);

        if (!$offerLetter->can_respond) {
            return response()->json([
                'success' => false,
                'message' => 'This offer cannot be accepted',
            ], 400);
        }

        $offerLetter->accept($request->input('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Congratulations! You have accepted the offer.',
        ]);
    }

    /**
     * Decline offer
     */
    public function decline(Request $request, OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('respond', $offerLetter);

        if (!$offerLetter->can_respond) {
            return response()->json([
                'success' => false,
                'message' => 'This offer cannot be declined',
            ], 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $offerLetter->decline($request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Offer has been declined',
        ]);
    }

    /**
     * Withdraw offer (employer action)
     */
    public function withdraw(OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('update', $offerLetter);

        if ($offerLetter->isAccepted() || $offerLetter->isWithdrawn()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer cannot be withdrawn',
            ], 400);
        }

        $offerLetter->withdraw();

        return response()->json([
            'success' => true,
            'message' => 'Offer has been withdrawn',
        ]);
    }

    /**
     * Submit counter offer
     */
    public function counterOffer(Request $request, OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('respond', $offerLetter);

        if (!$offerLetter->can_respond) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit counter offer for this offer',
            ], 400);
        }

        $validated = $request->validate([
            'requested_salary' => 'nullable|numeric|min:0',
            'requested_signing_bonus' => 'nullable|numeric|min:0',
            'requested_start_date' => 'nullable|date|after:today',
            'requested_equity_shares' => 'nullable|integer|min:0',
            'requested_benefits' => 'nullable|string',
            'other_requests' => 'nullable|string',
            'justification' => 'nullable|string',
        ]);

        $counterOffer = $this->offerLetterService->createCounterOffer($offerLetter, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Counter offer submitted successfully',
            'counter_offer' => $counterOffer,
        ]);
    }

    /**
     * Respond to counter offer (employer action)
     */
    public function respondToCounterOffer(Request $request, CounterOffer $counterOffer): JsonResponse
    {
        $this->authorize('update', $counterOffer->offerLetter);

        if (!$counterOffer->is_pending) {
            return response()->json([
                'success' => false,
                'message' => 'This counter offer has already been responded to',
            ], 400);
        }

        $validated = $request->validate([
            'action' => 'required|in:accept,partial,reject',
            'response' => 'nullable|string',
            'accepted_terms' => 'required_if:action,partial|nullable|array',
            'accepted_terms.salary' => 'nullable|numeric',
            'accepted_terms.signing_bonus' => 'nullable|numeric',
            'accepted_terms.start_date' => 'nullable|date',
            'accepted_terms.equity_shares' => 'nullable|integer',
        ]);

        $counterOffer = $this->offerLetterService->respondToCounterOffer(
            $counterOffer,
            $validated['action'],
            $validated['accepted_terms'] ?? null,
            $validated['response'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Response submitted successfully',
            'counter_offer' => $counterOffer,
        ]);
    }

    /**
     * Request digital signature
     */
    public function requestSignature(Request $request, OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('update', $offerLetter);

        $request->validate([
            'provider' => 'required|in:docusign,hellosign',
        ]);

        $documentId = match ($request->provider) {
            'docusign' => $this->offerLetterService->requestDocuSignSignature($offerLetter),
            'hellosign' => $this->offerLetterService->requestHelloSignSignature($offerLetter),
        };

        if (!$documentId) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request digital signature',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Signature request sent successfully',
            'document_id' => $documentId,
        ]);
    }

    /**
     * Check signature status
     */
    public function signatureStatus(OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('view', $offerLetter);

        $status = $this->offerLetterService->checkSignatureStatus($offerLetter);

        return response()->json([
            'success' => true,
            'status' => $status,
            'signed_at' => $offerLetter->signed_at,
        ]);
    }

    /**
     * Get AI analysis of offer
     */
    public function analyze(OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('view', $offerLetter);

        $analysis = $this->offerLetterService->analyzeOfferWithAI($offerLetter);

        return response()->json([
            'success' => (bool) $analysis,
            'analysis' => $analysis,
        ]);
    }

    /**
     * Get counter offer suggestions
     */
    public function suggestCounterOffer(OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('view', $offerLetter);

        $suggestions = $this->offerLetterService->suggestCounterOffer($offerLetter);

        return response()->json([
            'success' => (bool) $suggestions,
            'suggestions' => $suggestions,
        ]);
    }

    // Offer Comparison

    /**
     * Show comparison tool
     */
    public function comparison(): View
    {
        $user = Auth::user();
        $offers = OfferLetter::forCandidate($user->id)->active()->get();
        $comparisons = OfferComparison::where('user_id', $user->id)->latest()->get();

        return view('offer-letters.comparison', compact('offers', 'comparisons'));
    }

    /**
     * Create comparison
     */
    public function createComparison(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'offer_ids' => 'required|array|min:2',
            'offer_ids.*' => 'exists:offer_letters,id',
        ]);

        $comparison = $this->offerLetterService->createComparison(
            Auth::id(),
            $validated['offer_ids'],
            $validated['name'] ?? null
        );

        return response()->json([
            'success' => true,
            'comparison' => $comparison,
            'report' => $this->offerLetterService->generateComparisonReport($comparison),
        ]);
    }

    /**
     * Get comparison report
     */
    public function comparisonReport(OfferComparison $comparison): JsonResponse
    {
        $this->authorize('view', $comparison);

        $report = $this->offerLetterService->generateComparisonReport($comparison);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    // Templates Management

    /**
     * List templates
     */
    public function templates(): View
    {
        $user = Auth::user();
        $templates = OfferLetterTemplate::forCompany($user->company_id)->paginate(20);

        return view('offer-letters.templates.index', compact('templates'));
    }

    /**
     * Show template
     */
    public function showTemplate(OfferLetterTemplate $template): View
    {
        return view('offer-letters.templates.show', compact('template'));
    }

    /**
     * Create template
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $user->company_id;
        $validated['type'] = 'custom';

        $template = OfferLetterTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'template' => $template,
        ]);
    }

    // Benefits Packages

    /**
     * List benefits packages
     */
    public function benefitsPackages(): View
    {
        $user = Auth::user();
        $packages = BenefitsPackage::forCompany($user->company_id)->paginate(20);

        return view('offer-letters.benefits.index', compact('packages'));
    }

    /**
     * Create benefits package
     */
    public function storeBenefitsPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'benefits' => 'required|array',
            'benefits.*.category' => 'required|string',
            'benefits.*.name' => 'required|string',
            'benefits.*.description' => 'nullable|string',
            'benefits.*.annual_value' => 'nullable|numeric|min:0',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();

        $package = $this->offerLetterService->createBenefitsPackage(
            $user->company_id,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Benefits package created successfully',
            'package' => $package,
        ]);
    }

    /**
     * Get standard benefits template
     */
    public function benefitsTemplate(): JsonResponse
    {
        $template = $this->offerLetterService->getStandardBenefitsTemplate();

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    // Statistics

    /**
     * Get offer statistics
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->offerLetterService->getCompanyStatistics($user->company_id);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Get activity log
     */
    public function activities(OfferLetter $offerLetter): JsonResponse
    {
        $this->authorize('view', $offerLetter);

        $activities = $offerLetter->activities()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }
}
