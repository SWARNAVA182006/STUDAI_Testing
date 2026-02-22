@extends('layouts.app')

@section('title', 'AI-Powered Career Platform | StudAI Career - Your Intelligent Job Search Partner')

@section('meta')
<meta name="description" content="Transform your career with StudAI Career's AI-powered job matching, resume optimization, and talent pipeline management. Connect with top employers and discover opportunities tailored to your skills.">
<meta name="keywords" content="AI career platform, job search, resume builder, talent matching, career advancement, job board India, AI recruitment">
<meta property="og:title" content="StudAI Career - AI-Powered Career Transformation">
<meta property="og:description" content="Discover your dream job with intelligent matching, automated applications, and personalized career guidance powered by advanced AI.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="{{ url('/') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-purple-900 via-pink-800 to-blue-900">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 -left-4 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 -right-4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left Content -->
            <div class="text-white space-y-8">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-2 rounded-full border border-white/20">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-sm font-medium">50,000+ Jobs | 10,000+ Companies | AI-Powered Matching</span>
                </div>

                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight">
                    Your Career,
                    <span class="bg-gradient-to-r from-pink-400 via-purple-400 to-blue-400 bg-clip-text text-transparent">
                        Supercharged
                    </span>
                    by AI
                </h1>

                <p class="text-xl md:text-2xl text-gray-200 leading-relaxed">
                    Transform your job search with intelligent matching, automated applications, and personalized career guidance. 
                    Let AI handle the heavy lifting while you focus on landing your dream role.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    @guest
                        <a href="{{ route('register') }}" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-xl shadow-2xl hover:shadow-pink-500/50 transition-all duration-300 transform hover:scale-105">
                            <span>Get Started Free</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                        <a href="{{ route('how-it-works') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-white/10 backdrop-blur-md rounded-xl border-2 border-white/20 hover:bg-white/20 transition-all duration-300">
                            See How It Works
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-xl shadow-2xl hover:shadow-pink-500/50 transition-all duration-300 transform hover:scale-105">
                            <span>Go to Dashboard</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    @endguest
                </div>

                <!-- Trust Badges -->
                <div class="flex flex-wrap items-center gap-8 pt-8 border-t border-white/20">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-sm font-medium">4.9/5 Rating</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium">ISO 27001 Certified</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="text-sm font-medium">GDPR Compliant</span>
                    </div>
                </div>
            </div>

            <!-- Right Visual -->
            <div class="hidden lg:block relative">
                <div class="relative w-full h-[600px]">
                    <!-- Floating Cards Animation -->
                    <div class="absolute top-0 left-0 w-80 bg-white rounded-2xl shadow-2xl p-6 transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-600"></div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Senior Developer</h4>
                                <p class="text-sm text-gray-500">TechCorp Inc.</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Match Score</span>
                                <span class="font-semibold text-emerald-600">94%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-2 rounded-full" style="width: 94%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute top-32 right-0 w-72 bg-white rounded-2xl shadow-2xl p-6 transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                        <div class="flex items-center gap-3 mb-4">
                            <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900">Application Sent</h4>
                                <p class="text-sm text-gray-500">2 minutes ago</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">Your AI-optimized resume has been submitted to 5 matching positions.</p>
                    </div>

                    <div class="absolute bottom-0 left-12 w-64 bg-white rounded-2xl shadow-2xl p-6 transform rotate-2 hover:rotate-0 transition-transform duration-300">
                        <h4 class="font-semibold text-gray-900 mb-3">Skills Analysis</h4>
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600">React.js</span>
                                    <span class="text-gray-900 font-medium">Expert</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-1.5 rounded-full" style="width: 95%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600">Node.js</span>
                                    <span class="text-gray-900 font-medium">Advanced</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-1.5 rounded-full" style="width: 85%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2">
        <div class="animate-bounce">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent mb-2">
                    50K+
                </div>
                <div class="text-gray-600 font-medium">Active Job Listings</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent mb-2">
                    10K+
                </div>
                <div class="text-gray-600 font-medium">Hiring Companies</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-600 to-emerald-600 bg-clip-text text-transparent mb-2">
                    95%
                </div>
                <div class="text-gray-600 font-medium">Match Accuracy</div>
            </div>
            <div class="text-center">
                <div class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-emerald-600 to-pink-600 bg-clip-text text-transparent mb-2">
                    2.5M+
                </div>
                <div class="text-gray-600 font-medium">Successful Placements</div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-24 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Powered by <span class="bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">Advanced AI</span>
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Experience the future of career management with our intelligent platform designed to accelerate your professional growth.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-pink-200">
                <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">AI Job Matching</h3>
                <p class="text-gray-600 leading-relaxed">
                    Our proprietary algorithm analyzes your skills, experience, and preferences to match you with opportunities where you'll thrive. 95% accuracy rate.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-purple-200">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Smart Resume Builder</h3>
                <p class="text-gray-600 leading-relaxed">
                    AI-powered resume optimization that beats ATS systems. Automatic keyword insertion, formatting, and tailoring for each application.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-blue-200">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Automated Applications</h3>
                <p class="text-gray-600 leading-relaxed">
                    Set it and forget it. Our AI agent applies to relevant positions on your behalf, tracks responses, and schedules interviews automatically.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-emerald-200">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Skill Gap Analysis</h3>
                <p class="text-gray-600 leading-relaxed">
                    Identify missing skills for your dream role. Get personalized learning paths with curated courses, certifications, and practice projects.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-orange-200">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Interview Prep AI</h3>
                <p class="text-gray-600 leading-relaxed">
                    Practice with our AI interviewer. Get real-time feedback on your answers, body language, and communication style. Company-specific prep included.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-indigo-200">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Career Trajectory</h3>
                <p class="text-gray-600 leading-relaxed">
                    Visualize your career path with AI predictions. See salary growth, skill development timelines, and optimal career moves based on market data.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- For Employers Section -->
<section class="py-24 bg-gradient-to-br from-purple-900 via-pink-900 to-blue-900 text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="inline-block bg-white/10 backdrop-blur-md px-4 py-2 rounded-full mb-6">
                    <span class="text-sm font-medium">For Employers</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    Build Your <span class="text-pink-400">Dream Team</span> with AI Precision
                </h2>
                <p class="text-xl text-gray-200 mb-8 leading-relaxed">
                    Stop sifting through hundreds of irrelevant resumes. Our AI-powered talent pipeline identifies, engages, and nurtures top candidates who perfectly match your company DNA.
                </p>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-lg">AI-powered candidate matching reduces time-to-hire by 60%</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-lg">Automated screening and bias detection for fair hiring</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-lg">Predictive performance analytics for better hiring decisions</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-lg">Talent pipeline management with silver medalist tracking</span>
                    </li>
                </ul>
                <a href="{{ route('register', ['account_type' => 'employer']) }}" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-purple-900 font-semibold rounded-xl hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-xl">
                    <span>Start Hiring Smarter</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <div class="hidden lg:block">
                <div class="relative">
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 border border-white/20">
                        <h4 class="text-2xl font-bold mb-6">Hiring Dashboard</h4>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Active Positions</span>
                                <span class="text-3xl font-bold">24</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Qualified Candidates</span>
                                <span class="text-3xl font-bold text-emerald-400">156</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Avg. Match Score</span>
                                <span class="text-3xl font-bold text-pink-400">92%</span>
                            </div>
                            <div class="pt-4 border-t border-white/20">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-300">Time to Hire</span>
                                    <span class="text-sm font-semibold text-emerald-400">↓ 60% faster</span>
                                </div>
                                <div class="w-full bg-white/20 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-2 rounded-full" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Trusted by <span class="bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">Thousands</span>
            </h2>
            <p class="text-xl text-gray-600">Real stories from real people who transformed their careers</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-gray-700 mb-6 leading-relaxed">
                    "StudAI Career found me the perfect role in just 2 weeks. The AI matching was spot-on, and the automated applications saved me countless hours. Best career decision I ever made!"
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-400 to-purple-600"></div>
                    <div>
                        <div class="font-semibold text-gray-900">Priya Sharma</div>
                        <div class="text-sm text-gray-500">Senior Software Engineer</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-gray-700 mb-6 leading-relaxed">
                    "As an employer, this platform revolutionized our hiring process. We've reduced time-to-hire by 65% and the quality of candidates has never been better. The AI really works!"
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-600"></div>
                    <div>
                        <div class="font-semibold text-gray-900">Rahul Mehta</div>
                        <div class="text-sm text-gray-500">HR Director, TechVentures</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg">
                <div class="flex items-center gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                <p class="text-gray-700 mb-6 leading-relaxed">
                    "The career trajectory feature helped me plan my next 5 years. I got a 40% salary increase by following the AI's recommendations for skill development. Absolutely worth it!"
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600"></div>
                    <div>
                        <div class="font-semibold text-gray-900">Anjali Reddy</div>
                        <div class="text-sm text-gray-500">Product Manager</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 bg-gradient-to-r from-pink-600 via-purple-600 to-blue-600 text-white relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full mix-blend-overlay filter blur-3xl animate-blob"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full mix-blend-overlay filter blur-3xl animate-blob animation-delay-2000"></div>
    </div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">
            Ready to Transform Your Career?
        </h2>
        <p class="text-xl mb-10 text-gray-100">
            Join thousands of professionals who've already accelerated their careers with AI-powered job matching and career guidance.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            @guest
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold bg-white text-purple-600 rounded-xl shadow-2xl hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">
                    <span>Start Free Trial</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold bg-white/10 backdrop-blur-md border-2 border-white rounded-xl hover:bg-white/20 transition-all duration-300">
                    Talk to Sales
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold bg-white text-purple-600 rounded-xl shadow-2xl hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">
                    <span>Go to Dashboard</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            @endguest
        </div>
        <p class="mt-6 text-sm text-gray-200">
            No credit card required • Free 14-day trial • Cancel anytime
        </p>
    </div>
</section>

{{-- 
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(20px, -50px) scale(1.1); }
    50% { transform: translate(-20px, 20px) scale(0.9); }
    75% { transform: translate(50px, 50px) scale(1.05); }
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
--}}
@endsection
