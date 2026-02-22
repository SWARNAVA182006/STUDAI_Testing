{{--
    StudAI Path — Blog & Resources
    Career Insights, Guides & Trends
--}}
@extends('layouts.app')

@section('title', 'Blog & Resources — StudAI Path | Career Insights & AI Guides')

@section('meta')
<meta name="description" content="Expert career insights, AI job search strategies, and professional development guides. Learn how to automate your career success.">
<meta property="og:title" content="Blog & Resources — StudAI Path">
<meta property="og:description" content="Career advice and AI insights from the StudAI Path team.">
<link rel="canonical" href="{{ route('blog') }}">
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
            <svg class="w-4 h-4 text-google-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Career Intelligence
        </span>
        <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl max-w-4xl mx-auto">
            Ideas to Accelerate Your Career Journey
        </h1>
        <p class="mt-6 text-lg text-slate-200 max-w-2xl mx-auto">
            Expert insights on AI-powered job search, negotiation strategies, market trends, and professional growth.
        </p>

        {{-- Search Bar --}}
        <div class="max-w-xl mx-auto mt-10">
            <div class="relative">
                <input type="text" placeholder="Search articles..." 
                       class="w-full px-6 py-4 pr-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl text-white placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-google-blue-500">
                <button class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>

{{-- Categories --}}
<section class="py-8 bg-canvas-subtle border-b border-surface-200">
    <div class="mx-auto max-w-7xl px-6">
        <div class="flex flex-wrap justify-center gap-3">
            @foreach ([
                ['name' => 'All', 'active' => true],
                ['name' => 'AI & Automation', 'active' => false],
                ['name' => 'Job Search', 'active' => false],
                ['name' => 'Interviews', 'active' => false],
                ['name' => 'Salary & Negotiation', 'active' => false],
                ['name' => 'Career Growth', 'active' => false],
                ['name' => 'For Employers', 'active' => false],
            ] as $cat)
                <button class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $cat['active'] ? 'bg-google-blue-600 text-white' : 'bg-white border border-surface-200 text-ink-secondary hover:bg-surface-50' }}">
                    {{ $cat['name'] }}
                </button>
            @endforeach
        </div>
    </div>
</section>

{{-- Featured Article --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-7xl px-6">
        <div class="rounded-3xl border border-surface-200 bg-gradient-to-br from-google-blue-50 to-purple-50 overflow-hidden">
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="p-10 lg:p-12 flex flex-col justify-center">
                    <span class="inline-flex items-center gap-1 w-fit rounded-full bg-google-blue-100 px-3 py-1 text-xs font-semibold text-google-blue-700 mb-4">
                        ⭐ Featured
                    </span>
                    <h2 class="text-3xl font-bold text-ink-primary mb-4">
                        The Complete Guide to AI-Powered Job Search in 2025
                    </h2>
                    <p class="text-ink-secondary mb-6">
                        Everything you need to know about using AI tools to find, apply, and land your dream job — from autonomous agents to interview prep.
                    </p>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-google-blue-600 text-white flex items-center justify-center text-sm font-semibold">AM</div>
                            <span class="text-sm text-ink-secondary">Arjun Mehta</span>
                        </div>
                        <span class="text-ink-muted">•</span>
                        <span class="text-sm text-ink-muted">15 min read</span>
                        <span class="text-ink-muted">•</span>
                        <span class="text-sm text-ink-muted">Jan 10, 2025</span>
                    </div>
                    <a href="#" class="inline-flex items-center gap-2 studai-btn studai-btn-primary w-fit">
                        Read Article
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
                <div class="bg-gradient-to-br from-google-blue-500 to-purple-600 flex items-center justify-center p-12">
                    <div class="text-center text-white">
                        <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <p class="text-sm opacity-75">Featured Image</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Article Grid --}}
<section class="py-16 bg-canvas-subtle">
    <div class="mx-auto max-w-7xl px-6">
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-2xl font-bold text-ink-primary">Latest Articles</h2>
            <div class="flex items-center gap-2 text-sm text-ink-secondary">
                <span>Sort by:</span>
                <select class="bg-white border border-surface-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-google-blue-500">
                    <option>Most Recent</option>
                    <option>Most Popular</option>
                    <option>Trending</option>
                </select>
            </div>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @php
                $articles = [
                    [
                        'title' => '5 Resume Mistakes AI Can Fix in Seconds',
                        'excerpt' => 'Common formatting and keyword errors that hurt your ATS score — and how to fix them automatically.',
                        'category' => 'Job Search',
                        'author' => 'Priya Sharma',
                        'initials' => 'PS',
                        'time' => '8 min',
                        'date' => 'Jan 8, 2025',
                        'color' => 'google-green',
                    ],
                    [
                        'title' => 'How to Negotiate a 30% Higher Salary',
                        'excerpt' => 'Data-backed strategies and scripts that helped 500+ users increase their offers significantly.',
                        'category' => 'Salary & Negotiation',
                        'author' => 'Rahul Verma',
                        'initials' => 'RV',
                        'time' => '12 min',
                        'date' => 'Jan 5, 2025',
                        'color' => 'purple',
                    ],
                    [
                        'title' => 'The Autonomous Agent: How It Works',
                        'excerpt' => 'A deep dive into the technology behind StudAI Path\'s 24/7 job-finding AI.',
                        'category' => 'AI & Automation',
                        'author' => 'Tech Team',
                        'initials' => 'TT',
                        'time' => '10 min',
                        'date' => 'Jan 3, 2025',
                        'color' => 'google-blue',
                    ],
                    [
                        'title' => 'Mock Interview vs. Real Interview: What\'s Different',
                        'excerpt' => 'Insights from analyzing 10,000 practice sessions and how they translate to real-world success.',
                        'category' => 'Interviews',
                        'author' => 'Sneha Iyer',
                        'initials' => 'SI',
                        'time' => '7 min',
                        'date' => 'Dec 28, 2024',
                        'color' => 'google-yellow',
                    ],
                    [
                        'title' => 'Tech Hiring Trends in 2025',
                        'excerpt' => 'Which skills are in demand, which are declining, and how to position yourself for the year ahead.',
                        'category' => 'Career Growth',
                        'author' => 'Market Research',
                        'initials' => 'MR',
                        'time' => '15 min',
                        'date' => 'Dec 22, 2024',
                        'color' => 'teal',
                    ],
                    [
                        'title' => 'S.C.O.U.T. vs. Traditional ATS: A Comparison',
                        'excerpt' => 'Why bias-free AI screening is the future of hiring and how employers are adopting it.',
                        'category' => 'For Employers',
                        'author' => 'Karan Singh',
                        'initials' => 'KS',
                        'time' => '9 min',
                        'date' => 'Dec 18, 2024',
                        'color' => 'pink',
                    ],
                ];
            @endphp

            @foreach ($articles as $article)
                <article class="bg-white rounded-2xl border border-surface-200 overflow-hidden hover:shadow-card transition-shadow group">
                    <div class="h-40 bg-gradient-to-br from-{{ $article['color'] }}-50 to-{{ $article['color'] }}-100 flex items-center justify-center">
                        <span class="text-{{ $article['color'] }}-300 text-5xl font-bold opacity-50">📄</span>
                    </div>
                    <div class="p-6">
                        <span class="inline-block px-2.5 py-1 bg-{{ $article['color'] }}-50 text-{{ $article['color'] }}-700 text-xs font-semibold rounded-full mb-3">
                            {{ $article['category'] }}
                        </span>
                        <h3 class="text-lg font-semibold text-ink-primary mb-2 group-hover:text-google-blue-600 transition-colors">
                            <a href="#">{{ $article['title'] }}</a>
                        </h3>
                        <p class="text-sm text-ink-secondary mb-4 line-clamp-2">{{ $article['excerpt'] }}</p>
                        <div class="flex items-center justify-between text-xs text-ink-muted">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-surface-200 flex items-center justify-center text-ink-secondary font-medium text-[10px]">
                                    {{ $article['initials'] }}
                                </div>
                                <span>{{ $article['author'] }}</span>
                            </div>
                            <span>{{ $article['time'] }} read</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-center gap-2 mt-12">
            <button class="w-10 h-10 rounded-lg border border-surface-200 bg-white flex items-center justify-center text-ink-muted hover:bg-surface-50 disabled:opacity-50" disabled>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button class="w-10 h-10 rounded-lg bg-google-blue-600 text-white font-semibold">1</button>
            <button class="w-10 h-10 rounded-lg border border-surface-200 bg-white text-ink-secondary hover:bg-surface-50">2</button>
            <button class="w-10 h-10 rounded-lg border border-surface-200 bg-white text-ink-secondary hover:bg-surface-50">3</button>
            <span class="px-2 text-ink-muted">...</span>
            <button class="w-10 h-10 rounded-lg border border-surface-200 bg-white text-ink-secondary hover:bg-surface-50">12</button>
            <button class="w-10 h-10 rounded-lg border border-surface-200 bg-white flex items-center justify-center text-ink-secondary hover:bg-surface-50">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</section>

{{-- Newsletter --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-3xl px-6 text-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-google-blue-50 px-4 py-1 text-xs font-semibold text-google-blue-700 mb-4">
            📬 Newsletter
        </span>
        <h2 class="text-3xl font-bold text-ink-primary mb-4">Get Career Insights Weekly</h2>
        <p class="text-ink-secondary mb-8">
            Join 25,000+ professionals receiving our best articles, salary reports, and job market updates every Friday.
        </p>
        <form class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
            <input type="email" placeholder="your@email.com" 
                   class="flex-1 px-4 py-3 border border-surface-200 rounded-xl focus:ring-2 focus:ring-google-blue-500 focus:border-transparent">
            <button type="submit" class="studai-btn studai-btn-primary studai-btn-lg">
                Subscribe
            </button>
        </form>
        <p class="text-xs text-ink-muted mt-4">No spam. Unsubscribe anytime.</p>
    </div>
</section>

{{-- CTA --}}
<section class="py-24 bg-gradient-to-br from-google-blue-600 to-purple-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
            Ready to put your career on autopilot?
        </h2>
        <p class="text-lg text-white/80 mb-8">
            Stop just reading about career success — start living it.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Free Today
            </a>
            <a href="{{ route('features') }}" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                Explore Features
            </a>
        </div>
    </div>
</section>
@endsection
