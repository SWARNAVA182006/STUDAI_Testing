<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    protected $paymentService;
    
    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->middleware('auth')->except(['pricing']);
        $this->paymentService = $paymentService;
    }
    
    /**
     * Show pricing page (public)
     */
    public function pricing()
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get();
        
        $userPlan = null;
        if (Auth::check()) {
            $userPlan = Auth::user()->subscription?->subscriptionPlan;
        }
        
        return view('subscriptions.pricing', compact('plans', 'userPlan'));
    }
    
    /**
     * Show user's current subscription
     */
    public function index()
    {
        $user = Auth::user();
        $subscription = $user->subscription;
        
        if (!$subscription) {
            return redirect()->route('subscriptions.pricing');
        }
        
        $subscription->load('subscriptionPlan');
        
        // Get usage statistics
        $usage = [
            'applications' => [
                'used' => $subscription->applications_used_this_month,
                'limit' => $subscription->subscriptionPlan->applications_limit,
                'percentage' => $this->calculatePercentage(
                    $subscription->applications_used_this_month,
                    $subscription->subscriptionPlan->applications_limit
                ),
            ],
            'ai_credits' => [
                'used' => $subscription->ai_credits_used_this_month,
                'limit' => $subscription->subscriptionPlan->ai_credits,
                'percentage' => $this->calculatePercentage(
                    $subscription->ai_credits_used_this_month,
                    $subscription->subscriptionPlan->ai_credits
                ),
            ],
            'assessments' => [
                'used' => $subscription->assessments_taken_this_month ?? 0,
                'limit' => $subscription->subscriptionPlan->assessment_limit,
                'percentage' => $this->calculatePercentage(
                    $subscription->assessments_taken_this_month ?? 0,
                    $subscription->subscriptionPlan->assessment_limit
                ),
            ],
        ];
        
        // Get payment history
        $transactions = $user->paymentTransactions()
            ->where('user_subscription_id', $subscription->id)
            ->latest()
            ->take(10)
            ->get();
        
        return view('subscriptions.index', compact('subscription', 'usage', 'transactions'));
    }
    
    /**
     * Show plan selection for subscription/upgrade
     */
    public function selectPlan(Request $request)
    {
        $planId = $request->get('plan_id');
        $plan = SubscriptionPlan::findOrFail($planId);
        
        $user = Auth::user();
        $currentSubscription = $user->subscription;
        
        $isUpgrade = $currentSubscription && $currentSubscription->subscriptionPlan->price_monthly < $plan->price_monthly;
        $isDowngrade = $currentSubscription && $currentSubscription->subscriptionPlan->price_monthly > $plan->price_monthly;
        
        return view('subscriptions.select-plan', compact('plan', 'currentSubscription', 'isUpgrade', 'isDowngrade'));
    }
    
    /**
     * Initiate subscription payment
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'gateway' => 'required|in:razorpay,payu',
        ]);
        
        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        
        // Check if plan is free
        if ($plan->isFree()) {
            return $this->activateFreePlan($user, $plan);
        }
        
        // Create payment order
        $orderData = $this->paymentService->createOrder(
            $user,
            $plan,
            $validated['gateway'],
            $validated['billing_cycle']
        );
        
        if (!$orderData['success']) {
            return back()->with('error', $orderData['error'] ?? 'Failed to initiate payment');
        }
        
        // Store billing cycle in session for later
        session(['billing_cycle' => $validated['billing_cycle']]);
        
        if ($validated['gateway'] === 'razorpay') {
            return view('payments.razorpay', ['orderData' => $orderData]);
        } else {
            return view('payments.payu', ['orderData' => $orderData]);
        }
    }
    
    /**
     * Activate free plan without payment
     */
    protected function activateFreePlan($user, $plan)
    {
        $subscription = $user->subscription;
        
        if ($subscription) {
            $subscription->update([
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'amount' => 0,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        } else {
            UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'amount' => 0,
                'currency' => 'INR',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }
        
        return redirect()->route('subscriptions.index')
            ->with('success', 'Free plan activated successfully!');
    }
    
    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->subscription;
        
        if (!$subscription || !$subscription->isActive()) {
            return back()->with('error', 'No active subscription to cancel');
        }
        
        $subscription->cancel();
        
        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription canceled. You can continue using until ' . $subscription->current_period_end->format('M d, Y'));
    }
    
    /**
     * Resume canceled subscription
     */
    public function resume()
    {
        $user = Auth::user();
        $subscription = $user->subscription;
        
        if (!$subscription || !$subscription->isCanceled()) {
            return back()->with('error', 'Cannot resume subscription');
        }
        
        $subscription->update([
            'status' => 'active',
            'canceled_at' => null,
        ]);
        
        return redirect()->route('subscriptions.index')
            ->with('success', 'Subscription resumed successfully!');
    }
    
    /**
     * Helper: Calculate percentage
     */
    protected function calculatePercentage($used, $limit): int
    {
        if ($limit === null || $limit === 0) return 0;
        return min(100, round(($used / $limit) * 100));
    }
}
