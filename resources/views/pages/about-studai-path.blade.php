{{--
    StudAI Path — About Page
    Brand story, mission, and team
--}}
@extends('layouts.app')

@section('title', 'About StudAI Path | Our Mission to Automate Career Success')

@section('meta')
<meta name="description" content="Meet the team building India's first autonomous career OS. Learn how we're using AI to transform job search and hiring for millions.">
<meta property="og:title" content="About StudAI Path — Your Career. On Autopilot.">
<meta property="og:description" content="We're building the future of work. One automated career at a time.">
<link rel="canonical" href="{{ route('about') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
        <div class="absolute top-20 right-0 h-[28rem] w-[28rem] rounded-full bg-purple-500/20 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-6xl px-6 py-24 lg:py-32">
        <div class="grid gap-12 lg:grid-cols-[1.4fr_1fr]">
            <div class="space-y-6">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-semibold uppercase tracking-widest">
                    Our Story
                </span>
                <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl">
                    Building the career operating system for ambitious talent.
                </h1>
                <p class="text-lg text-slate-200">
                    StudAI Path exists to eliminate the friction between talent and opportunity. We're combining AI agents, market intelligence, and human-centered design to automate career success for millions.
                </p>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <a href="{{ route('register') }}" class="group inline-flex items-center rounded-xl bg-white px-8 py-3 text-lg font-semibold text-google-blue-600 transition-all duration-300 hover:shadow-2xl">
                        Join the Movement
                        <svg class="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="{{ route('about') }}#careers" class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 px-8 py-3 text-lg font-semibold text-white transition-all duration-300 hover:bg-white/20">
                        We're Hiring
                    </a>
                </div>
            </div>
            <div class="rounded-3xl border border-google-blue-500/30 bg-slate-900/70 p-8 shadow-2xl">
                <h2 class="text-2xl font-semibold text-white">Impact So Far</h2>
                <dl class="mt-6 grid gap-6 sm:grid-cols-2">
                    @foreach ([
                        ['label' => 'Careers Launched', 'value' => '50K+'],
                        ['label' => 'Jobs Indexed', 'value' => '2.5M'],
                        ['label' => 'Countries', 'value' => '12'],
                        ['label' => 'Interview Success Rate', 'value' => '94%'],
                    ] as $stat)
                        <div>
                            <dd class="text-3xl font-bold text-white">{{ $stat['value'] }}</dd>
                            <dt class="mt-1 text-xs uppercase tracking-widest text-slate-400">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
</section>

{{-- Mission Section --}}
<section class="py-20 bg-white">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid gap-12 lg:grid-cols-2">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">Our Mission</span>
                <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl mb-6">
                    Put every career on autopilot.
                </h2>
                <p class="text-lg text-ink-secondary mb-8">
                    Job searching is broken. Applying to 100 jobs. Hearing back from 3. Preparing for interviews alone. Negotiating without data. We're fixing all of it.
                </p>
                <div class="space-y-6">
                    @foreach ([
                        ['title' => 'Automation First', 'body' => 'Why spend hours applying when AI can do it better? Our autonomous agent finds, matches, and applies to jobs 24/7.'],
                        ['title' => 'Insight-Driven', 'body' => 'Every decision backed by real-time market data. Know your worth. Understand trends. Move strategically.'],
                        ['title' => 'Human at Heart', 'body' => 'Technology amplifies human potential. We automate the tedious so you can focus on what matters — acing interviews and making decisions.'],
                    ] as $principle)
                        <div class="rounded-xl border border-surface-200 bg-surface-50 p-6">
                            <h3 class="text-lg font-semibold text-ink-primary mb-2">{{ $principle['title'] }}</h3>
                            <p class="text-ink-secondary">{{ $principle['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-3xl border border-surface-200 bg-surface-50 p-8">
                <h3 class="text-2xl font-semibold text-ink-primary mb-6">The Problems We Solve</h3>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-lg bg-google-red-50 text-google-red-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-ink-primary">Manual Applications</h4>
                            <p class="text-sm text-ink-secondary">Average job seeker spends 11 hours/week applying. We reduce that to 0.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-lg bg-google-red-50 text-google-red-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-ink-primary">Interview Anxiety</h4>
                            <p class="text-sm text-ink-secondary">72% fail due to lack of prep. Our AI interviewer ensures you walk in ready.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-lg bg-google-red-50 text-google-red-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-ink-primary">Salary Guesswork</h4>
                            <p class="text-sm text-ink-secondary">Most accept first offers. We provide market data + negotiation coaching.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-lg bg-google-red-50 text-google-red-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-ink-primary">Bias in Hiring</h4>
                            <p class="text-sm text-ink-secondary">Our S.C.O.U.T. system ensures employers see skills, not bias signals.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Timeline --}}
<section class="py-20 bg-canvas-subtle">
    <div class="mx-auto max-w-4xl px-6">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">Our Journey</span>
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">From Idea to Impact</h2>
        </div>
        <div class="space-y-8">
            @foreach ([
                ['year' => '2022', 'title' => 'The Spark', 'body' => 'Frustrated by applying to 200+ jobs manually, our founder built a prototype that applied to jobs automatically. Friends wanted it.'],
                ['year' => '2023', 'title' => 'First 10,000 Users', 'body' => 'Launched publicly. Added resume AI, interview prep, and market intelligence. Crossed 10K users in 6 months.'],
                ['year' => '2024', 'title' => 'S.C.O.U.T. for Employers', 'body' => 'Realized employers need help too. Built bias-free AI screening. Fortune 500s took notice. Launched B2B arm.'],
                ['year' => '2025', 'title' => '50K Careers & Counting', 'body' => 'Expanded to 12 countries. Launched autonomous agent 2.0. Mission: 1 million careers on autopilot by 2027.'],
            ] as $milestone)
                <div class="flex gap-6 bg-white rounded-2xl border border-surface-200 p-6 shadow-card">
                    <div class="text-3xl font-bold text-google-blue-600">{{ $milestone['year'] }}</div>
                    <div>
                        <h3 class="text-xl font-semibold text-ink-primary mb-2">{{ $milestone['title'] }}</h3>
                        <p class="text-ink-secondary">{{ $milestone['body'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Leadership --}}
<section class="py-20 bg-white">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">Leadership</span>
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">Built by People Who Get It</h2>
            <p class="mt-4 text-lg text-ink-secondary max-w-2xl mx-auto">
                We've been job seekers, recruiters, and hiring managers. We know both sides of the table.
            </p>
        </div>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['name' => 'Arjun Mehta', 'role' => 'CEO & Co-founder', 'bio' => 'Applied to 200+ jobs before founding StudAI Path. Former product at Microsoft. IIT Delhi.'],
                ['name' => 'Priya Sharma', 'role' => 'CTO & Co-founder', 'bio' => 'Built ML systems at Google. Obsessed with making AI practical. Stanford MS.'],
                ['name' => 'Rahul Verma', 'role' => 'Head of AI', 'bio' => 'Led NLP at Amazon. Published researcher. Makes our AI actually understand jobs.'],
                ['name' => 'Sneha Iyer', 'role' => 'VP Product', 'bio' => 'Former PM at Razorpay. Designs experiences that feel like magic.'],
                ['name' => 'Karan Singh', 'role' => 'VP Growth', 'bio' => 'Scaled Swiggy B2B. Now bringing StudAI Path to every job seeker in India.'],
                ['name' => 'Dr. Ananya Rao', 'role' => 'Head of AI Ethics', 'bio' => 'PhD from MIT. Ensures our AI is fair, explainable, and bias-free.'],
            ] as $leader)
                <div class="rounded-2xl border border-surface-200 bg-surface-50 p-6 hover:shadow-card transition-shadow">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-google-blue-500 to-purple-500 text-lg font-semibold text-white mb-4">
                        {{ collect(explode(' ', $leader['name']))->map(fn($part) => strtoupper(substr($part, 0, 1)))->implode('') }}
                    </div>
                    <h3 class="text-lg font-semibold text-ink-primary">{{ $leader['name'] }}</h3>
                    <p class="text-sm text-google-blue-600 font-medium mb-3">{{ $leader['role'] }}</p>
                    <p class="text-sm text-ink-secondary">{{ $leader['bio'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Culture & Hiring --}}
<section class="py-20 bg-canvas-subtle">
    <div class="mx-auto max-w-6xl px-6">
        <div class="grid gap-12 lg:grid-cols-2 items-center">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">Join Us</span>
                <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl mb-6">
                    Build the Future of Work With Us
                </h2>
                <p class="text-lg text-ink-secondary mb-8">
                    We're a distributed-first team across Bangalore, Delhi, and remote. We move fast, ship often, and believe great ideas can come from anywhere.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 text-ink-secondary">
                        <svg class="w-5 h-5 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span><strong class="text-ink-primary">Remote-first:</strong> Work from anywhere in India</span>
                    </li>
                    <li class="flex items-center gap-3 text-ink-secondary">
                        <svg class="w-5 h-5 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span><strong class="text-ink-primary">Learning budget:</strong> ₹1L/year for courses, books, conferences</span>
                    </li>
                    <li class="flex items-center gap-3 text-ink-secondary">
                        <svg class="w-5 h-5 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span><strong class="text-ink-primary">Equity:</strong> Everyone gets skin in the game</span>
                    </li>
                    <li class="flex items-center gap-3 text-ink-secondary">
                        <svg class="w-5 h-5 text-google-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span><strong class="text-ink-primary">Health:</strong> Full medical for you + family</span>
                    </li>
                </ul>
                <a href="{{ route('about') }}#careers" class="inline-flex items-center gap-2 mt-8 studai-btn studai-btn-primary studai-btn-lg">
                    See Open Roles
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
            <div class="bg-white rounded-2xl border border-surface-200 p-8">
                <h3 class="text-xl font-semibold text-ink-primary mb-6">Our Values</h3>
                <div class="space-y-6">
                    <div>
                        <h4 class="font-semibold text-ink-primary flex items-center gap-2">
                            <span class="text-xl">🚀</span> Ship It
                        </h4>
                        <p class="text-sm text-ink-secondary mt-1">Done is better than perfect. We iterate based on real feedback.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-ink-primary flex items-center gap-2">
                            <span class="text-xl">🔍</span> Obsess Over Users
                        </h4>
                        <p class="text-sm text-ink-secondary mt-1">Every decision starts with: "How does this help someone find a job?"</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-ink-primary flex items-center gap-2">
                            <span class="text-xl">🤝</span> Radical Candor
                        </h4>
                        <p class="text-sm text-ink-secondary mt-1">We give direct feedback because we care about growth.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-ink-primary flex items-center gap-2">
                            <span class="text-xl">⚖️</span> Build for Everyone
                        </h4>
                        <p class="text-sm text-ink-secondary mt-1">Fairness isn't a feature. It's a requirement.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
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
                Talk to Us
            </a>
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'StudAI Path',
    'alternateName' => 'StudAI Technologies Pvt. Ltd.',
    'url' => url('/'),
    'logo' => asset('images/logo-dark.svg'),
    'foundingDate' => '2022',
    'founders' => [
        ['@type' => 'Person', 'name' => 'Arjun Mehta'],
        ['@type' => 'Person', 'name' => 'Priya Sharma'],
    ],
    'description' => 'India\'s first autonomous career operating system. AI-powered job search, interview prep, and hiring.',
    'sameAs' => [
        'https://www.linkedin.com/company/studai-path',
        'https://twitter.com/studaipath'
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'contactType' => 'customer support',
        'email' => 'hello@studaipath.com',
        'areaServed' => 'IN',
        'availableLanguage' => ['English', 'Hindi'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
