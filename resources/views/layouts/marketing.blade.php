<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- DNS Prefetch & Preconnect for Performance --}}
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- SEO Meta Tags --}}
    <title>{{ $title ?? 'StudAI Career - AI-Powered Job Discovery & Career Growth Platform' }}</title>
    <meta name="description" content="{{ $description ?? 'Discover your dream job with AI-powered matching. Smart resume optimization, interview prep, and personalized career guidance. Join thousands of job seekers finding success with StudAI Career.' }}">
    <meta name="keywords" content="job search, AI career platform, resume optimization, interview preparation, job matching, career growth, StudAI Career">
    <meta name="author" content="StudAI Career">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle ?? $title ?? 'StudAI Career - AI-Powered Job Discovery' }}">
    <meta property="og:description" content="{{ $ogDescription ?? $description ?? 'Find your dream job with AI-powered matching and career tools' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-image.jpg') }}">
    <meta property="og:site_name" content="StudAI Career">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $twitterTitle ?? $title ?? 'StudAI Career' }}">
    <meta name="twitter:description" content="{{ $twitterDescription ?? $description ?? 'AI-powered job discovery platform' }}">
    <meta name="twitter:image" content="{{ $twitterImage ?? asset('images/twitter-card.jpg') }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Fonts - Preloaded for Performance --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Additional Styles --}}
    @stack('styles')

    <style>
        :root {
            --primary-color: #1A73E8; /* StudAI Brand Blue */
            --primary-dark: #1557b0;
            --primary-light: #8AB4F8;
            --accent-blue: #4285F4;
            --accent-cyan: #22d3ee;
            --surface-dark: #020617;
            --surface-elevated: rgba(15, 23, 42, 0.85);
        }

        html { scroll-behavior: smooth; }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #020617; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary-color), var(--accent-cyan));
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg, var(--primary-dark), #06b6d4); }

        /* Gradient text */
        .gradient-text {
            background-image: linear-gradient(120deg, #e879f9, #60a5fa, #22d3ee);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Glass panel */
        .glass-panel {
            background: radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(244, 114, 182, 0.18), transparent 55%),
                        rgba(15, 23, 42, 0.82);
            border-radius: 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.4);
            box-shadow:
                0 24px 60px rgba(15, 23, 42, 0.9),
                0 0 0 1px rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        /* Floating gradient orbs */
        .orb {
            position: absolute;
            border-radius: 999px;
            filter: blur(40px);
            opacity: 0.9;
            pointer-events: none;
        }
        .orb-indigo { background: radial-gradient(circle, #4f46e5, transparent 65%); }
        .orb-pink { background: radial-gradient(circle, #ec4899, transparent 65%); }
        .orb-cyan { background: radial-gradient(circle, #22d3ee, transparent 65%); }

        /* Soft border cards */
        .card-soft {
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: radial-gradient(circle at top left, rgba(148, 163, 184, 0.16), transparent 60%),
                        rgba(15, 23, 42, 0.85);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.85),
                0 0 0 1px rgba(30, 64, 175, 0.5);
        }

        /* Subtle animated gradient border */
        .animated-border {
            position: relative;
        }
        .animated-border::before {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: conic-gradient(from 120deg, #4f46e5, #ec4899, #22d3ee, #4f46e5);
            -webkit-mask:
                linear-gradient(#000 0 0) content-box,
                linear-gradient(#000 0 0);
            mask:
                linear-gradient(#000 0 0) content-box,
                linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 200ms ease-out;
        }
        .animated-border:hover::before { opacity: 1; }
    </style>
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-50">
    {{-- Navigation --}}
        <nav x-data="{ mobileMenuOpen: false, scrolled: false }" 
            @scroll.window="scrolled = (window.pageYOffset > 20)"
            :class="scrolled ? 'bg-slate-950/95 backdrop-blur border-b border-slate-800/70 shadow-[0_18px_40px_rgba(15,23,42,0.95)]' : 'bg-gradient-to-b from-slate-950/95 via-slate-950/80 to-transparent'"
            class="fixed w-full top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                {{-- Logo --}}
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2">
                        <div class="relative flex items-center justify-center w-9 h-9 rounded-2xl bg-slate-900/80 shadow-[0_0_0_1px_rgba(148,163,184,0.45)]">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-500 via-fuchsia-500 to-cyan-400 text-[0.6rem] font-semibold tracking-tight text-slate-950">AI</span>
                            <span class="absolute -inset-1 rounded-2xl bg-gradient-to-tr from-indigo-500/40 via-fuchsia-500/20 to-cyan-400/40 blur-lg opacity-60" aria-hidden="true"></span>
                        </div>
                        <span class="text-xl font-semibold tracking-tight gradient-text">StudAI Career</span>
                    </a>
                </div>

                {{-- Desktop Navigation --}}
                <div class="hidden md:flex items-center space-x-6 text-sm">
                    <a href="{{ route('home') }}" class="text-slate-200/80 hover:text-white font-medium transition">Home</a>
                    <a href="{{ route('features') }}" class="text-slate-200/80 hover:text-white font-medium transition">Features</a>
                    <a href="{{ route('pricing') }}" class="text-slate-200/80 hover:text-white font-medium transition">Pricing</a>
                    <a href="{{ route('about') }}" class="text-slate-200/80 hover:text-white font-medium transition">About</a>
                    <a href="{{ route('contact') }}" class="text-slate-200/80 hover:text-white font-medium transition">Contact</a>
                    
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-slate-200/80 hover:text-white font-medium transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-slate-200/80 hover:text-white font-medium transition">Login</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-cyan-400 px-4 py-1.5 text-xs font-semibold text-slate-950 shadow-[0_12px_30px_rgba(30,64,175,0.75)] hover:brightness-110 hover:-translate-y-0.5 transition">
                            <span>Get started free</span>
                        </a>
                    @endauth
                </div>

                {{-- Mobile Menu Button --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-300 hover:bg-slate-800/60 hover:text-white transition">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="md:hidden bg-slate-950/95 backdrop-blur border-t border-slate-800/70 shadow-[0_18px_40px_rgba(15,23,42,0.95)]">
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a href="{{ route('home') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">Home</a>
                <a href="{{ route('features') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">Features</a>
                <a href="{{ route('pricing') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">Pricing</a>
                <a href="{{ route('about') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">About</a>
                <a href="{{ route('contact') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">Contact</a>

                @auth
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block px-4 py-2.5 text-slate-200/80 hover:bg-slate-800/60 hover:text-white rounded-lg transition text-sm font-medium">Login</a>
                    <a href="{{ route('register') }}" class="block px-4 py-2.5 bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-cyan-400 text-slate-950 text-center rounded-lg hover:brightness-110 transition text-sm font-semibold mt-2">
                        Get Started
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="pt-16">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                {{-- Brand --}}
                <div class="col-span-1">
                    <div class="flex items-center space-x-2 mb-4">
                        <svg class="w-8 h-8 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        <span class="text-xl font-bold text-white">StudAI Career</span>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">
                        AI-powered job discovery and career advancement platform helping you find your dream job.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-pink-500 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-pink-500 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-pink-500 transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('features') }}" class="hover:text-pink-500 transition">Features</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-pink-500 transition">Pricing</a></li>
                        <li><a href="{{ route('about') }}" class="hover:text-pink-500 transition">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-pink-500 transition">Contact</a></li>
                    </ul>
                </div>

                {{-- For Job Seekers --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">For Job Seekers</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-pink-500 transition">Browse Jobs</a></li>
                        <li><a href="#" class="hover:text-pink-500 transition">Career Advice</a></li>
                        <li><a href="#" class="hover:text-pink-500 transition">Resume Builder</a></li>
                        <li><a href="#" class="hover:text-pink-500 transition">Interview Prep</a></li>
                    </ul>
                </div>

                {{-- For Employers --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">For Employers</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-pink-500 transition">Post a Job</a></li>
                        <li><a href="#" class="hover:text-pink-500 transition">Find Talent</a></li>
                        <li><a href="#" class="hover:text-pink-500 transition">ATS Solution</a></li>
                        <li><a href="#" class="hover:text-pink-500 transition">Pricing for Employers</a></li>
                    </ul>
                </div>
            </div>

            {{-- Bottom Bar --}}
            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-400">
                    &copy; {{ date('Y') }} StudAI Career. All rights reserved.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-sm text-gray-400 hover:text-pink-500 transition">Privacy Policy</a>
                    <a href="#" class="text-sm text-gray-400 hover:text-pink-500 transition">Terms of Service</a>
                    <a href="#" class="text-sm text-gray-400 hover:text-pink-500 transition">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Cookie Consent Banner --}}
    @include('components.cookie-consent')

    {{-- Live Chat Widget Placeholder --}}
    <div x-data="{ chatOpen: false }" class="fixed bottom-6 right-6 z-40">
        <button @click="chatOpen = !chatOpen" 
                class="bg-pink-600 hover:bg-pink-700 text-white rounded-full p-4 shadow-lg transition transform hover:scale-110">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </button>

        <div x-show="chatOpen" 
             x-transition
             class="absolute bottom-20 right-0 w-80 bg-white rounded-lg shadow-xl border border-gray-200">
            <div class="bg-pink-600 text-white p-4 rounded-t-lg flex justify-between items-center">
                <h3 class="font-semibold">Chat with us</h3>
                <button @click="chatOpen = false" class="hover:bg-pink-700 rounded p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 h-64 flex items-center justify-center text-gray-500">
                <p class="text-sm text-center">
                    Live chat integration will be added here.<br>
                    <a href="mailto:support@studaicareer.com" class="text-pink-600 hover:underline">Email us instead</a>
                </p>
            </div>
        </div>
    </div>

    {{-- Additional Scripts --}}
    @stack('scripts')

    {{-- Analytics Placeholder --}}
    {{-- Google Analytics, Facebook Pixel, etc. will be added here --}}
</body>
</html>
