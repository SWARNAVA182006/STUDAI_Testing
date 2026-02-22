{{--
    StudAI Path — For Employers Page
    Employer-focused features showcase
--}}
@extends('layouts.app')

@section('title', 'For Employers | StudAI Path — AI-Powered Hiring')

@section('meta')
<meta name="description" content="Hire smarter with AI-powered candidate matching, applicant tracking, automated screening, and talent insights. Transform your recruiting process.">
<meta property="og:title" content="For Employers — StudAI Path">
<meta property="og:description" content="AI-powered hiring tools for modern recruiters.">
<link rel="canonical" href="{{ route('employers') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
        <div class="absolute top-20 right-0 h-[28rem] w-[28rem] rounded-full bg-purple-500/20 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-6 py-24 lg:py-32 text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-semibold uppercase tracking-widest mb-6">
            For Employers
        </span>
        <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl max-w-4xl mx-auto">
            Hire Smarter. Hire Faster. Hire Better.
        </h1>
        <p class="mt-6 text-lg text-slate-200 max-w-2xl mx-auto">
            AI-powered hiring platform that matches you with pre-qualified candidates, automates screening, and reduces time-to-hire by 60%.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Hiring Free
            </a>
            <a href="#features" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                See How It Works
            </a>
        </div>
    </div>
</section>

{{-- Stats Section --}}
<section class="bg-white py-16 border-b border-gray-100">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-4xl font-bold text-google-blue-600">60%</div>
                <div class="mt-2 text-sm text-gray-600">Faster Time-to-Hire</div>
            </div>
            <div>
                <div class="text-4xl font-bold text-google-green-500">3x</div>
                <div class="mt-2 text-sm text-gray-600">Quality Candidates</div>
            </div>
            <div>
                <div class="text-4xl font-bold text-purple-600">80%</div>
                <div class="mt-2 text-sm text-gray-600">Screening Automated</div>
            </div>
            <div>
                <div class="text-4xl font-bold text-orange-500">50%</div>
                <div class="mt-2 text-sm text-gray-600">Cost Reduction</div>
            </div>
        </div>
    </div>
</section>

{{-- Employer Features --}}
<section id="features" class="py-20 bg-surface-50">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">Employer Tools</span>
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">Everything You Need to Hire Top Talent</h2>
            <p class="mt-4 text-lg text-ink-secondary max-w-2xl mx-auto">
                From job posting to offer letters — our AI handles the heavy lifting so your team can focus on what matters.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Feature 1: AI Matching --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-google-blue-500 to-cyan-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">AI Candidate Matching</h3>
                <p class="text-ink-secondary mb-4">
                    Our AI analyzes skills, experience, and cultural fit to surface the best candidates from our talent pool.
                </p>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Smart skill matching
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Culture fit scoring
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Salary alignment check
                    </li>
                </ul>
            </div>

            {{-- Feature 2: ATS --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">Applicant Tracking System</h3>
                <p class="text-ink-secondary mb-4">
                    Track every candidate through your hiring pipeline with our intuitive, drag-and-drop ATS.
                </p>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Kanban pipeline view
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Automated status updates
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Team collaboration tools
                    </li>
                </ul>
            </div>

            {{-- Feature 3: Video Interviews --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-google-green-500 to-teal-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">AI Video Interviews</h3>
                <p class="text-ink-secondary mb-4">
                    Conduct asynchronous video interviews with AI-powered analysis of responses and body language.
                </p>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Async one-way interviews
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        AI response analysis
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Structured scoring
                    </li>
                </ul>
            </div>

            {{-- Feature 4: Job Posting --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">Smart Job Posting</h3>
                <p class="text-ink-secondary mb-4">
                    AI helps you craft compelling job descriptions and distributes them across multiple channels.
                </p>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        AI-optimized descriptions
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Multi-channel distribution
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Performance analytics
                    </li>
                </ul>
            </div>

            {{-- Feature 5: Offer Letters --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-yellow-500 to-amber-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">Digital Offer Letters</h3>
                <p class="text-ink-secondary mb-4">
                    Generate, send, and track offer letters with e-signatures and negotiation workflows.
                </p>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Templated offers
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        E-signature integration
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Negotiation tracking
                    </li>
                </ul>
            </div>

            {{-- Feature 6: Analytics --}}
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">Hiring Analytics</h3>
                <p class="text-ink-secondary mb-4">
                    Data-driven insights into your hiring funnel, time-to-hire, source effectiveness, and more.
                </p>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Funnel conversion rates
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Source performance
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Time-to-hire metrics
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="bg-gradient-to-r from-google-blue-600 to-purple-600 py-20">
    <div class="mx-auto max-w-4xl px-6 text-center">
        <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to Transform Your Hiring?</h2>
        <p class="mt-4 text-lg text-white/80">
            Join hundreds of companies using StudAI Path to hire smarter, faster, and better.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Free Trial
            </a>
            <a href="{{ route('contact') }}" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                Contact Sales
            </a>
        </div>
    </div>
</section>
@endsection
