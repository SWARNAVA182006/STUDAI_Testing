<?php

declare(strict_types=1);

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\FreelancerProfile;
use App\Models\SkillBadge;
use App\Models\UserSkillBadge;
use App\Services\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FreelancerController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show profile setup/edit page.
     */
    public function profile(): View
    {
        $this->marketplaceService->forUser(auth()->user());
        $profile = $this->marketplaceService->getFreelancerProfile();
        $badges = $this->marketplaceService->getMyBadges();
        $availableBadges = $this->marketplaceService->getAvailableBadges();

        return view('marketplace.freelancer.profile', [
            'profile' => $profile,
            'badges' => $badges,
            'availableBadges' => $availableBadges,
        ]);
    }

    /**
     * Update freelancer profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'professional_title' => 'required|string|max:255',
            'bio' => 'required|string|min:50|max:2000',
            'overview' => 'nullable|string|max:5000',
            'hourly_rate' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:10',
            'skills' => 'required|array|min:1|max:20',
            'skills.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'experience_level' => 'required|in:entry,intermediate,expert',
            'availability' => 'required|in:full_time,part_time,hourly,not_available',
            'hours_per_week' => 'nullable|integer|min:1|max:80',
            'available_for_remote' => 'boolean',
            'available_for_onsite' => 'boolean',
            'preferred_project_size' => 'nullable|in:small,medium,large',
            'portfolio' => 'nullable|array',
            'portfolio.*.title' => 'required_with:portfolio|string|max:255',
            'portfolio.*.url' => 'required_with:portfolio|url',
            'certifications' => 'nullable|array',
        ]);

        $this->marketplaceService->forUser(auth()->user());
        $profile = $this->marketplaceService->updateFreelancerProfile($request->all());

        return response()->json([
            'success' => true,
            'profile' => $profile,
            'message' => 'Profile updated successfully!',
        ]);
    }

    /**
     * My proposals page.
     */
    public function proposals(Request $request): View
    {
        $this->marketplaceService->forUser(auth()->user());
        
        $status = $request->get('status');
        $proposals = $this->marketplaceService->getMyProposals($status);

        return view('marketplace.freelancer.proposals', [
            'proposals' => $proposals,
            'currentStatus' => $status,
        ]);
    }

    /**
     * My contracts page.
     */
    public function contracts(Request $request): View
    {
        $this->marketplaceService->forUser(auth()->user());
        
        $status = $request->get('status');
        $contracts = $this->marketplaceService->getMyContracts('freelancer', $status);

        return view('marketplace.freelancer.contracts', [
            'contracts' => $contracts,
            'currentStatus' => $status,
        ]);
    }

    /**
     * My earnings page.
     */
    public function earnings(): View
    {
        $this->marketplaceService->forUser(auth()->user());
        $stats = $this->marketplaceService->getFreelancerStats();
        $profile = $this->marketplaceService->getFreelancerProfile();

        // Get recent payments
        $recentPayments = \App\Models\MarketplaceEscrow::where('payee_id', auth()->id())
            ->where('status', 'released')
            ->with('contract.project')
            ->orderByDesc('released_at')
            ->limit(20)
            ->get();

        return view('marketplace.freelancer.earnings', [
            'stats' => $stats,
            'profile' => $profile,
            'recentPayments' => $recentPayments,
        ]);
    }

    /**
     * Apply for a skill badge.
     */
    public function applyForBadge(Request $request, SkillBadge $badge): JsonResponse
    {
        $request->validate([
            'evidence' => 'nullable|string|max:2000',
        ]);

        try {
            $this->marketplaceService->forUser(auth()->user());
            $userBadge = $this->marketplaceService->applyForBadge($badge, $request->evidence);

            return response()->json([
                'success' => true,
                'badge' => $userBadge->load('badge'),
                'message' => $userBadge->isVerified() 
                    ? 'Badge earned successfully!' 
                    : 'Badge application submitted for verification.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get recommended projects.
     */
    public function recommendedProjects(): JsonResponse
    {
        $this->marketplaceService->forUser(auth()->user());
        $projects = $this->marketplaceService->getRecommendedProjects(10);

        return response()->json([
            'success' => true,
            'projects' => $projects,
        ]);
    }
}
