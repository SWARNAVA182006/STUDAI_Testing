@extends('layouts.app')

@section('title', 'Features - StudAI Career | AI-Powered Job Search & Hiring Platform')

@section('meta')
<meta name="description" content="Explore StudAI Career's complete feature suite: AI job matching, resume optimization, automated applications, interview prep, salary intelligence, and hiring automation for employers.">
<meta name="keywords" content="AI job search features, resume optimization, automated applications, job matching, interview preparation, hiring platform, ATS, career analytics">
<meta property="og:title" content="Features - StudAI Career">
<meta property="og:description" content="Comprehensive AI-powered features for job seekers and employers. Smart matching, automation, and analytics to accelerate careers and hiring.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('features') }}">
<link rel="canonical" href="{{ route('features') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 -left-4 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 -right-4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center text-white space-y-8">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="text-sm font-medium">50+ AI-Powered Features</span>
            </div>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold leading-tight">
                Every Feature You Need to
                <span class="bg-gradient-to-r from-pink-400 via-purple-400 to-blue-400 bg-clip-text text-transparent">
                    Accelerate Careers
                </span>
            </h1>

            <p class="text-xl md:text-2xl text-gray-200 leading-relaxed max-w-4xl mx-auto">
                From intelligent job matching to automated applications, interview preparation to salary negotiation - 
                discover the comprehensive AI platform that transforms how talent connects with opportunity.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('register') }}" class="group inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-xl shadow-2xl hover:shadow-pink-500/50 transition-all duration-300 transform hover:scale-105">
                        <span>Start Free Trial</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="group inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-xl shadow-2xl hover:shadow-pink-500/50 transition-all duration-300 transform hover:scale-105">
                        <span>Go to Dashboard</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                @endguest
                <a href="{{ route('how-it-works') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-white/10 backdrop-blur-md rounded-xl border-2 border-white/20 hover:bg-white/20 transition-all duration-300">
                    See How It Works
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Job Seeker Features -->
<section class="py-24 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                For Job Seekers
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Comprehensive AI-powered tools to accelerate your job search and career advancement
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ([
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'title' => 'AI Job Matching',
                    'description' => 'Intelligent algorithm matches you with perfect opportunities based on skills, experience, and preferences.',
                    'features' => ['Semantic job search', '90%+ match accuracy', 'Culture fit predictions', 'Salary compatibility']
                ],
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'title' => 'Resume Optimization',
                    'description' => 'AI-powered resume enhancement with ATS optimization and keyword targeting for each application.',
                    'features' => ['ATS compatibility scoring', 'Dynamic keyword optimization', 'Impact statement generation', 'Multiple format export']
                ],
                [
                    'icon' => 'M8 7V3a4 4 0 118 0v4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1m-2-4v10m0 0l-2-2m2 2l2-2',
                    'title' => 'Automated Applications',
                    'description' => 'Let AI handle job applications with personalized cover letters and automated follow-ups.',
                    'features' => ['One-click applications', 'Custom cover letters', 'Application tracking', 'Automated follow-ups']
                ],
                [
                    'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                    'title' => 'Interview Preparation',
                    'description' => 'AI-powered interview coaching with practice sessions, question banks, and performance feedback.',
                    'features' => ['Mock interview sessions', 'Industry-specific questions', 'Real-time feedback', 'Body language analysis']
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
                    'title' => 'Salary Intelligence',
                    'description' => 'Market-based salary insights and negotiation strategies tailored to your location and experience.',
                    'features' => ['Real-time salary data', 'Negotiation coaching', 'Market positioning', 'Compensation benchmarking']
                ],
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 00-2 2z',
                    'title' => 'Career Analytics',
                    'description' => 'Comprehensive insights into your job search performance and career progression opportunities.',
                    'features' => ['Application success rates', 'Skill gap analysis', 'Market trend insights', 'Career path recommendations']
                ]
            ] as $feature)
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 hover:border-pink-500/30 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500/20 to-purple-500/20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ $feature['title'] }}</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">{{ $feature['description'] }}</p>
                    <ul class="space-y-2">
                        @foreach($feature['features'] as $item)
                            <li class="flex items-center gap-2 text-sm text-gray-400">
                                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Employer Features -->
<section class="py-24 bg-gradient-to-b from-slate-950 to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                For Employers
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Advanced hiring tools to find, evaluate, and onboard top talent efficiently
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ([
                [
                    'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'title' => 'Intelligent Candidate Sourcing',
                    'description' => 'AI-powered talent discovery that goes beyond keywords to find candidates with the right potential.',
                    'features' => ['Semantic candidate matching', 'Passive talent identification', 'Diversity sourcing', 'Skill-based recommendations']
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m0-8h10a2 2 0 012 2v6a2 2 0 01-2 2h-2m-6-8h6m-6 4h6',
                    'title' => 'Applicant Tracking System',
                    'description' => 'Comprehensive ATS with collaborative hiring workflows, automated screening, and performance analytics.',
                    'features' => ['Kanban pipeline management', 'Collaborative evaluation', 'Automated workflows', 'Integration capabilities']
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => 'AI-Powered Screening',
                    'description' => 'Automated candidate evaluation with bias-free assessments and comprehensive skill verification.',
                    'features' => ['Resume scoring automation', 'Skill assessments', 'Video interview analysis', 'Bias detection']
                ],
                [
                    'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'title' => 'Hiring Analytics',
                    'description' => 'Data-driven insights to optimize your recruitment process and improve hiring outcomes.',
                    'features' => ['Time-to-hire tracking', 'Source effectiveness', 'Quality of hire metrics', 'Cost per hire analysis']
                ],
                [
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'title' => 'Team Collaboration',
                    'description' => 'Seamless collaboration tools for hiring teams with role-based access and decision tracking.',
                    'features' => ['Multi-reviewer workflows', 'Interview scheduling', 'Decision tracking', 'Feedback aggregation']
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'title' => 'Compliance & Security',
                    'description' => 'Built-in compliance tools and security features to meet regulatory requirements.',
                    'features' => ['GDPR compliance', 'Data encryption', 'Audit trails', 'Access controls']
                ]
            ] as $feature)
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 hover:border-purple-500/30 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500/20 to-blue-500/20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ $feature['title'] }}</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">{{ $feature['description'] }}</p>
                    <ul class="space-y-2">
                        @foreach($feature['features'] as $item)
                            <li class="flex items-center gap-2 text-sm text-gray-400">
                                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- AI Technology Deep Dive -->
<section class="py-24 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                Powered by Advanced AI
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Our cutting-edge artificial intelligence technology drives every feature to deliver unprecedented accuracy and efficiency
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                @foreach ([
                    [
                        'title' => 'Natural Language Processing',
                        'description' => 'Advanced NLP algorithms understand job descriptions, resumes, and candidate profiles with human-like comprehension.',
                        'metrics' => '95% accuracy in skill extraction'
                    ],
                    [
                        'title' => 'Machine Learning Models',
                        'description' => 'Continuously learning algorithms improve matching precision and prediction accuracy with every interaction.',
                        'metrics' => '40% improvement in match quality over time'
                    ],
                    [
                        'title' => 'Semantic Search Technology',
                        'description' => 'Goes beyond keyword matching to understand context, intent, and meaning for better job-candidate alignment.',
                        'metrics' => '3x more relevant matches than traditional systems'
                    ]
                ] as $tech)
                    <div class="flex gap-6">
                        <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-emerald-500/20 to-teal-500/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-white mb-3">{{ $tech['title'] }}</h3>
                            <p class="text-gray-300 mb-2 leading-relaxed">{{ $tech['description'] }}</p>
                            <p class="text-sm text-emerald-400 font-semibold">{{ $tech['metrics'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="relative">
                <div class="aspect-square bg-gradient-to-br from-pink-500/10 to-purple-500/10 rounded-3xl border border-pink-500/20 p-8 backdrop-blur-md">
                    <div class="w-full h-full bg-slate-900/30 rounded-2xl flex items-center justify-center">
                        <div class="text-center text-white">
                            <svg class="w-32 h-32 mx-auto mb-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <h4 class="text-2xl font-bold mb-2">AI-Powered Intelligence</h4>
                            <p class="text-gray-300">Processing 10M+ data points daily</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Application Tools Section --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Complete Application Toolkit
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    From application to offer, we've got you covered
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition" data-aos="fade-up">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Cover Letter Generator</h3>
                    <p class="text-gray-600 mb-4">
                        AI-powered personalized cover letters that highlight your unique value proposition for each role.
                    </p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• Multiple tone options</li>
                        <li>• Company research integration</li>
                        <li>• Instant customization</li>
                    </ul>
                </div>

                <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Interview Preparation</h3>
                    <p class="text-gray-600 mb-4">
                        Practice with AI-powered mock interviews and get real-time feedback on your answers.
                    </p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• Role-specific questions</li>
                        <li>• STAR method guidance</li>
                        <li>• Video recording & analysis</li>
                    </ul>
                </div>

                <div class="bg-gray-50 rounded-2xl p-8 hover:shadow-lg transition" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Application Tracking</h3>
                    <p class="text-gray-600 mb-4">
                        Track all your applications, follow-ups, and interviews in one organized dashboard.
                    </p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li>• Pipeline visualization</li>
                        <li>• Auto follow-up reminders</li>
                        <li>• Success rate analytics</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Integration & Enterprise -->
<section class="py-24 bg-gradient-to-b from-slate-900 to-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                Enterprise-Ready Integrations
            </h2>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Seamlessly connect with your existing HR tech stack and business tools
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8 mb-16">
            @foreach(['LinkedIn', 'Slack', 'Teams', 'BambooHR', 'Workday', 'Greenhouse'] as $integration)
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl p-6 text-center border border-slate-700 hover:border-pink-500/30 transition-all duration-300">
                    <div class="text-lg font-bold text-white mb-1">{{ $integration }}</div>
                    <div class="text-xs text-gray-400 uppercase tracking-wide">Integration</div>
                </div>
            @endforeach
        </div>

        <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 md:p-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-3xl font-bold text-white mb-6">Developer-Friendly APIs</h3>
                    <p class="text-lg text-gray-300 mb-8 leading-relaxed">
                        Build custom integrations and workflows with our comprehensive REST APIs, webhooks, and SDKs. 
                        Complete documentation with interactive examples available.
                    </p>
                    <ul class="space-y-4">
                        @foreach([
                            'RESTful APIs with comprehensive documentation',
                            'Real-time webhooks for instant notifications',
                            'SDKs for Python, Node.js, PHP, and more',
                            '99.9% uptime SLA with global CDN'
                        ] as $feature)
                            <li class="flex items-center gap-3 text-gray-300">
                                <svg class="w-6 h-6 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="#" class="inline-flex items-center gap-2 mt-8 bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:shadow-lg transition-all duration-300">
                        View API Documentation
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
                <div class="bg-slate-900 rounded-2xl p-6 border border-slate-700">
                    <div class="text-gray-400 text-sm mb-4">// Sample API Integration</div>
                    <div class="font-mono text-sm space-y-1">
                        <div><span class="text-blue-400">const</span> <span class="text-white">response</span> = <span class="text-blue-400">await</span> <span class="text-yellow-400">fetch</span><span class="text-gray-400">(</span></div>
                        <div class="ml-4"><span class="text-green-400">'https://api.studai.careers/v1/jobs/search'</span>,</div>
                        <div class="ml-4"><span class="text-gray-400">{</span></div>
                        <div class="ml-8"><span class="text-purple-400">method:</span> <span class="text-green-400">'POST'</span>,</div>
                        <div class="ml-8"><span class="text-purple-400">headers:</span> <span class="text-gray-400">{</span></div>
                        <div class="ml-12"><span class="text-green-400">'Authorization'</span>: <span class="text-green-400">'Bearer ${token}'</span>,</div>
                        <div class="ml-12"><span class="text-green-400">'Content-Type'</span>: <span class="text-green-400">'application/json'</span></div>
                        <div class="ml-8"><span class="text-gray-400">},</span></div>
                        <div class="ml-8"><span class="text-purple-400">body:</span> <span class="text-yellow-400">JSON.stringify</span><span class="text-gray-400">({</span></div>
                        <div class="ml-12"><span class="text-purple-400">skills:</span> <span class="text-gray-400">[</span><span class="text-green-400">'React'</span>, <span class="text-green-400">'Node.js'</span><span class="text-gray-400">],</span></div>
                        <div class="ml-12"><span class="text-purple-400">experience:</span> <span class="text-orange-400">3</span>,</div>
                        <div class="ml-12"><span class="text-purple-400">location:</span> <span class="text-green-400">'Remote'</span></div>
                        <div class="ml-8"><span class="text-gray-400">})</span></div>
                        <div class="ml-4"><span class="text-gray-400">}</span></div>
                        <div><span class="text-gray-400">);</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 bg-gradient-to-br from-pink-900 via-purple-900 to-blue-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
            Ready to Experience the Future of Careers?
        </h2>
        <p class="text-xl text-gray-200 mb-8 leading-relaxed">
            Join thousands of professionals and companies transforming their career journeys with AI-powered intelligence.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            @guest
                <a href="{{ route('register') }}" class="group inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-white/20 backdrop-blur-md rounded-xl border-2 border-white/30 hover:bg-white/30 transition-all duration-300">
                    Start Free Trial
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-pink-100 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 hover:bg-white/20 transition-all duration-300">
                    Schedule Demo
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="group inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-white/20 backdrop-blur-md rounded-xl border-2 border-white/30 hover:bg-white/30 transition-all duration-300">
                    Go to Dashboard
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            @endguest
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => 'StudAI Career',
    'applicationCategory' => 'BusinessApplication',
    'description' => 'AI-powered career platform for job search, hiring, and talent management',
    'url' => url('/'),
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'INR',
        'name' => 'Free Trial'
    ],
    'featureList' => [
        'AI Job Matching',
        'Resume Optimization',
        'Automated Applications',
        'Interview Preparation',
        'Salary Intelligence',
        'Applicant Tracking System',
        'Hiring Analytics'
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@push('styles')
<style>
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
@endpush
