{{--
    StudAI Path — How It Works Page
    The Career OS Workflow
--}}
@extends('layouts.app')

@section('title', 'How It Works — StudAI Path | The Autonomous Career OS')

@section('meta')
<meta name="description" content="See how StudAI Path automates your job search in 5 steps: from profile creation to offer negotiation. AI handles the work while you focus on what matters.">
<meta property="og:title" content="How StudAI Path Works — Career on Autopilot">
<meta property="og:description" content="Discover the 5-step workflow that helps job seekers land 3x more interviews with zero manual applications.">
<link rel="canonical" href="{{ route('how-it-works') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
        <div class="absolute top-20 right-0 h-[28rem] w-[28rem] rounded-full bg-purple-500/20 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-6xl px-6 py-24 lg:py-32 text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-semibold uppercase tracking-widest mb-6">
            The Career OS Workflow
        </span>
        <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl max-w-4xl mx-auto">
            From "Looking for Work" to "Offer Accepted" in 5 Steps
        </h1>
        <p class="mt-6 text-lg text-slate-200 max-w-2xl mx-auto">
            Your autonomous career agent works 24/7 — finding jobs, submitting applications, and preparing you for interviews. Here's how.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Free Today
            </a>
            <a href="#step-1" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                See the Steps
            </a>
        </div>
    </div>
</section>

{{-- Steps Section --}}
<section class="py-20 bg-white">
    <div class="mx-auto max-w-5xl px-6">
        @php
            $steps = [
                [
                    'step' => '01',
                    'id' => 'step-1',
                    'title' => 'Tell Us Your Goals',
                    'subtitle' => 'Setup takes 3 minutes',
                    'body' => 'Import your resume or LinkedIn profile. Our AI analyzes your experience, skills, and achievements to understand where you are and where you want to go.',
                    'features' => [
                        'One-click LinkedIn import',
                        'AI skill extraction & gap analysis',
                        'Salary expectation calibration',
                        'Location & remote preferences',
                    ],
                    'color' => 'google-blue',
                    'visual' => 'profile',
                ],
                [
                    'step' => '02',
                    'id' => 'step-2',
                    'title' => 'AI Finds Your Perfect Matches',
                    'subtitle' => 'Runs continuously, 24/7',
                    'body' => 'Your autonomous agent scans 50+ job boards every hour, ranking opportunities by skill fit, growth potential, salary match, and culture alignment.',
                    'features' => [
                        'Semantic job matching (not just keywords)',
                        'Company culture & Glassdoor insights',
                        'Salary benchmarking vs. market rates',
                        'Daily digest of top opportunities',
                    ],
                    'color' => 'purple',
                    'visual' => 'matching',
                ],
                [
                    'step' => '03',
                    'id' => 'step-3',
                    'title' => 'Auto-Apply With Tailored Materials',
                    'subtitle' => 'You approve, AI executes',
                    'body' => 'Review matched jobs and hit "Apply" — or set rules for auto-apply. AI generates custom cover letters, answers screening questions, and submits applications.',
                    'features' => [
                        'AI-written cover letters (editable)',
                        'Smart answers to common questions',
                        'ATS-optimized resume formatting',
                        'Application tracking dashboard',
                    ],
                    'color' => 'google-green',
                    'visual' => 'apply',
                ],
                [
                    'step' => '04',
                    'id' => 'step-4',
                    'title' => 'Prepare to Ace the Interview',
                    'subtitle' => 'Company-specific coaching',
                    'body' => 'When you get an interview, our AI creates a prep kit: likely questions, STAR-format answer templates, company research, and mock interview sessions.',
                    'features' => [
                        'Question prediction based on role + company',
                        'Voice/video mock interviews with AI',
                        'Body language & speech coaching',
                        'Detailed performance analytics',
                    ],
                    'color' => 'google-yellow',
                    'visual' => 'interview',
                ],
                [
                    'step' => '05',
                    'id' => 'step-5',
                    'title' => 'Negotiate & Accept With Confidence',
                    'subtitle' => 'Data-backed negotiation',
                    'body' => 'Got an offer? We show you market benchmarks, suggest counter-offers, and provide scripts to maximize your compensation package.',
                    'features' => [
                        'Real-time salary comparisons',
                        'Negotiation strategy recommendations',
                        'Counter-offer scripts & talking points',
                        'Total compensation calculator',
                    ],
                    'color' => 'teal',
                    'visual' => 'negotiate',
                ],
            ];
        @endphp

        @foreach ($steps as $index => $step)
            <div id="{{ $step['id'] }}" class="relative {{ $index < count($steps) - 1 ? 'pb-20' : '' }}">
                {{-- Connector Line --}}
                @if ($index < count($steps) - 1)
                    <div class="absolute left-6 top-20 bottom-0 w-px bg-gradient-to-b from-{{ $step['color'] }}-500 to-{{ $steps[$index + 1]['color'] }}-500 hidden lg:block"></div>
                @endif

                <div class="grid lg:grid-cols-[auto_1fr] gap-8">
                    {{-- Step Number --}}
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-{{ $step['color'] }}-500 to-{{ $step['color'] }}-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                            {{ $step['step'] }}
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="rounded-3xl border border-surface-200 bg-surface-50 p-8 lg:p-10">
                        <span class="text-xs font-semibold uppercase tracking-widest text-{{ $step['color'] }}-600 mb-2 block">{{ $step['subtitle'] }}</span>
                        <h2 class="text-2xl lg:text-3xl font-bold text-ink-primary mb-4">{{ $step['title'] }}</h2>
                        <p class="text-lg text-ink-secondary mb-8">{{ $step['body'] }}</p>
                        
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ($step['features'] as $feature)
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-{{ $step['color'] }}-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-ink-secondary">{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- Results Section --}}
<section class="py-20 bg-canvas-subtle">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center mb-16">
            <span class="text-xs font-semibold uppercase tracking-widest text-google-blue-600 mb-4 block">Real Results</span>
            <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl">What Happens When Your Career Runs on Autopilot</h2>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['value' => '11 hrs', 'label' => 'Saved per Week', 'detail' => 'No more manual applications'],
                ['value' => '3.2×', 'label' => 'More Interviews', 'detail' => 'Tailored applications win'],
                ['value' => '94%', 'label' => 'Interview Confidence', 'detail' => 'After AI mock sessions'],
                ['value' => '₹4.2L', 'label' => 'Avg. Salary Boost', 'detail' => 'With negotiation coaching'],
            ] as $stat)
                <div class="rounded-2xl border border-surface-200 bg-white p-6 text-center">
                    <div class="text-4xl font-bold text-google-blue-600 mb-2">{{ $stat['value'] }}</div>
                    <div class="text-lg font-semibold text-ink-primary mb-1">{{ $stat['label'] }}</div>
                    <div class="text-sm text-ink-muted">{{ $stat['detail'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- For Employers --}}
<section class="py-20 bg-white">
    <div class="mx-auto max-w-6xl px-6">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-purple-600 mb-4 block">For Employers</span>
                <h2 class="text-3xl font-bold text-ink-primary sm:text-4xl mb-6">
                    Flip the Script: Let Talent Come to You
                </h2>
                <p class="text-lg text-ink-secondary mb-8">
                    With S.C.O.U.T. AI, you get a bias-free talent pipeline that screens, ranks, and schedules interviews automatically. Close roles 2.3× faster.
                </p>
                <div class="space-y-4 mb-8">
                    @foreach ([
                        'Post jobs once → distribute to 50+ channels',
                        'AI screens applications using skills, not demographics',
                        'Automated interview scheduling & reminders',
                        'Rich analytics on time-to-hire, diversity, and funnel health',
                    ] as $feature)
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-ink-secondary">{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('register') }}" class="studai-btn studai-btn-primary studai-btn-lg inline-flex items-center gap-2">
                    Start Hiring with S.C.O.U.T.
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-3xl p-8 border border-purple-100">
                <div class="bg-white rounded-2xl shadow-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-semibold text-ink-primary">Hiring Pipeline</span>
                        <span class="text-xs px-2 py-1 bg-google-green-100 text-google-green-700 rounded-full">Live</span>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-ink-secondary">Applications</span>
                                <span class="font-semibold text-ink-primary">847</span>
                            </div>
                            <div class="h-2 bg-surface-200 rounded-full">
                                <div class="h-2 bg-google-blue-500 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-ink-secondary">AI Shortlisted</span>
                                <span class="font-semibold text-ink-primary">124</span>
                            </div>
                            <div class="h-2 bg-surface-200 rounded-full">
                                <div class="h-2 bg-purple-500 rounded-full" style="width: 14.6%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-ink-secondary">Interviewed</span>
                                <span class="font-semibold text-ink-primary">42</span>
                            </div>
                            <div class="h-2 bg-surface-200 rounded-full">
                                <div class="h-2 bg-google-yellow-500 rounded-full" style="width: 5%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-ink-secondary">Offers Sent</span>
                                <span class="font-semibold text-google-green-600">8</span>
                            </div>
                            <div class="h-2 bg-surface-200 rounded-full">
                                <div class="h-2 bg-google-green-500 rounded-full" style="width: 1%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-surface-200 text-center">
                        <span class="text-sm text-ink-muted">Avg. Time to Hire: <strong class="text-ink-primary">18 days</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="py-20 bg-canvas-subtle">
    <div class="mx-auto max-w-4xl px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-ink-primary mb-4">Common Questions</h2>
        </div>
        <div class="space-y-4">
            @foreach ([
                [
                    'question' => 'How is this different from regular job boards?',
                    'answer' => 'Job boards just list openings. StudAI Path actually applies for you, customizes each application, and preps you for interviews. It\'s the difference between a search engine and a full-time career assistant.',
                ],
                [
                    'question' => 'Will employers know AI helped with my application?',
                    'answer' => 'No. Your applications look 100% human-written because they\'re based on your real experience and voice. AI just removes the tedious work of tailoring each one.',
                ],
                [
                    'question' => 'What if I want to control which jobs I apply to?',
                    'answer' => 'You have full control. Use manual review mode to approve each application, or set auto-apply rules (e.g., only remote jobs, only 20L+ salary) and let AI handle the rest.',
                ],
                [
                    'question' => 'How accurate is the interview prep?',
                    'answer' => 'Our question prediction is trained on real interview data from 500+ companies. Users report 72% of questions in real interviews match our prep materials.',
                ],
                [
                    'question' => 'Is there a limit to how many jobs I can apply to?',
                    'answer' => 'Free plan: 20 applications/month. Pro plan: unlimited applications. Executive plan: unlimited + priority screening visibility.',
                ],
            ] as $index => $faq)
                <div class="rounded-2xl border border-surface-200 bg-white" x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full text-left p-6 focus:outline-none focus:ring-2 focus:ring-google-blue-500 rounded-2xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-ink-primary pr-4">{{ $faq['question'] }}</h3>
                            <svg class="w-5 h-5 text-google-blue-600 transform transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <div x-show="open" x-transition class="px-6 pb-6">
                        <p class="text-ink-secondary">{{ $faq['answer'] }}</p>
                    </div>
                </div>
            @endforeach
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
            <a href="{{ route('pricing') }}" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                See Pricing
            </a>
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How StudAI Path Works',
    'description' => 'Learn how StudAI Path automates your job search from profile creation to offer acceptance in 5 simple steps.',
    'totalTime' => 'PT30M',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Tell Us Your Goals', 'text' => 'Import your resume or LinkedIn profile for AI analysis.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'AI Finds Matches', 'text' => 'Autonomous agent scans 50+ job boards 24/7.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Auto-Apply', 'text' => 'AI generates tailored applications and submits them.'],
        ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Interview Prep', 'text' => 'Get company-specific coaching and mock interviews.'],
        ['@type' => 'HowToStep', 'position' => 5, 'name' => 'Negotiate & Accept', 'text' => 'Data-backed negotiation strategies maximize your offer.'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
