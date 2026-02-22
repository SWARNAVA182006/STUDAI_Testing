@extends('layouts.app')

@section('title', 'How StudAI Career Works | Intelligent Job Matching & Hiring Workflows')

@section('meta')
<meta name="description" content="See how StudAI Career guides job seekers and employers through AI-powered job matching, resume optimization, and hiring workflows in five simple steps.">
<meta name="keywords" content="how it works, AI job search, applicant tracking, hiring automation, job seeker workflow, employer workflow">
<meta property="og:title" content="How StudAI Career Works">
<meta property="og:description" content="Discover the AI-driven journey for job seekers and employers on StudAI Career—from profile setup to successful hires.">
<link rel="canonical" href="{{ route('how-it-works') }}">
@endsection

@section('content')
<section class="relative bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white overflow-hidden">
    <div class="absolute inset-0 opacity-30">
        <div class="absolute -top-20 -left-10 w-80 h-80 bg-pink-500/40 blur-3xl rounded-full"></div>
        <div class="absolute top-32 right-16 w-96 h-96 bg-blue-500/30 blur-3xl rounded-full"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[28rem] h-[28rem] bg-purple-500/20 blur-3xl rounded-full"></div>
    </div>
    <div class="relative mx-auto max-w-6xl px-6 py-24 lg:py-32">
        <div class="flex flex-col gap-6 text-center">
            <span class="mx-auto inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1 text-sm font-semibold uppercase tracking-widest">How It Works</span>
            <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl">Your AI Co-Pilot For Every Career Move</h1>
            <p class="mx-auto max-w-3xl text-lg text-slate-200 sm:text-xl">
                StudAI Career combines intelligent profiling, semantic job matching, and automated workflows to deliver measurable outcomes for job seekers and employers.
                Here9s the five-step journey that keeps both sides aligned and hiring velocity at peak.
            </p>
            <div class="mx-auto mt-6 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="group inline-flex items-center rounded-xl bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 px-8 py-3 text-lg font-semibold text-white shadow-2xl transition-all duration-300 hover:shadow-pink-500/60">
                    Start Free Trial
                    <svg class="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center rounded-xl border border-white/30 bg-white/10 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:bg-white/20">
                    Talk To Sales
                </a>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-950 py-20">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid gap-10 lg:grid-cols-[1.6fr_1fr]">
            <div class="space-y-12">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-8 shadow-2xl shadow-pink-500/10">
                    <div class="flex items-center gap-3 text-pink-400">
                        <span class="text-sm font-bold uppercase tracking-widest">For Job Seekers</span>
                        <span class="h-[2px] w-12 bg-gradient-to-r from-pink-500 to-purple-500"></span>
                    </div>
                    <h2 class="mt-3 text-3xl font-semibold text-white">A Guided Journey From Profile To Offer</h2>
                    <p class="mt-2 text-slate-300">Every step is optimized with AI prompts, smart defaults, and proactive reminders.</p>
                    <div class="mt-6 space-y-6">
                        @php
                            $jobSeekerSteps = [
                                [
                                    'step' => '01',
                                    'title' => 'Discover Your Career Baseline',
                                    'body' => 'Import your resume or LinkedIn profile and let our AI analyze skills, accomplishments, achievements, and gaps with a tailored strengths report.'
                                ],
                                [
                                    'step' => '02',
                                    'title' => 'Generate a Magnetic Profile',
                                    'body' => 'Turn your baseline into a structured, recruiter-friendly portfolio complete with AI-crafted positioning statements and impact bullet points.'
                                ],
                                [
                                    'step' => '03',
                                    'title' => 'Match With Precision',
                                    'body' => 'Run semantic searches across 50,000+ openings. We rank roles on skill fit, growth trajectory, culture alignment, and salary benchmarks.'
                                ],
                                [
                                    'step' => '04',
                                    'title' => 'Automate Applications & Outreach',
                                    'body' => 'Use one-click Smart Apply to personalize cover letters, answer screening questions, and send warm outreach messages powered by AI.'
                                ],
                                [
                                    'step' => '05',
                                    'title' => 'Interview With Confidence',
                                    'body' => 'Ace interviews with adaptive practice sessions, behavioral story prompts, and negotiation strategy briefings tuned to each employer.'
                                ],
                            ];
                        @endphp
                        @foreach ($jobSeekerSteps as $step)
                            <div class="flex flex-col gap-4 rounded-3xl border border-slate-800 bg-slate-950/80 p-6 transition hover:border-pink-500/60">
                                <div class="flex items-center gap-4">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-500 to-purple-600 text-xl font-bold">{{ $step['step'] }}</span>
                                    <h3 class="text-2xl font-semibold text-white">{{ $step['title'] }}</h3>
                                </div>
                                <p class="text-slate-300">{{ $step['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="grid gap-8 rounded-3xl border border-slate-800 bg-slate-900/60 p-8 md:grid-cols-2">
                    @php
                        $seekerHighlights = [
                            ['title' => 'AI Resume Refiner', 'body' => 'Instantly adapt your resume to every job description while preserving metrics and unique achievements.'],
                            ['title' => 'Autonomous Agent', 'body' => 'Queue tasks for your StudAI agent9from follow-up emails to new job alerts9and get daily digests.'],
                            ['title' => 'Skill Roadmaps', 'body' => 'Receive curated learning playlists to close the top gaps blocking your dream role.'],
                            ['title' => 'Offer Intelligence', 'body' => 'Benchmark offers in real-time with location and seniority adjusted salary reports.'],
                        ];
                    @endphp
                    @foreach ($seekerHighlights as $highlight)
                        <div class="flex flex-col gap-3 rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                            <div class="flex items-center gap-3 text-pink-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path d="M12 3v18M3 12h18" />
                                </svg>
                                <span class="text-lg font-semibold text-white">{{ $highlight['title'] }}</span>
                            </div>
                            <p class="text-slate-300">{{ $highlight['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col gap-8">
                <div class="rounded-3xl border border-blue-500/40 bg-blue-500/10 p-8">
                    <div class="flex items-center gap-3 text-blue-300">
                        <span class="text-sm font-bold uppercase tracking-widest">For Employers</span>
                        <span class="h-[2px] w-12 bg-gradient-to-r from-blue-400 to-cyan-400"></span>
                    </div>
                    <h2 class="mt-3 text-3xl font-semibold text-white">Close Roles 2.3
Faster</h2>
                    <p class="mt-2 text-slate-200">Deep insights, pipeline automation, and bias-aware filtering to power your next hiring sprint.</p>
                    <ul class="mt-6 space-y-5 text-slate-300">
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/30 text-sm font-semibold text-blue-200">1</span>
                            Unified workspace for requisition intake, AI-generated scorecards, and approval routing.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/30 text-sm font-semibold text-blue-200">2</span>
                            Semantic candidate sourcing with diversity-friendly ranking and instant shortlists synced to your ATS.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/30 text-sm font-semibold text-blue-200">3</span>
                            Automated interview coordination, feedback capture, and next-step nudges across hiring teams.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500/30 text-sm font-semibold text-blue-200">4</span>
                            Offer generation with compliant templates, compensation guardrails, and digital acceptance tracking.
                        </li>
                    </ul>
                </div>
                <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
                    <h3 class="text-2xl font-semibold text-white">Unified Analytics</h3>
                    <p class="mt-2 text-slate-300">Real-time dashboards track fill rates, pipeline health, and AI credit usage across teams. Export to your BI stack in one click.</p>
                    <dl class="mt-6 grid gap-6 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm uppercase tracking-wide text-slate-400">Average Time To Fill</dt>
                            <dd class="mt-1 text-3xl font-semibold text-white">18 days</dd>
                        </div>
                        <div>
                            <dt class="text-sm uppercase tracking-wide text-slate-400">Interview-to-offer Ratio</dt>
                            <dd class="mt-1 text-3xl font-semibold text-white">4:1</dd>
                        </div>
                        <div>
                            <dt class="text-sm uppercase tracking-wide text-slate-400">Candidate NPS</dt>
                            <dd class="mt-1 text-3xl font-semibold text-white">+68</dd>
                        </div>
                    </dl>
                </div>
                <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-8">
                    <h3 class="text-2xl font-semibold text-white">Integrations That Just Work</h3>
                    <p class="mt-2 text-slate-300">Connect with Greenhouse, Lever, Workday, Slack, Microsoft Teams, Zapier, and 30+ HRIS / ATS systems out of the box.</p>
                    <div class="mt-6 grid grid-cols-2 gap-4 text-sm text-slate-200">
                        <span class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">HR Platforms</span>
                        <span class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">Collaboration Tools</span>
                        <span class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">Calendar & Email</span>
                        <span class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">Payroll Systems</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-950 py-20">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid gap-10 lg:grid-cols-2">
            <div class="space-y-6">
                <span class="inline-flex items-center rounded-full bg-pink-500/10 px-4 py-1 text-sm font-semibold uppercase tracking-wider text-pink-300">Outcomes</span>
                <h2 class="text-3xl font-bold text-white sm:text-4xl">Proof That The Workflow Works</h2>
                <p class="text-lg text-slate-300">Every rollout is measured. Here9s what teams achieve within the first 90 days on StudAI Career.</p>
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="rounded-3xl border border-pink-500/30 bg-pink-500/10 p-6">
                        <div class="text-4xl font-bold text-white">62%</div>
                        <p class="mt-2 text-sm uppercase tracking-wider text-pink-200">Faster Job Discovery</p>
                        <p class="mt-2 text-slate-200">Seeker dashboards surface the top 30 roles ranked by impact-fit instantly.</p>
                    </div>
                    <div class="rounded-3xl border border-blue-500/30 bg-blue-500/10 p-6">
                        <div class="text-4xl font-bold text-white">3.20
                        </div>
                        <p class="mt-2 text-sm uppercase tracking-wider text-blue-200">Higher Interview-to-offer</p>
                        <p class="mt-2 text-slate-200">Interview prep and hiring team playbooks align conversations faster.</p>
                    </div>
                    <div class="rounded-3xl border border-purple-500/30 bg-purple-500/10 p-6">
                        <div class="text-4xl font-bold text-white">40%
                        </div>
                        <p class="mt-2 text-sm uppercase tracking-wider text-purple-200">Reduced Manual Ops</p>
                        <p class="mt-2 text-slate-200">Workflow automations eliminate repetitive status updates and reminders.</p>
                    </div>
                    <div class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-6">
                        <div class="text-4xl font-bold text-white">+52</div>
                        <p class="mt-2 text-sm uppercase tracking-wider text-emerald-200">Candidate NPS Boost</p>
                        <p class="mt-2 text-slate-200">Personalized communication keeps candidates informed at every step.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-6">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
                    <h3 class="text-2xl font-semibold text-white">What Happens Behind The Scenes</h3>
                    <ul class="mt-4 space-y-4 text-slate-300">
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-sm font-semibold text-pink-300">1</span>
                            AI orchestrates data pipelines across profile, job, and engagement signals.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-sm font-semibold text-pink-300">2</span>
                            Custom scoring models weigh skills, experience, aspirations, and cultural preferences.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-sm font-semibold text-pink-300">3</span>
                            Feedback loops retrain match algorithms every 48 hours based on outcome data.
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-sm font-semibold text-pink-300">4</span>
                            Compliance engine enforces GDPR, DPDP (India), EEOC, and SOC 2 best practices.
                        </li>
                    </ul>
                </div>
                <div class="rounded-3xl border border-slate-800 bg-slate-950/60 p-8">
                    <h3 class="text-2xl font-semibold text-white">Security & Governance</h3>
                    <p class="mt-2 text-slate-300">Data is segmented across transactional and analytics databases with strict role-based access, encrypted vaults for resumes, and continuous anomaly monitoring.</p>
                    <ul class="mt-4 space-y-3 text-sm text-slate-400">
                        <li>
                            <span class="font-semibold text-slate-200">Enterprise SSO:</span> Microsoft Entra ID, Okta, Google Workspace supported.
                        </li>
                        <li>
                            <span class="font-semibold text-slate-200">Data Residency:</span> Choose India (Mumbai), EU (Frankfurt), or US (Oregon) clusters.
                        </li>
                        <li>
                            <span class="font-semibold text-slate-200">Audit Trails:</span> Immutable logs stored in Azure Confidential Ledger.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 py-24">
    <div class="mx-auto max-w-6xl px-6">
        <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-10 text-center shadow-2xl shadow-pink-500/20">
            <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready To Experience The StudAI Workflow?</h2>
            <p class="mt-4 text-lg text-slate-300">Spin up your workspace in under 3 minutes. Invite teammates, import jobs, and see your first AI-generated matches instantly.</p>
            <div class="mt-6 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="group inline-flex items-center rounded-xl bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:shadow-2xl hover:shadow-pink-500/40">
                    Create Your Workspace
                    <svg class="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="{{ route('pricing') }}" class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:bg-white/20">
                    Explore Pricing Plans
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How StudAI Career Works',
    'description' => 'Learn how StudAI Career guides job seekers from profile creation to accepted offer using AI-powered workflows.',
    'totalTime' => 'PT2H',
    'step' => array_map(fn ($step) => [
        '@type' => 'HowToStep',
        'name' => $step['title'],
        'position' => (int) $step['step'],
        'itemListElement' => [
            '@type' => 'HowToDirection',
            'text' => $step['body'],
        ],
    ], $jobSeekerSteps),
    'tool' => [
        ['@type' => 'HowToTool', 'name' => 'StudAI Career Profile Builder'],
        ['@type' => 'HowToTool', 'name' => 'Semantic Job Match Engine'],
        ['@type' => 'HowToTool', 'name' => 'Interview & Negotiation Coach'],
    ],
    'supply' => [
        ['@type' => 'HowToSupply', 'name' => 'Resume or LinkedIn profile'],
        ['@type' => 'HowToSupply', 'name' => 'Preferred job preferences'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
