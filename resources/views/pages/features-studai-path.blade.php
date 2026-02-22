{{--
    StudAI Path — Features Page
    Complete product modules showcase
--}}
@extends('layouts.app')

@section('title', 'Features | StudAI Path — The Autonomous Career OS')

@section('meta')
<meta name="description" content="Explore all features: Autonomous Agent, Resume AI, Interview Coach, Market Intelligence, Career Coach. Everything you need to automate your job search.">
<meta property="og:title" content="Features — StudAI Path">
<meta property="og:description" content="6 powerful AI modules. One platform. Zero manual work.">
<link rel="canonical" href="{{ route('features') }}">
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
            The Full Stack
        </span>
        <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl max-w-4xl mx-auto">
            6 AI Modules. One Career OS. Zero Manual Work.
        </h1>
        <p class="mt-6 text-lg text-slate-200 max-w-2xl mx-auto">
            Every tool you need to find, apply, prepare, negotiate, and land your dream job — all running on autopilot.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Free Today
            </a>
            <a href="#modules" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                Explore Modules
            </a>
        </div>
    </div>
</section>

{{-- Job Seeker Modules --}}
<section id="modules" class="py-20 bg-white">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">For Job Seekers</span>
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">Your Entire Job Search, Automated</h2>
            <p class="mt-4 text-lg text-ink-secondary max-w-2xl mx-auto">
                From finding jobs to negotiating offers — AI handles the grunt work so you can focus on what matters.
            </p>
        </div>

        {{-- Module 1: Autonomous Agent --}}
        <div class="mb-16 rounded-3xl border border-surface-200 bg-surface-50 overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="p-10 lg:p-12">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-google-blue-500 to-purple-500 text-white mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 mb-4">⚡ Flagship Feature</span>
                    <h3 class="text-2xl font-bold text-ink-primary mb-4">Autonomous Career Agent</h3>
                    <p class="text-lg text-ink-secondary mb-6">
                        Your 24/7 AI assistant that finds, evaluates, and applies to jobs matching your criteria — while you sleep.
                    </p>
                    <ul class="space-y-3 mb-8">
                        @foreach ([
                            'Scans 50+ job boards automatically',
                            'Smart matching based on skills + preferences',
                            'Auto-applies with tailored cover letters',
                            'Tracks applications & follows up',
                            'Daily digest of new opportunities',
                        ] as $feature)
                            <li class="flex items-start gap-3 text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('agent.dashboard') }}" class="inline-flex items-center gap-2 studai-btn studai-btn-primary">
                        Explore Agent
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
                <div class="bg-gradient-to-br from-google-blue-50 to-purple-50 p-10 lg:p-12 flex items-center justify-center">
                    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-google-green-100 text-google-green-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-ink-primary">Agent Active</div>
                                <div class="text-xs text-ink-muted">Last run: 2 mins ago</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-secondary">Jobs Found Today</span>
                                <span class="font-semibold text-ink-primary">127</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-secondary">Applied This Week</span>
                                <span class="font-semibold text-google-blue-600">34</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-ink-secondary">Interview Invites</span>
                                <span class="font-semibold text-google-green-600">5</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-surface-200">
                            <div class="text-xs text-ink-muted">Next scan in 45 minutes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Module 2: Resume Studio --}}
        <div class="mb-16 rounded-3xl border border-surface-200 bg-surface-50 overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="bg-gradient-to-br from-google-green-50 to-teal-50 p-10 lg:p-12 flex items-center justify-center order-2 lg:order-1">
                    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                        <div class="flex items-center justify-between mb-4">
                            <span class="font-semibold text-ink-primary">Resume Score</span>
                            <span class="text-2xl font-bold text-google-green-600">92/100</span>
                        </div>
                        <div class="w-full h-2 bg-surface-200 rounded-full overflow-hidden mb-4">
                            <div class="h-full bg-google-green-500 rounded-full" style="width: 92%"></div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-google-green-500"></span>
                                <span class="text-ink-secondary">ATS-optimized format</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-google-green-500"></span>
                                <span class="text-ink-secondary">Keywords matched: 18/20</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-google-yellow-500"></span>
                                <span class="text-ink-secondary">Add 2 more achievements</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-10 lg:p-12 order-1 lg:order-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-google-green-500 to-teal-500 text-white mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-ink-primary mb-4">Resume Studio</h3>
                    <p class="text-lg text-ink-secondary mb-6">
                        Build ATS-beating resumes that get past the bots and impress humans. AI-powered optimization for every job.
                    </p>
                    <ul class="space-y-3 mb-8">
                        @foreach ([
                            'One-click resume generation from LinkedIn',
                            'Real-time ATS scoring & suggestions',
                            'Job-specific keyword optimization',
                            '20+ professional templates',
                            'Export to PDF, Word, or shareable link',
                        ] as $feature)
                            <li class="flex items-start gap-3 text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 studai-btn studai-btn-primary">
                        Build Your Resume
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Module 3: Interview AI --}}
        <div class="mb-16 rounded-3xl border border-surface-200 bg-surface-50 overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="p-10 lg:p-12">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-google-yellow-500 to-orange-500 text-white mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-ink-primary mb-4">Interview AI Coach</h3>
                    <p class="text-lg text-ink-secondary mb-6">
                        Practice with an AI interviewer trained on real questions from Google, Amazon, and 500+ companies.
                    </p>
                    <ul class="space-y-3 mb-8">
                        @foreach ([
                            'Voice + video mock interviews',
                            'Company-specific question banks',
                            'Real-time speech analysis & feedback',
                            'Body language coaching (Pro)',
                            'Detailed performance analytics',
                        ] as $feature)
                            <li class="flex items-start gap-3 text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('interview.index') }}" class="inline-flex items-center gap-2 studai-btn studai-btn-primary">
                        Practice Now
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
                <div class="bg-gradient-to-br from-google-yellow-50 to-orange-50 p-10 lg:p-12 flex items-center justify-center">
                    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                        <div class="text-center mb-4">
                            <div class="w-16 h-16 rounded-full bg-google-yellow-100 text-google-yellow-600 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                            </div>
                            <div class="font-semibold text-ink-primary">Mock Interview</div>
                            <div class="text-xs text-ink-muted">Behavioral Round</div>
                        </div>
                        <div class="bg-surface-50 rounded-lg p-4 mb-4">
                            <p class="text-sm text-ink-secondary italic">"Tell me about a time you handled a difficult stakeholder..."</p>
                        </div>
                        <div class="flex items-center justify-center gap-4">
                            <button class="w-12 h-12 rounded-full bg-google-red-100 text-google-red-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                </svg>
                            </button>
                            <button class="w-14 h-14 rounded-full bg-google-green-500 text-white flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                            </button>
                            <button class="w-12 h-12 rounded-full bg-surface-100 text-ink-muted flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Module 4: Market Intelligence --}}
        <div class="mb-16 rounded-3xl border border-surface-200 bg-surface-50 overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-10 lg:p-12 flex items-center justify-center order-2 lg:order-1">
                    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                        <div class="mb-4">
                            <span class="font-semibold text-ink-primary">Senior Product Manager</span>
                            <span class="text-xs text-ink-muted block">Bangalore • Tech Industry</span>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-ink-secondary">Market Salary Range</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-bold text-ink-primary">₹35L</span>
                                    <span class="text-ink-muted">—</span>
                                    <span class="text-lg font-bold text-ink-primary">₹60L</span>
                                </div>
                                <span class="text-xs text-google-green-600 font-medium">You're at 75th percentile</span>
                            </div>
                            <div class="pt-4 border-t border-surface-200">
                                <div class="text-xs text-ink-muted mb-2">In-demand skills</div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2 py-1 bg-google-blue-50 text-google-blue-700 text-xs rounded-full">AI/ML</span>
                                    <span class="px-2 py-1 bg-google-blue-50 text-google-blue-700 text-xs rounded-full">Data Analytics</span>
                                    <span class="px-2 py-1 bg-google-blue-50 text-google-blue-700 text-xs rounded-full">Strategy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-10 lg:p-12 order-1 lg:order-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 text-white mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-ink-primary mb-4">Market Intelligence</h3>
                    <p class="text-lg text-ink-secondary mb-6">
                        Know your worth. Get real-time salary data, skill trends, and industry insights to negotiate like a pro.
                    </p>
                    <ul class="space-y-3 mb-8">
                        @foreach ([
                            'Real-time salary benchmarks by role/city',
                            'Skill demand trends & forecasts',
                            'Company-level compensation insights',
                            'Negotiation scripts backed by data',
                            'Industry hiring trend reports',
                        ] as $feature)
                            <li class="flex items-start gap-3 text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('market.overview') }}" class="inline-flex items-center gap-2 studai-btn studai-btn-primary">
                        Explore Insights
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Module 5 & 6 Grid --}}
        <div class="grid md:grid-cols-2 gap-8">
            {{-- Career Coach --}}
            <div class="rounded-2xl border border-surface-200 bg-surface-50 p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">Career Coach AI</h3>
                <p class="text-ink-secondary mb-6">
                    Your always-on career advisor. Get personalized guidance on career transitions, skill development, and growth strategies.
                </p>
                <ul class="space-y-2 mb-6">
                    @foreach ([
                        '24/7 conversational career advice',
                        'Personalized skill roadmaps',
                        'Career pivot recommendations',
                        'Goal setting & tracking',
                    ] as $feature)
                        <li class="flex items-start gap-2 text-sm text-ink-secondary">
                            <svg class="w-4 h-4 text-google-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('career-coach.index') }}" class="text-google-blue-600 font-semibold text-sm hover:underline inline-flex items-center gap-1">
                    Start a conversation
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>

            {{-- Skill Analyzer --}}
            <div class="rounded-2xl border border-surface-200 bg-surface-50 p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-ink-primary mb-3">Skill Analyzer</h3>
                <p class="text-ink-secondary mb-6">
                    Understand your skill gaps and get AI-powered recommendations for courses, certifications, and projects.
                </p>
                <ul class="space-y-2 mb-6">
                    @foreach ([
                        'Skills assessment from resume/LinkedIn',
                        'Gap analysis vs. target roles',
                        'Curated learning recommendations',
                        'Progress tracking dashboard',
                    ] as $feature)
                        <li class="flex items-start gap-2 text-sm text-ink-secondary">
                            <svg class="w-4 h-4 text-google-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('skills.dashboard') }}" class="text-google-blue-600 font-semibold text-sm hover:underline inline-flex items-center gap-1">
                    Analyze your skills
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Employer Section --}}
<section class="py-20 bg-canvas-subtle">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-purple-600 mb-4 block">For Employers</span>
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">S.C.O.U.T. — Bias-Free AI Hiring</h2>
            <p class="mt-4 text-lg text-ink-secondary max-w-2xl mx-auto">
                Screen. Curate. Onboard. Unify. Track. The complete AI-powered ATS that helps you hire better, faster, and fairer.
            </p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ([
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
                    'title' => 'Bias-Free Screening',
                    'body' => 'AI evaluates skills, not demographics. Remove unconscious bias from the first filter.',
                    'color' => 'from-purple-500 to-pink-500',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                    'title' => 'Talent Marketplace',
                    'body' => 'Access 50K+ pre-screened candidates actively looking. No more waiting for applications.',
                    'color' => 'from-google-blue-500 to-cyan-500',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />',
                    'title' => 'AI Video Interviews',
                    'body' => 'Automated first-round interviews that evaluate soft skills, communication, and culture fit.',
                    'color' => 'from-google-yellow-500 to-orange-500',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                    'title' => 'Rich Analytics',
                    'body' => 'Track time-to-hire, source quality, diversity metrics, and funnel conversion in real-time.',
                    'color' => 'from-google-green-500 to-teal-500',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />',
                    'title' => 'Workflow Automation',
                    'body' => 'Auto-schedule interviews, send follow-ups, and move candidates through stages automatically.',
                    'color' => 'from-indigo-500 to-purple-500',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />',
                    'title' => 'Integrations',
                    'body' => 'Connects with Slack, Teams, Gmail, calendars, and your existing HRIS/ATS tools.',
                    'color' => 'from-pink-500 to-red-500',
                ],
            ] as $feature)
                <div class="rounded-2xl border border-surface-200 bg-white p-6 hover:shadow-card transition-shadow">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $feature['color'] }} text-white mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            {!! $feature['icon'] !!}
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-ink-primary mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-ink-secondary">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-12">
            <a href="{{ route('register') }}" class="studai-btn studai-btn-primary studai-btn-lg inline-flex items-center gap-2">
                Start Hiring with S.C.O.U.T.
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- Comparison Table --}}
<section class="py-20 bg-white">
    <div class="mx-auto max-w-5xl px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">Why StudAI Path?</h2>
            <p class="mt-4 text-lg text-ink-secondary">See how we stack up against traditional job boards.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b-2 border-surface-200">
                        <th class="text-left py-4 px-4 text-ink-secondary font-medium">Feature</th>
                        <th class="text-center py-4 px-4">
                            <span class="text-google-blue-600 font-bold">StudAI Path</span>
                        </th>
                        <th class="text-center py-4 px-4 text-ink-muted">Traditional Job Boards</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200">
                    @foreach ([
                        ['feature' => 'Automatic job applications', 'studai' => true, 'other' => false],
                        ['feature' => 'AI interview practice', 'studai' => true, 'other' => false],
                        ['feature' => 'Real-time salary insights', 'studai' => true, 'other' => false],
                        ['feature' => 'Resume optimization', 'studai' => true, 'other' => false],
                        ['feature' => 'Personalized career coaching', 'studai' => true, 'other' => false],
                        ['feature' => '24/7 autonomous agent', 'studai' => true, 'other' => false],
                        ['feature' => 'Basic job search', 'studai' => true, 'other' => true],
                    ] as $row)
                        <tr>
                            <td class="py-4 px-4 text-ink-primary">{{ $row['feature'] }}</td>
                            <td class="py-4 px-4 text-center">
                                @if ($row['studai'])
                                    <svg class="w-6 h-6 text-google-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-google-red-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if ($row['other'])
                                    <svg class="w-6 h-6 text-google-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-ink-muted mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-24 bg-gradient-to-br from-google-blue-600 to-purple-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
            Ready to put your career on autopilot?
        </h2>
        <p class="text-lg text-white/80 mb-8">
            Join 50,000+ professionals who let AI manage their job search.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Free Today
            </a>
            <a href="{{ route('contact') }}" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                Talk to Sales
            </a>
        </div>
    </div>
</section>
@endsection
