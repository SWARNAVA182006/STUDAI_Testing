{{--
    StudAI Path — Terms of Service
    User Agreement & Service Terms
--}}
@extends('layouts.app')

@section('title', 'Terms of Service — StudAI Path')

@section('meta')
<meta name="description" content="Terms and Conditions for using StudAI Path services. Covers user obligations, subscription terms, AI usage, and liability provisions.">
<meta property="og:title" content="Terms of Service — StudAI Path">
<meta property="og:description" content="Read our terms before using StudAI Path.">
<link rel="canonical" href="{{ route('terms') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-4xl px-6 py-20 text-center">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-medium mb-6">
            <svg class="w-4 h-4 text-google-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Last Updated: January 15, 2025
        </div>
        <h1 class="text-4xl font-bold sm:text-5xl">Terms of Service</h1>
        <p class="mt-4 text-lg text-slate-200 max-w-2xl mx-auto">
            Please read these terms carefully before using StudAI Path.
        </p>
    </div>
</section>

{{-- Table of Contents --}}
<section class="py-8 bg-canvas-subtle border-b border-surface-200">
    <div class="mx-auto max-w-4xl px-6">
        <h2 class="text-lg font-semibold text-ink-primary mb-4">Contents</h2>
        <div class="grid md:grid-cols-2 gap-2 text-sm">
            @foreach ([
                ['title' => '1. Acceptance of Terms', 'anchor' => 'acceptance'],
                ['title' => '2. Description of Services', 'anchor' => 'services'],
                ['title' => '3. Account Registration', 'anchor' => 'accounts'],
                ['title' => '4. Subscriptions & Payments', 'anchor' => 'payments'],
                ['title' => '5. User Conduct', 'anchor' => 'conduct'],
                ['title' => '6. AI & Automated Features', 'anchor' => 'ai-features'],
                ['title' => '7. Intellectual Property', 'anchor' => 'ip'],
                ['title' => '8. Disclaimers', 'anchor' => 'disclaimers'],
                ['title' => '9. Limitation of Liability', 'anchor' => 'liability'],
                ['title' => '10. Termination', 'anchor' => 'termination'],
                ['title' => '11. Governing Law', 'anchor' => 'law'],
                ['title' => '12. Contact', 'anchor' => 'contact'],
            ] as $item)
                <a href="#{{ $item['anchor'] }}" class="text-google-blue-600 hover:underline">{{ $item['title'] }}</a>
            @endforeach
        </div>
    </div>
</section>

{{-- Terms Content --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-4xl px-6">
        <div class="prose prose-lg max-w-none">

            {{-- Introduction --}}
            <div class="bg-surface-50 rounded-2xl border border-surface-200 p-6 mb-12 not-prose">
                <p class="text-ink-secondary">
                    These Terms of Service ("Terms") govern your use of the StudAI Path website, mobile apps, and services (the "Services") operated by StudAI Path Technologies Private Limited ("we", "us", "Company").
                </p>
                <p class="text-ink-secondary mt-4">
                    By accessing or using our Services, you agree to be bound by these Terms. If you disagree, do not use our Services.
                </p>
            </div>

            {{-- Section 1 --}}
            <div id="acceptance" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">1. Acceptance of Terms</h2>
                <p class="text-ink-secondary mt-4">
                    By creating an account, subscribing to a plan, or using any part of our Services, you confirm that you:
                </p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>Are at least 18 years old (or age of majority in your jurisdiction)</li>
                    <li>Have the legal capacity to enter into binding contracts</li>
                    <li>Are not barred from using the Services under applicable law</li>
                    <li>Will use the Services only for lawful purposes</li>
                </ul>
            </div>

            {{-- Section 2 --}}
            <div id="services" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">2. Description of Services</h2>
                <p class="text-ink-secondary mt-4">StudAI Path provides:</p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>Autonomous Career Agent:</strong> AI-powered job search and application automation</li>
                    <li><strong>Resume Studio:</strong> AI resume creation and optimization tools</li>
                    <li><strong>Interview AI:</strong> Mock interviews and coaching</li>
                    <li><strong>Market Intelligence:</strong> Salary data and career insights</li>
                    <li><strong>S.C.O.U.T. (Employers):</strong> AI-powered candidate screening</li>
                </ul>
                <p class="text-ink-secondary mt-4">
                    We reserve the right to modify, suspend, or discontinue any feature with reasonable notice.
                </p>
            </div>

            {{-- Section 3 --}}
            <div id="accounts" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">3. Account Registration</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>You must provide accurate, complete information during registration</li>
                    <li>You are responsible for maintaining account security</li>
                    <li>You must notify us immediately of unauthorized access</li>
                    <li>One account per person; no shared or transfer of accounts</li>
                </ul>
            </div>

            {{-- Section 4 --}}
            <div id="payments" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">4. Subscriptions & Payments</h2>
                
                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">4.1 Pricing</h3>
                <p class="text-ink-secondary">
                    Subscription fees are displayed on our pricing page. Prices are in INR and include applicable taxes unless stated otherwise.
                </p>
                
                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">4.2 Billing</h3>
                <ul class="space-y-2 text-ink-secondary">
                    <li>Monthly plans are billed monthly; annual plans are billed upfront</li>
                    <li>Subscriptions auto-renew unless cancelled before renewal date</li>
                    <li>Payments processed securely via Razorpay or Stripe</li>
                </ul>
                
                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">4.3 Refunds</h3>
                <p class="text-ink-secondary">
                    See our <a href="{{ route('refund-policy') }}" class="text-google-blue-600 hover:underline">Refund Policy</a> for details on cancellations and refunds.
                </p>
            </div>

            {{-- Section 5 --}}
            <div id="conduct" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">5. User Conduct</h2>
                <p class="text-ink-secondary mt-4">You agree NOT to:</p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>🚫 Provide false information in your profile or applications</li>
                    <li>🚫 Use the Services to spam, harass, or defraud others</li>
                    <li>🚫 Attempt to circumvent usage limits or security measures</li>
                    <li>🚫 Scrape, copy, or redistribute our content or data</li>
                    <li>🚫 Use automated tools (except our provided features) to access Services</li>
                    <li>🚫 Violate any applicable laws or third-party rights</li>
                </ul>
                <p class="text-ink-secondary mt-4">Violations may result in immediate termination without refund.</p>
            </div>

            {{-- Section 6 --}}
            <div id="ai-features" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">6. AI & Automated Features</h2>
                <div class="bg-google-blue-50 border border-google-blue-200 rounded-xl p-6 mt-4 not-prose">
                    <h4 class="font-semibold text-ink-primary mb-2">Understanding AI Limitations</h4>
                    <ul class="text-sm text-ink-secondary space-y-2">
                        <li>🔹 AI-generated content (cover letters, answers) is <strong>suggestions only</strong> — you are responsible for reviewing and submitting</li>
                        <li>🔹 Job matches are based on algorithms and may not be perfect</li>
                        <li>🔹 Interview coaching is for practice — real interview outcomes may vary</li>
                        <li>🔹 Salary insights are estimates based on aggregated data</li>
                        <li>🔹 We do not guarantee job placement or specific outcomes</li>
                    </ul>
                </div>
            </div>

            {{-- Section 7 --}}
            <div id="ip" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">7. Intellectual Property</h2>
                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">7.1 Our Content</h3>
                <p class="text-ink-secondary">
                    The Services, including design, logos, AI models, and software, are owned by StudAI Path and protected by IP laws. You may not copy, modify, or distribute without permission.
                </p>
                
                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">7.2 Your Content</h3>
                <p class="text-ink-secondary">
                    You retain ownership of content you upload (resumes, profiles). By uploading, you grant us a license to use it to provide Services. AI-generated outputs based on your content are licensed to you for personal use.
                </p>
            </div>

            {{-- Section 8 --}}
            <div id="disclaimers" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">8. Disclaimers</h2>
                <div class="bg-google-yellow-50 border border-google-yellow-200 rounded-xl p-6 mt-4 not-prose">
                    <p class="text-ink-secondary text-sm">
                        THE SERVICES ARE PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. WE DO NOT GUARANTEE THAT YOU WILL FIND A JOB, RECEIVE INTERVIEWS, OR ACHIEVE SPECIFIC CAREER OUTCOMES. AI FEATURES MAY CONTAIN ERRORS. USE PROFESSIONAL JUDGMENT BEFORE ACTING ON ANY SUGGESTIONS.
                    </p>
                </div>
            </div>

            {{-- Section 9 --}}
            <div id="liability" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">9. Limitation of Liability</h2>
                <p class="text-ink-secondary mt-4">
                    To the maximum extent permitted by law:
                </p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>We are not liable for indirect, incidental, or consequential damages</li>
                    <li>Our total liability is limited to the amount you paid us in the past 12 months</li>
                    <li>We are not responsible for third-party actions (employers, job boards)</li>
                </ul>
            </div>

            {{-- Section 10 --}}
            <div id="termination" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">10. Termination</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>You may cancel your account anytime via settings</li>
                    <li>We may terminate accounts for Terms violations with notice</li>
                    <li>Upon termination, your data will be deleted per our Privacy Policy</li>
                </ul>
            </div>

            {{-- Section 11 --}}
            <div id="law" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">11. Governing Law</h2>
                <p class="text-ink-secondary mt-4">
                    These Terms are governed by the laws of India. Disputes shall be subject to the exclusive jurisdiction of the courts in Bengaluru, Karnataka.
                </p>
                <p class="text-ink-secondary mt-4">
                    For disputes under ₹50,000, we agree to attempt resolution via mediation before litigation.
                </p>
            </div>

            {{-- Section 12 --}}
            <div id="contact" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">12. Contact</h2>
                <div class="bg-surface-50 rounded-xl border border-surface-200 p-6 mt-4 not-prose">
                    <p class="text-ink-secondary mb-4">Questions about these Terms?</p>
                    <ul class="space-y-2 text-ink-secondary">
                        <li><strong>Email:</strong> <a href="mailto:legal@studaipath.com" class="text-google-blue-600 hover:underline">legal@studaipath.com</a></li>
                        <li><strong>Address:</strong> StudAI Path Technologies Pvt. Ltd., WeWork Prestige Atlanta, Koramangala, Bengaluru 560034, India</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
