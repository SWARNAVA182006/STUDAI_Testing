@extends('layouts.app')

@section('title', 'Refund Policy - StudAI Career | Money-Back Guarantee & Cancellation Terms')

@section('meta')
<meta name="description" content="StudAI Career refund policy covering subscription cancellations, money-back guarantee, and payment terms for Indian users using Razorpay and PayU.">
<meta name="keywords" content="refund policy, money back guarantee, cancellation, subscription refund, payment terms">
<meta property="og:title" content="Refund Policy - StudAI Career">
<meta property="og:description" content="Clear and transparent refund policy with money-back guarantee for StudAI Career services.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('refund-policy') }}">
<link rel="canonical" href="{{ route('refund-policy') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative py-16 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20 mb-8">
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium text-white">Last Updated: December 1, 2024</span>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight">
            Refund Policy
        </h1>
        <p class="text-xl text-gray-200 leading-relaxed">
            We stand behind our services with a transparent and fair refund policy designed for your peace of mind.
        </p>
    </div>
</section>

<!-- Quick Summary -->
<section class="py-8 bg-slate-950 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-green-500/10 border border-green-500/20 rounded-2xl p-6 text-center">
                <div class="w-12 h-12 bg-green-500/20 rounded-xl mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">7-Day Guarantee</h3>
                <p class="text-sm text-gray-300">Full refund for new subscriptions within 7 days</p>
            </div>
            
            <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-6 text-center">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Secure Processing</h3>
                <p class="text-sm text-gray-300">Refunds processed via Razorpay/PayU within 5-7 business days</p>
            </div>
            
            <div class="bg-purple-500/10 border border-purple-500/20 rounded-2xl p-6 text-center">
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl mx-auto mb-3 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Pro-rated Refunds</h3>
                <p class="text-sm text-gray-300">Fair refunds for unused portions of annual plans</p>
            </div>
        </div>
    </div>
</section>

<!-- Refund Policy Content -->
<section class="py-16 bg-slate-950">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-invert prose-green max-w-none">
            
            <!-- Introduction -->
            <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 mb-12">
                <p class="text-lg text-gray-200 leading-relaxed mb-6">
                    At StudAI Career, we're confident in the value of our services. This Refund Policy outlines the terms and conditions for refunds and cancellations for our subscription plans and services.
                </p>
                <p class="text-gray-300 leading-relaxed">
                    This policy applies to all payments made through our platform using Razorpay, PayU, or other approved payment gateways. For questions about refunds, contact our support team at refunds@studai.careers.
                </p>
            </div>

            <!-- Section 1: Money-Back Guarantee -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">1. Money-Back Guarantee</h2>
                
                <div class="bg-green-500/10 border border-green-500/20 rounded-2xl p-6 mb-6">
                    <h3 class="text-xl font-semibold text-white mb-3">7-Day Satisfaction Guarantee</h3>
                    <p class="text-gray-300 mb-4">
                        We offer a 7-day money-back guarantee for all new subscription purchases. If you're not satisfied with our services, you can request a full refund within 7 days of your initial purchase.
                    </p>
                    <p class="text-gray-300 text-sm">
                        This guarantee applies to first-time subscribers only and excludes usage-based charges (AI credits, premium assessments) that have been consumed.
                    </p>
                </div>

                <h3 class="text-2xl font-semibold text-white mb-4">Eligibility Criteria</h3>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Request must be made within 7 days of the original purchase date</li>
                    <li>Account must be in good standing with no violations of our Terms of Service</li>
                    <li>Applies to subscription plans only (Basic, Premium, Enterprise)</li>
                    <li>One-time services and add-ons may have different refund terms</li>
                    <li>Refund requests must include a reason for dissatisfaction</li>
                </ul>
            </div>

            <!-- Section 2: Subscription Cancellations -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">2. Subscription Cancellations</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">2.1 Monthly Subscriptions</h3>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6 mb-6">
                    <ul class="list-disc list-inside text-gray-300 space-y-2">
                        <li><strong>Cancellation Timing:</strong> Cancel anytime before your next billing date</li>
                        <li><strong>Access Period:</strong> Continue using services until the end of your current billing period</li>
                        <li><strong>No Partial Refunds:</strong> Monthly subscriptions are not eligible for partial refunds</li>
                        <li><strong>Data Retention:</strong> Your data remains accessible for 30 days after cancellation</li>
                    </ul>
                </div>

                <h3 class="text-2xl font-semibold text-white mb-4">2.2 Annual Subscriptions</h3>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6 mb-6">
                    <ul class="list-disc list-inside text-gray-300 space-y-2">
                        <li><strong>30-Day Window:</strong> Pro-rated refunds available for cancellations within 30 days</li>
                        <li><strong>After 30 Days:</strong> No refunds, but service continues until expiration</li>
                        <li><strong>Pro-rated Calculation:</strong> Refund = (Unused months / 12) × Annual payment</li>
                        <li><strong>Processing Fee:</strong> Administrative fee of ₹500 may apply for annual refunds</li>
                    </ul>
                </div>

                <h3 class="text-2xl font-semibold text-white mb-4">How to Cancel</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h4 class="font-semibold text-white mb-3">Self-Service Cancellation</h4>
                        <ol class="list-decimal list-inside text-gray-300 text-sm space-y-1">
                            <li>Log into your StudAI Career account</li>
                            <li>Navigate to Account Settings → Billing</li>
                            <li>Click "Cancel Subscription"</li>
                            <li>Follow the confirmation prompts</li>
                            <li>Receive cancellation confirmation email</li>
                        </ol>
                    </div>
                    
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h4 class="font-semibold text-white mb-3">Support-Assisted Cancellation</h4>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-1">
                            <li>Email: support@studai.careers</li>
                            <li>Phone: +91-80-4567-8900</li>
                            <li>Live Chat: Available 9 AM - 6 PM IST</li>
                            <li>Include account email and reason</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 3: Refund Process -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">3. Refund Processing</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">3.1 Request Submission</h3>
                <p class="text-gray-300 mb-4">To request a refund, please provide:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Account email address used for purchase</li>
                    <li>Transaction ID or order number</li>
                    <li>Date of purchase</li>
                    <li>Reason for refund request</li>
                    <li>Any supporting documentation</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">3.2 Processing Timeline</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-slate-900/50 rounded-2xl border border-slate-800">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Step</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Timeline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-white">Request Review</td>
                                <td class="px-6 py-4 text-sm text-gray-300">1-2 business days</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Initial review and eligibility verification</td>
                            </tr>
                            <tr class="bg-slate-800/30">
                                <td class="px-6 py-4 text-sm font-medium text-white">Approval/Denial</td>
                                <td class="px-6 py-4 text-sm text-gray-300">2-3 business days</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Decision notification via email</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-white">Payment Processing</td>
                                <td class="px-6 py-4 text-sm text-gray-300">5-7 business days</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Refund processed to original payment method</td>
                            </tr>
                            <tr class="bg-slate-800/30">
                                <td class="px-6 py-4 text-sm font-medium text-white">Bank Processing</td>
                                <td class="px-6 py-4 text-sm text-gray-300">3-10 business days</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Depends on bank and payment method</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 4: Payment Gateway Specific Terms -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">4. Payment Gateway Specific Terms</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Razorpay Refunds</h3>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                            <li><strong>Credit/Debit Cards:</strong> 5-7 business days</li>
                            <li><strong>Net Banking:</strong> 5-7 business days</li>
                            <li><strong>UPI:</strong> 1-3 business days</li>
                            <li><strong>Wallets:</strong> Instant to 1 business day</li>
                            <li><strong>EMI:</strong> As per bank's EMI cancellation policy</li>
                        </ul>
                        <p class="text-gray-400 text-xs mt-3">
                            Refund timelines depend on the issuing bank and may vary during festivals or holidays.
                        </p>
                    </div>
                    
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">PayU Refunds</h3>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                            <li><strong>Credit Cards:</strong> 7-10 business days</li>
                            <li><strong>Debit Cards:</strong> 7-10 business days</li>
                            <li><strong>Net Banking:</strong> 7-10 business days</li>
                            <li><strong>Cash Cards:</strong> 1-3 business days</li>
                            <li><strong>LazyPay/Simpl:</strong> As per partner terms</li>
                        </ul>
                        <p class="text-gray-400 text-xs mt-3">
                            International cards may take up to 45 days for refund processing.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 5: Non-Refundable Items -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">5. Non-Refundable Items</h2>
                <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">The following items are not eligible for refunds:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                        <ul class="list-disc list-inside space-y-2">
                            <li><strong>Consumed AI Credits:</strong> Credits used for resume reviews, job matching, or assessments</li>
                            <li><strong>Completed Services:</strong> One-time resume writing, interview coaching sessions</li>
                            <li><strong>Premium Assessments:</strong> Skill tests and personality assessments already taken</li>
                            <li><strong>Third-party Fees:</strong> Payment gateway charges, taxes, and processing fees</li>
                        </ul>
                        <ul class="list-disc list-inside space-y-2">
                            <li><strong>Expired Subscriptions:</strong> Subscriptions that have already ended</li>
                            <li><strong>Violated Accounts:</strong> Accounts terminated for Terms of Service violations</li>
                            <li><strong>Promotional Credits:</strong> Bonus credits or promotional additions</li>
                            <li><strong>Data Export Fees:</strong> Charges for exporting large datasets</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 6: Special Circumstances -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">6. Special Circumstances</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">6.1 Technical Issues</h3>
                <p class="text-gray-300 mb-4">
                    If you experience technical issues that prevent you from using our services, we may provide:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Service credit for downtime exceeding 24 hours</li>
                    <li>Extension of subscription period for significant outages</li>
                    <li>Partial refunds for documented service failures</li>
                    <li>Alternative service arrangements when possible</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">6.2 Medical or Emergency Situations</h3>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-6 mb-6">
                    <p class="text-gray-300 mb-4">
                        We understand that unexpected situations arise. In cases of medical emergencies, military deployment, or other extraordinary circumstances, we may offer:
                    </p>
                    <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                        <li>Extended refund periods on a case-by-case basis</li>
                        <li>Account freezing to preserve subscription time</li>
                        <li>Flexible payment arrangements</li>
                        <li>Compassionate refund consideration</li>
                    </ul>
                    <p class="text-gray-400 text-xs mt-3">
                        Please contact our support team with appropriate documentation for consideration.
                    </p>
                </div>

                <h3 class="text-2xl font-semibold text-white mb-4">6.3 Duplicate Payments</h3>
                <p class="text-gray-300 mb-4">
                    If you are accidentally charged multiple times for the same service:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Contact us immediately with transaction details</li>
                    <li>Duplicate charges will be refunded within 3-5 business days</li>
                    <li>No additional documentation required for clear duplicates</li>
                    <li>We'll investigate and resolve within 24 hours</li>
                </ul>
            </div>

            <!-- Section 7: Dispute Resolution -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">7. Dispute Resolution</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">7.1 Refund Disputes</h3>
                <p class="text-gray-300 mb-4">If your refund request is denied and you disagree with our decision:</p>
                <ol class="list-decimal list-inside text-gray-300 mb-6 space-y-2">
                    <li>Request a detailed explanation of the denial reason</li>
                    <li>Provide additional supporting information if available</li>
                    <li>Escalate to our Customer Success Manager</li>
                    <li>Request a secondary review if unsatisfied</li>
                    <li>File a complaint with payment gateway if still unresolved</li>
                </ol>

                <h3 class="text-2xl font-semibold text-white mb-4">7.2 Chargeback Policy</h3>
                <div class="bg-orange-500/10 border border-orange-500/20 rounded-2xl p-6">
                    <p class="text-gray-300 mb-4">
                        <strong>Please contact us before initiating a chargeback.</strong> Chargebacks can result in:
                    </p>
                    <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                        <li>Immediate suspension of account access</li>
                        <li>Additional processing fees passed to your account</li>
                        <li>Difficulty resolving the issue amicably</li>
                        <li>Potential impact on future service eligibility</li>
                    </ul>
                    <p class="text-gray-300 text-sm mt-4">
                        We're committed to resolving payment issues fairly and quickly through direct communication.
                    </p>
                </div>
            </div>

            <!-- Section 8: Contact Information -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">8. Contact Information</h2>
                <div class="bg-gradient-to-br from-green-500/10 to-blue-500/10 rounded-3xl border border-green-500/20 p-8">
                    <p class="text-gray-300 mb-6">
                        For refund requests, billing questions, or policy clarifications:
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-3">Refunds Department</h3>
                            <div class="space-y-2 text-gray-300">
                                <p>Email: refunds@studai.careers</p>
                                <p>Phone: +91-80-4567-8900 (Ext. 2)</p>
                                <p>Hours: 9:00 AM - 6:00 PM IST, Mon-Fri</p>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-3">Billing Support</h3>
                            <div class="space-y-2 text-gray-300">
                                <p>Email: billing@studai.careers</p>
                                <p>Live Chat: Available on our platform</p>
                                <p>Response Time: Within 24 hours</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-green-500/20">
                        <p class="text-sm text-gray-400">
                            <strong>Business Details:</strong> StudAI Career Private Limited | CIN: L72900KA2024PTC180234<br>
                            <strong>Address:</strong> WeWork Prestige Atlanta, 80 Feet Road, Koramangala 4th Block, Bengaluru 560034<br>
                            <strong>GST:</strong> 29AABCS1234C1Z5
                        </p>
                    </div>
                </div>
            </div>

            <!-- Policy Updates -->
            <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                <h3 class="text-lg font-semibold text-white mb-3">Policy Updates</h3>
                <p class="text-gray-300 text-sm">
                    This Refund Policy may be updated periodically to reflect changes in our services, payment methods, or legal requirements. We'll notify customers of material changes via email and platform notifications at least 30 days before they take effect.
                </p>
            </div>

        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Refund Policy - StudAI Career',
    'description' => 'StudAI Career refund policy covering subscription cancellations, money-back guarantee, and payment terms',
    'url' => route('refund-policy'),
    'lastReviewed' => '2024-12-01',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'StudAI Career',
        'url' => url('/'),
        'sameAs' => [
            'https://www.linkedin.com/company/studai-career'
        ]
    ],
    'mainEntity' => [
        '@type' => 'Service',
        'name' => 'StudAI Career Platform',
        'provider' => [
            '@type' => 'Organization',
            'name' => 'StudAI Career Private Limited'
        ],
        'termsOfService' => route('terms'),
        'hasPOS' => [
            '@type' => 'PaymentMethod',
            'name' => 'Razorpay'
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@push('styles')
<style>
.prose h2 {
    scroll-margin-top: 2rem;
}
.prose h3 {
    scroll-margin-top: 2rem;
}
html {
    scroll-behavior: smooth;
}
</style>
@endpush