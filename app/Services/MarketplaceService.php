<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FreelancerProfile;
use App\Models\MarketplaceContract;
use App\Models\MarketplaceEscrow;
use App\Models\MarketplaceMilestone;
use App\Models\MarketplaceProject;
use App\Models\MarketplaceProposal;
use App\Models\MarketplaceReview;
use App\Models\PaymentTransaction;
use App\Models\SkillBadge;
use App\Models\User;
use App\Models\UserSkillBadge;
use App\Services\AI\AIService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api as RazorpayApi;

class MarketplaceService extends AIService
{
    protected ?User $user = null;
    protected RazorpayApi $razorpay;
    protected array $payuConfig;

    public function __construct()
    {
        parent::__construct();
        
        // Initialize Razorpay
        if (config('payment.razorpay.key') && config('payment.razorpay.secret')) {
            $this->razorpay = new RazorpayApi(
                config('payment.razorpay.key'),
                config('payment.razorpay.secret')
            );
        }

        // Initialize PayU config
        $this->payuConfig = config('payment.payu', []);
    }

    public function forUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    // ============================================================
    // PROJECT MANAGEMENT
    // ============================================================

    /**
     * Search and filter projects.
     */
    public function searchProjects(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MarketplaceProject::query()
            ->with(['employer', 'company'])
            ->published()
            ->open();

        // Category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Skills filter
        if (!empty($filters['skills'])) {
            $query->withSkills((array) $filters['skills']);
        }

        // Budget range
        if (!empty($filters['budget_min'])) {
            $query->where('budget_max', '>=', $filters['budget_min']);
        }
        if (!empty($filters['budget_max'])) {
            $query->where('budget_min', '<=', $filters['budget_max']);
        }

        // Project type
        if (!empty($filters['project_type'])) {
            $query->where('project_type', $filters['project_type']);
        }

        // Experience level
        if (!empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }

        // Remote only
        if (!empty($filters['remote_only'])) {
            $query->where('allows_remote', true);
        }

        // Duration
        if (!empty($filters['max_duration_days'])) {
            $query->where('estimated_duration_days', '<=', $filters['max_duration_days']);
        }

        // Search query
        if (!empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'newest';
        match ($sortBy) {
            'budget_high' => $query->orderByDesc('budget_max'),
            'budget_low' => $query->orderBy('budget_min'),
            'proposals' => $query->orderBy('proposals_count'),
            'deadline' => $query->orderBy('deadline'),
            default => $query->orderByDesc('published_at'),
        };

        // Featured first
        $query->orderByDesc('is_featured')->orderByDesc('is_urgent');

        return $query->paginate($perPage);
    }

    /**
     * Get recommended projects for a freelancer.
     */
    public function getRecommendedProjects(int $limit = 10): Collection
    {
        $profile = $this->getFreelancerProfile();
        if (!$profile) {
            return MarketplaceProject::open()->published()->featured()->limit($limit)->get();
        }

        $skills = $profile->skills ?? [];

        return MarketplaceProject::query()
            ->with(['employer', 'company'])
            ->open()
            ->published()
            ->withSkills($skills)
            ->where('experience_level', '<=', $this->mapExperienceLevel($profile->experience_level))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data): MarketplaceProject
    {
        $project = MarketplaceProject::create([
            'employer_id' => $this->user->id,
            'company_id' => $data['company_id'] ?? $this->user->company_id ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'requirements' => $data['requirements'] ?? null,
            'deliverables' => $data['deliverables'] ?? null,
            'project_type' => $data['project_type'] ?? 'fixed_price',
            'category' => $data['category'] ?? 'other',
            'skills_required' => $data['skills_required'] ?? [],
            'budget_min' => $data['budget_min'] ?? null,
            'budget_max' => $data['budget_max'] ?? null,
            'hourly_rate_min' => $data['hourly_rate_min'] ?? null,
            'hourly_rate_max' => $data['hourly_rate_max'] ?? null,
            'currency' => $data['currency'] ?? 'INR',
            'experience_level' => $data['experience_level'] ?? 'intermediate',
            'estimated_duration_days' => $data['estimated_duration_days'] ?? null,
            'duration_type' => $data['duration_type'] ?? 'weeks',
            'allows_remote' => $data['allows_remote'] ?? true,
            'location' => $data['location'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'is_urgent' => $data['is_urgent'] ?? false,
            'status' => $data['publish'] ?? false ? 'open' : 'draft',
            'published_at' => $data['publish'] ?? false ? now() : null,
        ]);

        return $project;
    }

    // ============================================================
    // FREELANCER PROFILE
    // ============================================================

    /**
     * Get or create freelancer profile for current user.
     */
    public function getFreelancerProfile(): ?FreelancerProfile
    {
        if (!$this->user) {
            return null;
        }

        return FreelancerProfile::where('user_id', $this->user->id)->first();
    }

    /**
     * Create or update freelancer profile.
     */
    public function updateFreelancerProfile(array $data): FreelancerProfile
    {
        return FreelancerProfile::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'professional_title' => $data['professional_title'],
                'bio' => $data['bio'],
                'overview' => $data['overview'] ?? null,
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'currency' => $data['currency'] ?? 'INR',
                'skills' => $data['skills'] ?? [],
                'languages' => $data['languages'] ?? [],
                'experience_level' => $data['experience_level'] ?? 'intermediate',
                'availability' => $data['availability'] ?? 'full_time',
                'hours_per_week' => $data['hours_per_week'] ?? null,
                'available_for_remote' => $data['available_for_remote'] ?? true,
                'available_for_onsite' => $data['available_for_onsite'] ?? false,
                'preferred_project_size' => $data['preferred_project_size'] ?? null,
                'portfolio' => $data['portfolio'] ?? [],
                'certifications' => $data['certifications'] ?? [],
            ]
        );
    }

    /**
     * Search freelancers.
     */
    public function searchFreelancers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FreelancerProfile::query()
            ->with(['user', 'badges.badge'])
            ->available();

        // Skills filter
        if (!empty($filters['skills'])) {
            $query->withSkills((array) $filters['skills']);
        }

        // Hourly rate range
        if (!empty($filters['rate_min'])) {
            $query->where('hourly_rate', '>=', $filters['rate_min']);
        }
        if (!empty($filters['rate_max'])) {
            $query->where('hourly_rate', '<=', $filters['rate_max']);
        }

        // Experience level
        if (!empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }

        // Verified only
        if (!empty($filters['verified_only'])) {
            $query->verified();
        }

        // Top rated
        if (!empty($filters['top_rated'])) {
            $query->topRated();
        }

        // Search query
        if (!empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('professional_title', 'like', "%{$searchTerm}%")
                  ->orWhere('bio', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                      $uq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'rating';
        match ($sortBy) {
            'rate_low' => $query->orderBy('hourly_rate'),
            'rate_high' => $query->orderByDesc('hourly_rate'),
            'projects' => $query->orderByDesc('completed_projects'),
            'earnings' => $query->orderByDesc('total_earnings'),
            default => $query->orderByDesc('average_rating')->orderByDesc('total_reviews'),
        };

        // Featured and verified first
        $query->orderByDesc('is_featured')->orderByDesc('is_verified');

        return $query->paginate($perPage);
    }

    // ============================================================
    // PROPOSALS
    // ============================================================

    /**
     * Submit a proposal for a project.
     */
    public function submitProposal(MarketplaceProject $project, array $data): MarketplaceProposal
    {
        $profile = $this->getFreelancerProfile();
        
        if (!$profile) {
            throw new \Exception('You must complete your freelancer profile before submitting proposals.');
        }

        if (!$project->canReceiveProposals()) {
            throw new \Exception('This project is no longer accepting proposals.');
        }

        if (!$profile->canApplyToProject($project)) {
            throw new \Exception('You have already submitted a proposal for this project.');
        }

        return MarketplaceProposal::create([
            'project_id' => $project->id,
            'freelancer_id' => $this->user->id,
            'cover_letter' => $data['cover_letter'],
            'proposed_amount' => $data['proposed_amount'],
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'currency' => $project->currency,
            'estimated_duration_days' => $data['estimated_duration_days'] ?? null,
            'milestones' => $data['milestones'] ?? null,
            'relevant_experience' => $data['relevant_experience'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Get proposals for a project (employer view).
     */
    public function getProjectProposals(MarketplaceProject $project): Collection
    {
        return $project->proposals()
            ->with(['freelancer', 'freelancerProfile.badges.badge'])
            ->orderByDesc('is_boosted')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get freelancer's proposals.
     */
    public function getMyProposals(string $status = null): Collection
    {
        $query = MarketplaceProposal::where('freelancer_id', $this->user->id)
            ->with(['project.employer', 'project.company']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->get();
    }

    // ============================================================
    // CONTRACTS & MILESTONES
    // ============================================================

    /**
     * Accept a proposal and create contract.
     */
    public function acceptProposal(MarketplaceProposal $proposal, array $milestones = null): MarketplaceContract
    {
        return DB::transaction(function () use ($proposal, $milestones) {
            $contract = $proposal->accept();

            // Create milestones if provided
            if ($milestones && count($milestones) > 0) {
                $contract->createMilestones($milestones);
            } elseif ($proposal->milestones && count($proposal->milestones) > 0) {
                // Use milestones from proposal
                $contract->createMilestones($proposal->milestones);
            } else {
                // Create single milestone for the full amount
                $contract->createMilestones([
                    [
                        'title' => 'Project Completion',
                        'description' => 'Complete project delivery',
                        'amount' => $contract->total_amount,
                    ]
                ]);
            }

            return $contract->load('milestones');
        });
    }

    /**
     * Fund a milestone (employer action).
     */
    public function fundMilestone(
        MarketplaceMilestone $milestone,
        string $paymentGateway,
        string $transactionId
    ): MarketplaceEscrow {
        $escrow = MarketplaceEscrow::createForMilestone($milestone);
        $escrow->fund($paymentGateway, $transactionId);

        return $escrow;
    }

    /**
     * Submit milestone work (freelancer action).
     */
    public function submitMilestone(
        MarketplaceMilestone $milestone,
        string $note = null,
        array $files = null
    ): void {
        if (!$milestone->isFunded() && !$milestone->isInProgress()) {
            throw new \Exception('This milestone must be funded before work can be submitted.');
        }

        $milestone->submit($note, $files);
    }

    /**
     * Request revision on milestone (employer action).
     */
    public function requestRevision(MarketplaceMilestone $milestone, string $note): void
    {
        if (!$milestone->isSubmitted()) {
            throw new \Exception('Can only request revision on submitted milestones.');
        }

        $milestone->requestRevision($note);
    }

    /**
     * Approve and release milestone payment (employer action).
     */
    public function approveMilestone(MarketplaceMilestone $milestone): void
    {
        if (!$milestone->isSubmitted()) {
            throw new \Exception('Can only approve submitted milestones.');
        }

        $milestone->approve();
        $milestone->release();
    }

    /**
     * Get user's contracts.
     */
    public function getMyContracts(string $role = null, string $status = null): Collection
    {
        $query = MarketplaceContract::query()
            ->with(['project', 'employer', 'freelancer', 'milestones']);

        if ($role === 'employer') {
            $query->where('employer_id', $this->user->id);
        } elseif ($role === 'freelancer') {
            $query->where('freelancer_id', $this->user->id);
        } else {
            $query->forUser($this->user->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->get();
    }

    // ============================================================
    // REVIEWS
    // ============================================================

    /**
     * Submit a review for a completed contract.
     */
    public function submitReview(MarketplaceContract $contract, array $data): MarketplaceReview
    {
        if (!$contract->isCompleted()) {
            throw new \Exception('Reviews can only be submitted for completed contracts.');
        }

        $isEmployer = $contract->employer_id === $this->user->id;
        $revieweeId = $isEmployer ? $contract->freelancer_id : $contract->employer_id;

        // Check if already reviewed
        $existingReview = $contract->reviews()
            ->where('reviewer_id', $this->user->id)
            ->first();

        if ($existingReview) {
            throw new \Exception('You have already submitted a review for this contract.');
        }

        $review = MarketplaceReview::create([
            'contract_id' => $contract->id,
            'reviewer_id' => $this->user->id,
            'reviewee_id' => $revieweeId,
            'reviewer_type' => $isEmployer ? 'employer' : 'freelancer',
            'overall_rating' => $data['overall_rating'],
            'communication_rating' => $data['communication_rating'] ?? null,
            'quality_rating' => $data['quality_rating'] ?? null,
            'timeliness_rating' => $data['timeliness_rating'] ?? null,
            'professionalism_rating' => $data['professionalism_rating'] ?? null,
            'value_rating' => $data['value_rating'] ?? null,
            'cooperation_rating' => $data['cooperation_rating'] ?? null,
            'review_text' => $data['review_text'],
            'private_feedback' => $data['private_feedback'] ?? null,
            'would_recommend' => $data['would_recommend'] ?? true,
            'would_hire_again' => $data['would_hire_again'] ?? null,
            'skills_endorsed' => $data['skills_endorsed'] ?? null,
            'status' => 'pending',
        ]);

        // Auto-publish if no moderation needed
        $review->publish();

        return $review;
    }

    // ============================================================
    // ESCROW PAYMENTS
    // ============================================================

    /**
     * Create escrow payment for a milestone.
     */
    public function createEscrow(
        MarketplaceMilestone $milestone,
        string $gateway = 'razorpay'
    ): array {
        $contract = $milestone->contract;
        
        if (!$contract || $contract->employer_id !== $this->user->id) {
            throw new \Exception('Unauthorized to create escrow for this milestone.');
        }

        $amount = $milestone->amount;
        $orderId = 'ESC_' . time() . '_' . $milestone->id;

        // Create escrow record
        $escrow = MarketplaceEscrow::create([
            'contract_id' => $contract->id,
            'milestone_id' => $milestone->id,
            'payer_id' => $contract->employer_id,
            'payee_id' => $contract->freelancer_id,
            'amount' => $amount,
            'platform_fee' => $amount * 0.05, // 5% platform fee
            'net_amount' => $amount * 0.95,
            'currency' => $contract->currency ?? 'INR',
            'payment_gateway' => $gateway,
            'status' => 'pending',
        ]);

        return match($gateway) {
            'razorpay' => $this->createRazorpayEscrowOrder($escrow, $orderId),
            'payu' => $this->createPayUEscrowOrder($escrow, $orderId),
            default => throw new \Exception("Unsupported payment gateway: {$gateway}"),
        };
    }

    /**
     * Create Razorpay order for escrow funding.
     */
    protected function createRazorpayEscrowOrder(MarketplaceEscrow $escrow, string $orderId): array
    {
        try {
            $orderData = [
                'receipt' => $orderId,
                'amount' => (int)($escrow->amount * 100), // Amount in paise
                'currency' => $escrow->currency ?? 'INR',
                'notes' => [
                    'escrow_id' => $escrow->id,
                    'contract_id' => $escrow->contract_id,
                    'milestone_id' => $escrow->milestone_id,
                    'type' => 'escrow_funding',
                ]
            ];

            $order = $this->razorpay->order->create($orderData);

            // Create payment transaction
            $transaction = PaymentTransaction::create([
                'user_id' => $escrow->payer_id,
                'transaction_id' => $order->id,
                'order_id' => $orderId,
                'payment_gateway' => 'razorpay',
                'amount' => $escrow->amount,
                'currency' => $escrow->currency,
                'status' => PaymentTransaction::STATUS_PENDING,
                'initiated_at' => now(),
                'metadata' => [
                    'razorpay_order_id' => $order->id,
                    'escrow_id' => $escrow->id,
                    'type' => 'escrow',
                ],
            ]);

            $escrow->update([
                'transaction_id' => $transaction->id,
                'gateway_order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'escrow_id' => $escrow->id,
                'transaction_id' => $transaction->id,
                'order_id' => $order->id,
                'amount' => $order->amount / 100,
                'currency' => $order->currency,
                'key' => config('payment.razorpay.key'),
                'name' => config('app.name'),
                'description' => 'Escrow Payment - Milestone: ' . $escrow->milestone->title,
                'image' => config('payment.razorpay.logo'),
                'theme' => ['color' => config('payment.razorpay.theme_color')],
                'prefill' => [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'contact' => $this->user->phone ?? '',
                ],
                'gateway' => 'razorpay',
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay Escrow Order Creation Failed', [
                'escrow_id' => $escrow->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create PayU order for escrow funding.
     */
    protected function createPayUEscrowOrder(MarketplaceEscrow $escrow, string $orderId): array
    {
        try {
            $txnId = 'ESCTXN_' . time() . '_' . $escrow->id;
            
            $payuData = [
                'key' => $this->payuConfig['merchant_key'],
                'txnid' => $txnId,
                'amount' => number_format($escrow->amount, 2, '.', ''),
                'productinfo' => 'Escrow Payment - Milestone',
                'firstname' => explode(' ', $this->user->name)[0],
                'email' => $this->user->email,
                'phone' => $this->user->phone ?? '9999999999',
                'surl' => route('marketplace.escrow.payu.success'),
                'furl' => route('marketplace.escrow.payu.failure'),
                'service_provider' => 'payu_paisa',
                'udf1' => $escrow->id,
                'udf2' => $this->user->id,
                'udf3' => $orderId,
            ];

            // Generate hash
            $hashString = $this->payuConfig['merchant_key'] . '|' . 
                         $payuData['txnid'] . '|' .
                         $payuData['amount'] . '|' .
                         $payuData['productinfo'] . '|' .
                         $payuData['firstname'] . '|' .
                         $payuData['email'] . '|||||||||||' .
                         $this->payuConfig['merchant_salt'];
            
            $payuData['hash'] = strtolower(hash('sha512', $hashString));

            // Create payment transaction
            $transaction = PaymentTransaction::create([
                'user_id' => $escrow->payer_id,
                'transaction_id' => $txnId,
                'order_id' => $orderId,
                'payment_gateway' => 'payu',
                'amount' => $escrow->amount,
                'currency' => $escrow->currency,
                'status' => PaymentTransaction::STATUS_PENDING,
                'initiated_at' => now(),
                'metadata' => [
                    'payu_txnid' => $txnId,
                    'escrow_id' => $escrow->id,
                    'type' => 'escrow',
                ],
            ]);

            $escrow->update([
                'transaction_id' => $transaction->id,
                'gateway_order_id' => $txnId,
            ]);

            return [
                'success' => true,
                'escrow_id' => $escrow->id,
                'transaction_id' => $transaction->id,
                'order_id' => $orderId,
                'amount' => $escrow->amount,
                'currency' => $escrow->currency,
                'gateway' => 'payu',
                'payment_url' => $this->payuConfig['payment_url'],
                'form_data' => $payuData,
            ];
        } catch (\Exception $e) {
            Log::error('PayU Escrow Order Creation Failed', [
                'escrow_id' => $escrow->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify and process escrow payment.
     */
    public function verifyEscrowPayment(array $data, string $gateway): bool
    {
        return match($gateway) {
            'razorpay' => $this->verifyRazorpayEscrowPayment($data),
            'payu' => $this->verifyPayUEscrowPayment($data),
            default => false,
        };
    }

    /**
     * Verify Razorpay escrow payment.
     */
    protected function verifyRazorpayEscrowPayment(array $data): bool
    {
        try {
            $attributes = [
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature']
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);

            // Find and update escrow
            $escrow = MarketplaceEscrow::where('gateway_order_id', $data['razorpay_order_id'])->first();
            
            if ($escrow) {
                $this->fundEscrow($escrow, $data['razorpay_payment_id']);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Razorpay Escrow Verification Failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify PayU escrow payment.
     */
    protected function verifyPayUEscrowPayment(array $data): bool
    {
        try {
            // Reverse hash calculation for PayU
            $hashString = $this->payuConfig['merchant_salt'] . '|' .
                         $data['status'] . '|||||||||||' .
                         ($data['udf3'] ?? '') . '|' .
                         ($data['udf2'] ?? '') . '|' .
                         ($data['udf1'] ?? '') . '|' .
                         $data['email'] . '|' .
                         $data['firstname'] . '|' .
                         $data['productinfo'] . '|' .
                         $data['amount'] . '|' .
                         $data['txnid'] . '|' .
                         $this->payuConfig['merchant_key'];

            $hash = strtolower(hash('sha512', $hashString));

            if ($hash === $data['hash'] && $data['status'] === 'success') {
                $escrow = MarketplaceEscrow::find($data['udf1']);
                
                if ($escrow) {
                    $this->fundEscrow($escrow, $data['txnid']);
                }
                
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('PayU Escrow Verification Failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark escrow as funded.
     */
    public function fundEscrow(MarketplaceEscrow $escrow, string $paymentId = null): void
    {
        DB::transaction(function () use ($escrow, $paymentId) {
            $escrow->update([
                'status' => 'funded',
                'funded_at' => now(),
                'gateway_payment_id' => $paymentId,
            ]);

            // Update transaction
            if ($escrow->transaction) {
                $escrow->transaction->update([
                    'status' => PaymentTransaction::STATUS_SUCCESS,
                    'completed_at' => now(),
                    'metadata' => array_merge(
                        $escrow->transaction->metadata ?? [],
                        ['gateway_payment_id' => $paymentId]
                    ),
                ]);
            }

            // Update milestone status
            if ($escrow->milestone) {
                $escrow->milestone->update(['status' => 'funded']);
            }

            Log::info('Escrow funded', ['escrow_id' => $escrow->id]);
        });
    }

    /**
     * Release escrow payment to freelancer.
     */
    public function releaseEscrow(MarketplaceEscrow $escrow): void
    {
        if (!$escrow->isFunded()) {
            throw new \Exception('Escrow is not in funded status.');
        }

        $contract = $escrow->contract;
        
        if ($contract->employer_id !== $this->user->id) {
            throw new \Exception('Only the employer can release escrow funds.');
        }

        DB::transaction(function () use ($escrow) {
            $escrow->update([
                'status' => 'released',
                'released_at' => now(),
                'released_by' => $this->user->id,
            ]);

            // Update milestone
            if ($escrow->milestone) {
                $escrow->milestone->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Update freelancer earnings
            $freelancerProfile = FreelancerProfile::where('user_id', $escrow->payee_id)->first();
            if ($freelancerProfile) {
                $freelancerProfile->increment('total_earnings', $escrow->net_amount);
            }

            Log::info('Escrow released', [
                'escrow_id' => $escrow->id,
                'amount' => $escrow->net_amount,
                'freelancer_id' => $escrow->payee_id,
            ]);
        });
    }

    /**
     * Dispute an escrow payment.
     */
    public function disputeEscrow(MarketplaceEscrow $escrow, string $reason): void
    {
        if (!in_array($escrow->status, ['funded', 'held'])) {
            throw new \Exception('Cannot dispute escrow in current status.');
        }

        $contract = $escrow->contract;
        
        if (!in_array($this->user->id, [$contract->employer_id, $contract->freelancer_id])) {
            throw new \Exception('Only contract parties can dispute escrow.');
        }

        $escrow->update([
            'status' => 'disputed',
            'dispute_reason' => $reason,
            'disputed_at' => now(),
            'disputed_by' => $this->user->id,
        ]);

        Log::info('Escrow disputed', [
            'escrow_id' => $escrow->id,
            'disputed_by' => $this->user->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Refund escrow to employer.
     */
    public function refundEscrow(MarketplaceEscrow $escrow, string $reason = null): void
    {
        if (!in_array($escrow->status, ['funded', 'held', 'disputed'])) {
            throw new \Exception('Cannot refund escrow in current status.');
        }

        DB::transaction(function () use ($escrow, $reason) {
            // Process refund through payment gateway if needed
            if ($escrow->gateway_payment_id && $escrow->payment_gateway === 'razorpay') {
                try {
                    $payment = $this->razorpay->payment->fetch($escrow->gateway_payment_id);
                    $payment->refund([
                        'amount' => (int)($escrow->amount * 100),
                        'notes' => [
                            'escrow_id' => $escrow->id,
                            'reason' => $reason ?? 'Escrow refund',
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Escrow refund failed', [
                        'escrow_id' => $escrow->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception('Failed to process refund through payment gateway.');
                }
            }

            $escrow->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);

            // Update milestone
            if ($escrow->milestone) {
                $escrow->milestone->update(['status' => 'refunded']);
            }

            Log::info('Escrow refunded', [
                'escrow_id' => $escrow->id,
                'amount' => $escrow->amount,
            ]);
        });
    }

    // ============================================================
    // SKILL BADGES
    // ============================================================

    /**
     * Get available skill badges.
     */
    public function getAvailableBadges(string $category = null): Collection
    {
        $query = SkillBadge::active();

        if ($category) {
            $query->byCategory($category);
        }

        return $query->get();
    }

    /**
     * Apply for a skill badge.
     */
    public function applyForBadge(SkillBadge $badge, string $evidence = null): UserSkillBadge
    {
        // Check if already has this badge
        $existing = UserSkillBadge::where('user_id', $this->user->id)
            ->where('badge_id', $badge->id)
            ->first();

        if ($existing && $existing->isVerified() && !$existing->isExpired()) {
            throw new \Exception('You already have this badge.');
        }

        return UserSkillBadge::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'badge_id' => $badge->id,
            ],
            [
                'status' => $badge->requires_verification ? 'pending' : 'verified',
                'verification_evidence' => $evidence,
                'verified_by' => $badge->requires_verification ? null : 'system',
                'verified_at' => $badge->requires_verification ? null : now(),
                'earned_at' => $badge->requires_verification ? null : now(),
            ]
        );
    }

    /**
     * Get user's badges.
     */
    public function getMyBadges(): Collection
    {
        return UserSkillBadge::where('user_id', $this->user->id)
            ->with('badge')
            ->active()
            ->get();
    }

    // ============================================================
    // AI-POWERED FEATURES
    // ============================================================

    /**
     * Generate AI-optimized proposal for a project.
     */
    public function generateProposalSuggestion(MarketplaceProject $project): array
    {
        $profile = $this->getFreelancerProfile();
        
        $prompt = <<<PROMPT
You are an expert freelancer proposal writer. Generate a compelling proposal for the following project.

PROJECT DETAILS:
- Title: {$project->title}
- Description: {$project->description}
- Requirements: {$project->requirements}
- Budget: {$project->budget_display}
- Skills Required: {$this->formatSkills($project->skills_required)}
- Experience Level: {$project->experience_level}
- Duration: {$project->duration_display}

FREELANCER PROFILE:
- Title: {$profile?->professional_title}
- Bio: {$profile?->bio}
- Skills: {$this->formatSkills($profile?->skills ?? [])}
- Experience Level: {$profile?->experience_level}
- Hourly Rate: {$profile?->hourly_rate_display}
- Completed Projects: {$profile?->completed_projects}
- Average Rating: {$profile?->average_rating}

Generate a JSON response with:
{
    "cover_letter": "A personalized, professional cover letter (300-400 words) that highlights relevant experience and enthusiasm",
    "key_points": ["3-5 key selling points to emphasize"],
    "suggested_amount": "A competitive bid amount based on the project scope",
    "estimated_days": "Realistic timeline in days",
    "milestones": [
        {"title": "Milestone 1", "description": "Description", "amount_percent": 30},
        {"title": "Milestone 2", "description": "Description", "amount_percent": 40},
        {"title": "Milestone 3", "description": "Description", "amount_percent": 30}
    ]
}
PROMPT;

        try {
            return $this->callAIForJSON($prompt, 'You are an expert freelance proposal strategist.');
        } catch (\Exception $e) {
            Log::error('Failed to generate proposal suggestion', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * AI-powered skill matching for projects.
     */
    public function matchProjectWithFreelancers(MarketplaceProject $project, int $limit = 10): Collection
    {
        // Get freelancers with matching skills
        $freelancers = FreelancerProfile::query()
            ->with(['user', 'badges.badge'])
            ->available()
            ->verified()
            ->withSkills($project->skills_required ?? [])
            ->topRated()
            ->limit($limit * 2) // Get more for AI ranking
            ->get();

        if ($freelancers->isEmpty()) {
            // Fallback to any available freelancers
            return FreelancerProfile::query()
                ->available()
                ->orderByDesc('average_rating')
                ->limit($limit)
                ->get();
        }

        // Use AI to rank the best matches
        $prompt = <<<PROMPT
Rank the following freelancers for this project based on skill match, experience, and ratings.

PROJECT:
- Title: {$project->title}
- Skills Required: {$this->formatSkills($project->skills_required)}
- Experience Level: {$project->experience_level}
- Budget: {$project->budget_display}

FREELANCERS (JSON):
{$freelancers->toJson()}

Return a JSON array of freelancer IDs ranked from best to worst match:
{"ranked_ids": [1, 5, 3, 2, 4], "match_scores": {"1": 95, "5": 88, "3": 82, "2": 75, "4": 70}}
PROMPT;

        try {
            $result = $this->callAIForJSON($prompt);
            if (!empty($result['ranked_ids'])) {
                $orderedIds = array_slice($result['ranked_ids'], 0, $limit);
                return $freelancers->whereIn('id', $orderedIds)
                    ->sortBy(function ($f) use ($orderedIds) {
                        return array_search($f->id, $orderedIds);
                    })
                    ->values();
            }
        } catch (\Exception $e) {
            Log::error('AI matching failed', ['error' => $e->getMessage()]);
        }

        // Fallback: return top rated
        return $freelancers->take($limit);
    }

    /**
     * Generate project description from brief.
     */
    public function enhanceProjectDescription(string $brief, string $category): array
    {
        $prompt = <<<PROMPT
Enhance this project brief into a professional, detailed project posting.

BRIEF: {$brief}
CATEGORY: {$category}

Generate a JSON response with:
{
    "title": "Compelling project title (max 100 chars)",
    "description": "Detailed project description (200-400 words) with clear scope and expectations",
    "requirements": "Bullet points of key requirements and qualifications needed",
    "deliverables": "List of expected deliverables",
    "skills_suggested": ["skill1", "skill2", "skill3"],
    "experience_level_suggested": "entry|intermediate|expert",
    "duration_estimate_days": 14
}
PROMPT;

        try {
            return $this->callAIForJSON($prompt, 'You are an expert project manager who writes clear, professional project descriptions.');
        } catch (\Exception $e) {
            Log::error('Failed to enhance project description', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ============================================================
    // STATISTICS
    // ============================================================

    /**
     * Get marketplace statistics for dashboard.
     */
    public function getMarketplaceStats(): array
    {
        return [
            'total_projects' => MarketplaceProject::open()->count(),
            'total_freelancers' => FreelancerProfile::available()->count(),
            'verified_freelancers' => FreelancerProfile::verified()->count(),
            'total_contracts' => MarketplaceContract::count(),
            'completed_contracts' => MarketplaceContract::completed()->count(),
            'total_value' => MarketplaceContract::completed()->sum('total_amount'),
        ];
    }

    /**
     * Get freelancer earnings and stats.
     */
    public function getFreelancerStats(): array
    {
        $profile = $this->getFreelancerProfile();

        if (!$profile) {
            return [];
        }

        $contracts = MarketplaceContract::where('freelancer_id', $this->user->id);

        return [
            'total_earnings' => $profile->total_earnings,
            'pending_earnings' => MarketplaceEscrow::where('payee_id', $this->user->id)
                ->whereIn('status', ['funded', 'held'])
                ->sum('net_amount'),
            'completed_projects' => $profile->completed_projects,
            'ongoing_projects' => $profile->ongoing_projects,
            'average_rating' => $profile->average_rating,
            'total_reviews' => $profile->total_reviews,
            'success_rate' => $profile->success_rate,
            'active_proposals' => MarketplaceProposal::where('freelancer_id', $this->user->id)
                ->pending()
                ->count(),
        ];
    }

    /**
     * Get employer stats.
     */
    public function getEmployerStats(): array
    {
        return [
            'total_projects' => MarketplaceProject::where('employer_id', $this->user->id)->count(),
            'open_projects' => MarketplaceProject::where('employer_id', $this->user->id)->open()->count(),
            'active_contracts' => MarketplaceContract::where('employer_id', $this->user->id)->active()->count(),
            'completed_contracts' => MarketplaceContract::where('employer_id', $this->user->id)->completed()->count(),
            'total_spent' => MarketplaceEscrow::where('payer_id', $this->user->id)->released()->sum('amount'),
            'pending_proposals' => MarketplaceProposal::whereHas('project', function ($q) {
                $q->where('employer_id', $this->user->id);
            })->pending()->count(),
        ];
    }

    // ============================================================
    // HELPERS
    // ============================================================

    protected function formatSkills(?array $skills): string
    {
        return $skills ? implode(', ', $skills) : 'Not specified';
    }

    protected function mapExperienceLevel(string $level): int
    {
        return match($level) {
            'entry' => 1,
            'intermediate' => 2,
            'expert' => 3,
            default => 2,
        };
    }
}
