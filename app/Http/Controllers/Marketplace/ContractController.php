<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceContract;
use App\Models\MarketplaceDispute;
use App\Models\MarketplaceMilestone;
use App\Models\MarketplaceReview;
use App\Services\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show contract details.
     */
    public function show(MarketplaceContract $contract): View
    {
        $this->authorize('view', $contract);

        $contract->load([
            'project',
            'employer',
            'freelancer',
            'proposal',
            'milestones.escrow',
            'messages' => fn($q) => $q->orderByDesc('created_at')->limit(20),
            'reviews',
            'disputes' => fn($q) => $q->active(),
        ]);

        $isEmployer = $contract->employer_id === auth()->id();
        $isFreelancer = $contract->freelancer_id === auth()->id();

        return view('marketplace.contract.show', [
            'contract' => $contract,
            'isEmployer' => $isEmployer,
            'isFreelancer' => $isFreelancer,
        ]);
    }

    /**
     * Submit milestone work (freelancer).
     */
    public function submitMilestone(Request $request, MarketplaceMilestone $milestone): JsonResponse
    {
        $contract = $milestone->contract;
        $this->authorize('update', $contract);

        if ($contract->freelancer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the freelancer can submit milestone work.',
            ], 403);
        }

        $request->validate([
            'note' => 'nullable|string|max:2000',
            'files' => 'nullable|array',
            'files.*' => 'string|max:500', // URLs or file paths
        ]);

        try {
            $this->marketplaceService->forUser(auth()->user());
            $this->marketplaceService->submitMilestone(
                $milestone,
                $request->note,
                $request->files
            );

            return response()->json([
                'success' => true,
                'message' => 'Work submitted for review!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start working on milestone (freelancer).
     */
    public function startMilestone(MarketplaceMilestone $milestone): JsonResponse
    {
        $contract = $milestone->contract;
        $this->authorize('update', $contract);

        if ($contract->freelancer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the freelancer can start milestone work.',
            ], 403);
        }

        if (!$milestone->isFunded()) {
            return response()->json([
                'success' => false,
                'message' => 'This milestone must be funded before work can begin.',
            ], 422);
        }

        $milestone->start();

        return response()->json([
            'success' => true,
            'message' => 'Milestone started!',
        ]);
    }

    /**
     * Submit a review.
     */
    public function submitReview(Request $request, MarketplaceContract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'quality_rating' => 'nullable|integer|min:1|max:5',
            'timeliness_rating' => 'nullable|integer|min:1|max:5',
            'professionalism_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'cooperation_rating' => 'nullable|integer|min:1|max:5',
            'review_text' => 'required|string|min:50|max:2000',
            'private_feedback' => 'nullable|string|max:1000',
            'would_recommend' => 'boolean',
            'would_hire_again' => 'nullable|boolean',
            'skills_endorsed' => 'nullable|array',
        ]);

        try {
            $this->marketplaceService->forUser(auth()->user());
            $review = $this->marketplaceService->submitReview($contract, $request->all());

            return response()->json([
                'success' => true,
                'review' => $review,
                'message' => 'Review submitted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Respond to a review (reviewee).
     */
    public function respondToReview(Request $request, MarketplaceReview $review): JsonResponse
    {
        if ($review->reviewee_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only respond to reviews about you.',
            ], 403);
        }

        $request->validate([
            'response' => 'required|string|max:1000',
        ]);

        $review->respond($request->response);

        return response()->json([
            'success' => true,
            'message' => 'Response added.',
        ]);
    }

    /**
     * Raise a dispute.
     */
    public function raiseDispute(Request $request, MarketplaceContract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        // Check if there's already an active dispute
        if ($contract->hasActiveDispute()) {
            return response()->json([
                'success' => false,
                'message' => 'There is already an active dispute on this contract.',
            ], 422);
        }

        $request->validate([
            'milestone_id' => 'nullable|exists:marketplace_milestones,id',
            'dispute_type' => 'required|in:payment,quality,deadline,scope,communication,other',
            'description' => 'required|string|min:50|max:5000',
            'evidence' => 'nullable|array',
            'disputed_amount' => 'nullable|numeric|min:0',
        ]);

        $againstId = $contract->employer_id === auth()->id() 
            ? $contract->freelancer_id 
            : $contract->employer_id;

        $dispute = MarketplaceDispute::create([
            'contract_id' => $contract->id,
            'milestone_id' => $request->milestone_id,
            'raised_by_id' => auth()->id(),
            'against_id' => $againstId,
            'dispute_type' => $request->dispute_type,
            'description' => $request->description,
            'evidence' => $request->evidence,
            'disputed_amount' => $request->disputed_amount,
            'status' => 'open',
        ]);

        // Start review process
        $dispute->startReview();

        return response()->json([
            'success' => true,
            'dispute' => $dispute,
            'message' => 'Dispute raised. Our team will review it within 24-48 hours.',
        ]);
    }

    /**
     * View dispute details.
     */
    public function showDispute(MarketplaceDispute $dispute): View
    {
        $contract = $dispute->contract;
        $this->authorize('view', $contract);

        $dispute->load(['contract.project', 'raisedBy', 'against', 'milestone']);

        return view('marketplace.contract.dispute', [
            'dispute' => $dispute,
            'contract' => $contract,
        ]);
    }

    /**
     * Send message in contract.
     */
    public function sendMessage(Request $request, MarketplaceContract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $request->validate([
            'message' => 'required|string|max:5000',
            'attachments' => 'nullable|array',
        ]);

        $recipientId = $contract->employer_id === auth()->id()
            ? $contract->freelancer_id
            : $contract->employer_id;

        $message = $contract->messages()->create([
            'sender_id' => auth()->id(),
            'recipient_id' => $recipientId,
            'message' => $request->message,
            'attachments' => $request->attachments,
            'message_type' => 'contract',
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('sender'),
        ]);
    }

    /**
     * Get contract messages.
     */
    public function getMessages(MarketplaceContract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        $messages = $contract->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        // Mark unread messages as read
        $contract->messages()
            ->where('recipient_id', auth()->id())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Complete contract (if all milestones done).
     */
    public function completeContract(MarketplaceContract $contract): JsonResponse
    {
        $this->authorize('update', $contract);

        if ($contract->employer_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the employer can mark the contract as complete.',
            ], 403);
        }

        // Check if all milestones are released
        $pendingMilestones = $contract->milestones()
            ->whereNotIn('status', ['released'])
            ->count();

        if ($pendingMilestones > 0) {
            return response()->json([
                'success' => false,
                'message' => 'All milestones must be approved and released before completing the contract.',
            ], 422);
        }

        $contract->complete();

        return response()->json([
            'success' => true,
            'message' => 'Contract completed! Don\'t forget to leave a review.',
        ]);
    }

    /**
     * Cancel contract.
     */
    public function cancelContract(Request $request, MarketplaceContract $contract): JsonResponse
    {
        $this->authorize('update', $contract);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        // Check if there are funded but unreleased escrows
        $fundedEscrows = $contract->escrowTransactions()
            ->whereIn('status', ['funded', 'held'])
            ->exists();

        if ($fundedEscrows) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel contract with funded escrows. Please raise a dispute instead.',
            ], 422);
        }

        $contract->cancel($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Contract cancelled.',
        ]);
    }
}
