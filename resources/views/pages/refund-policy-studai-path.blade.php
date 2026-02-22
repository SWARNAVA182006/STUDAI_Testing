{{--
    StudAI Path — Refund Policy
    Money-Back Guarantee & Cancellation Terms
--}}
@extends('layouts.app')

@section('title', 'Refund Policy — StudAI Path | Fair & Transparent')

@section('meta')
<meta name="description" content="StudAI Path refund policy: 7-day money-back guarantee, pro-rated refunds for annual plans, and transparent cancellation process.">
<meta property="og:title" content="Refund Policy — StudAI Path">
<meta property="og:description" content="We believe in fair refunds. See our transparent policy.">
<link rel="canonical" href="{{ route('refund-policy') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-4xl px-6 py-20 text-center">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-medium mb-6">
            <svg class="w-4 h-4 text-google-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Last Updated: January 15, 2025
        </div>
        <h1 class="text-4xl font-bold sm:text-5xl">Refund Policy</h1>
        <p class="mt-4 text-lg text-slate-200 max-w-2xl mx-auto">
            We stand behind our product. If you're not happy, we'll make it right.
        </p>
    </div>
</section>

{{-- Quick Summary --}}
<section class="py-8 bg-canvas-subtle">
    <div class="mx-auto max-w-5xl px-6">
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl border border-surface-200 p-6 text-center">
                <div class="w-14 h-14 bg-google-green-100 rounded-xl mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-7 h-7 text-google-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-ink-primary mb-2">7-Day Guarantee</h3>
                <p class="text-sm text-ink-secondary">Full refund if you're not satisfied within 7 days of first subscription</p>
            </div>
            
            <div class="bg-white rounded-2xl border border-surface-200 p-6 text-center">
                <div class="w-14 h-14 bg-google-blue-100 rounded-xl mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-7 h-7 text-google-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-ink-primary mb-2">5-7 Day Processing</h3>
                <p class="text-sm text-ink-secondary">Refunds processed via Razorpay/Stripe to original payment method</p>
            </div>
            
            <div class="bg-white rounded-2xl border border-surface-200 p-6 text-center">
                <div class="w-14 h-14 bg-purple-100 rounded-xl mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-7 h-7 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-ink-primary mb-2">Pro-Rated Refunds</h3>
                <p class="text-sm text-ink-secondary">Fair refunds for unused time on annual plans</p>
            </div>
        </div>
    </div>
</section>

{{-- Policy Content --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-4xl px-6">
        <div class="prose prose-lg max-w-none">

            {{-- Introduction --}}
            <div class="bg-surface-50 rounded-2xl border border-surface-200 p-6 mb-12 not-prose">
                <p class="text-ink-secondary">
                    At StudAI Path, we're confident in the value we provide. This Refund Policy outlines when and how you can request refunds for our subscription services.
                </p>
            </div>

            {{-- Section 1: Money-Back Guarantee --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">1. 7-Day Money-Back Guarantee</h2>
                <div class="bg-google-green-50 border border-google-green-200 rounded-xl p-6 mt-4 not-prose">
                    <h4 class="font-semibold text-google-green-800 mb-2">✅ New Subscribers</h4>
                    <p class="text-ink-secondary text-sm">
                        If this is your first paid subscription to StudAI Path and you're not satisfied, request a full refund within <strong>7 days</strong> of your initial payment. No questions asked.
                    </p>
                </div>
                <p class="text-ink-secondary mt-4">
                    <strong>Eligibility:</strong> First-time subscribers only. One guarantee per person/account.
                </p>
            </div>

            {{-- Section 2: Monthly Subscriptions --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">2. Monthly Subscriptions</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>Cancellation:</strong> Cancel anytime from your account settings</li>
                    <li><strong>Access:</strong> You keep access until the end of your billing period</li>
                    <li><strong>Refunds:</strong> No partial refunds for unused days in the current month (except under 7-day guarantee)</li>
                    <li><strong>Auto-renewal:</strong> Cancel before renewal date to avoid next charge</li>
                </ul>
            </div>

            {{-- Section 3: Annual Subscriptions --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">3. Annual Subscriptions</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>7-Day Guarantee:</strong> Full refund within 7 days of first annual payment</li>
                    <li><strong>After 7 Days:</strong> Pro-rated refund for remaining full months (minus a 10% processing fee)</li>
                    <li><strong>Example:</strong> Cancel after 4 months of a 12-month plan = refund for 8 months minus 10%</li>
                    <li><strong>No Refund After 6 Months:</strong> Beyond 50% of the term, refunds are not available</li>
                </ul>
            </div>

            {{-- Section 4: How to Request a Refund --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">4. How to Request a Refund</h2>
                <div class="bg-surface-50 rounded-xl border border-surface-200 p-6 mt-4 not-prose">
                    <ol class="space-y-4 text-ink-secondary">
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-google-blue-100 text-google-blue-700 flex items-center justify-center font-semibold text-sm flex-shrink-0">1</span>
                            <span>Email <a href="mailto:billing@studaipath.com" class="text-google-blue-600 hover:underline">billing@studaipath.com</a> with subject: "Refund Request"</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-google-blue-100 text-google-blue-700 flex items-center justify-center font-semibold text-sm flex-shrink-0">2</span>
                            <span>Include your account email and reason for refund</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-google-blue-100 text-google-blue-700 flex items-center justify-center font-semibold text-sm flex-shrink-0">3</span>
                            <span>We'll confirm eligibility within 2 business days</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 rounded-full bg-google-blue-100 text-google-blue-700 flex items-center justify-center font-semibold text-sm flex-shrink-0">4</span>
                            <span>Approved refunds are processed within 5-7 business days</span>
                        </li>
                    </ol>
                </div>
            </div>

            {{-- Section 5: Non-Refundable Items --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">5. Non-Refundable Items</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>🚫 One-time purchases (e.g., resume template packs)</li>
                    <li>🚫 Consumed AI credits (beyond the subscription allocation)</li>
                    <li>🚫 Enterprise contracts (custom terms apply)</li>
                    <li>🚫 Accounts terminated for Terms of Service violations</li>
                </ul>
            </div>

            {{-- Section 6: Chargebacks --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">6. Chargebacks</h2>
                <p class="text-ink-secondary mt-4">
                    We encourage you to contact us before disputing a charge with your bank. If you file a chargeback without first contacting us:
                </p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>Your account may be suspended pending resolution</li>
                    <li>We will provide transaction evidence to the bank</li>
                    <li>Future refund requests may be affected</li>
                </ul>
            </div>

            {{-- Section 7: Payment Issues --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">7. Payment Issues</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>Failed Payments:</strong> We retry 3 times over 10 days before suspending</li>
                    <li><strong>Duplicate Charges:</strong> Contact us immediately; we'll refund the duplicate</li>
                    <li><strong>Currency:</strong> All prices in INR; bank conversion fees are not refundable</li>
                </ul>
            </div>

            {{-- Section 8: Contact --}}
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">8. Contact Billing Support</h2>
                <div class="bg-surface-50 rounded-xl border border-surface-200 p-6 mt-4 not-prose">
                    <ul class="space-y-2 text-ink-secondary">
                        <li><strong>Email:</strong> <a href="mailto:billing@studaipath.com" class="text-google-blue-600 hover:underline">billing@studaipath.com</a></li>
                        <li><strong>Response Time:</strong> Within 24 hours on business days</li>
                        <li><strong>Phone:</strong> +91-80-4567-8900 (Mon-Fri, 10 AM - 6 PM IST)</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-canvas-subtle">
    <div class="mx-auto max-w-4xl px-6 text-center">
        <h2 class="text-2xl font-bold text-ink-primary mb-4">Still have questions?</h2>
        <p class="text-ink-secondary mb-8">Our support team is here to help with any billing concerns.</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('contact') }}" class="studai-btn studai-btn-primary">
                Contact Support
            </a>
            <a href="{{ route('pricing') }}" class="studai-btn studai-btn-secondary">
                View Pricing
            </a>
        </div>
    </div>
</section>
@endsection
