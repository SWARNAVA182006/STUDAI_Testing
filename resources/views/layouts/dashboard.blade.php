{{--
    StudAI Career - Dashboard Layout
    Google-inspired sidebar navigation with clean content area
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'StudAI Career') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Favicon & PWA -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.svg">
    <meta name="theme-color" content="#1A73E8">
    @stack('styles')
</head>
<body class="font-sans antialiased bg-canvas-subtle" x-data="{ sidebarOpen: true, sidebarMobileOpen: false }">
    <div class="min-h-screen flex">
        {{-- ============================================
            SIDEBAR NAVIGATION
        ============================================ --}}
        <aside 
            class="fixed inset-y-0 left-0 z-sticky bg-white border-r border-surface-200 transition-all duration-300 flex flex-col"
            :class="sidebarOpen ? 'w-[280px]' : 'w-[72px]'"
        >
            {{-- Sidebar Header --}}
            <div class="h-16 px-4 flex items-center gap-3 border-b border-surface-100">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-google-blue-600 to-purple-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span x-show="sidebarOpen" x-transition class="text-lg font-semibold text-ink-primary">StudAI<span class="text-google-blue-600">Career</span></span>
                </a>
            </div>

            {{-- Navigation Content --}}
            <nav class="flex-1 overflow-y-auto py-4 scrollbar-thin">
                @if(auth()->user()?->isEmployer())
                {{-- ================ EMPLOYER NAVIGATION ================ --}}
                {{-- Main Navigation --}}
                <div class="px-3 mb-6">
                    <div x-show="sidebarOpen" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-tertiary">Main</div>
                    
                    <a href="{{ route('employer.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.dashboard') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Dashboard</span>
                    </a>

                    <a href="{{ route('employer.jobs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.jobs.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Job Postings</span>
                    </a>

                    <a href="{{ route('employer.applicants.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.applicants.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Applicants</span>
                    </a>
                </div>

                {{-- S.C.O.U.T. AI Tools --}}
                <div class="px-3 mb-6">
                    <div x-show="sidebarOpen" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-tertiary">S.C.O.U.T. AI</div>
                    
                    <a href="{{ route('employer.scout.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.scout.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>DNA Dashboard</span>
                        <span x-show="sidebarOpen" class="ml-auto px-1.5 py-0.5 text-[10px] font-semibold bg-google-green-100 text-google-green-700 rounded-full">AI</span>
                    </a>

                    <a href="{{ route('employer.scout.shortlisting') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.scout.shortlisting') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Auto Shortlisting</span>
                    </a>

                    <a href="{{ route('employer.scout.candidate-matching') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.scout.candidate-matching') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Candidate Match</span>
                    </a>

                    <a href="{{ route('employer.scout.predictive') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.scout.predictive') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Predictive Analytics</span>
                    </a>
                </div>

                {{-- Employer Extras --}}
                <div class="px-3">
                    <div x-show="sidebarOpen" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-tertiary">More</div>
                    
                    <a href="{{ route('employer.interviews.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.interviews.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Interviews</span>
                    </a>

                    <a href="{{ route('employer.profile.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.profile.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Company Profile</span>
                    </a>

                    <a href="{{ route('employer.analytics') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('employer.analytics') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Analytics</span>
                    </a>
                </div>

                @else
                {{-- ================ JOB SEEKER NAVIGATION ================ --}}
                {{-- Main Navigation --}}
                <div class="px-3 mb-6">
                    <div x-show="sidebarOpen" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-tertiary">Main</div>
                    
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Dashboard</span>
                    </a>

                    <a href="{{ route('jobs.search') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('jobs.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Job Search</span>
                    </a>

                    <a href="{{ route('agent.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('agent.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>AI Agent</span>
                        <span x-show="sidebarOpen" class="ml-auto px-1.5 py-0.5 text-[10px] font-semibold bg-google-green-100 text-google-green-700 rounded-full">Active</span>
                    </a>
                </div>

                {{-- Career Tools --}}
                <div class="px-3 mb-6">
                    <div x-show="sidebarOpen" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-tertiary">Career Tools</div>
                    
                    <a href="{{ route('resume.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('resume.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Resume Builder</span>
                    </a>

                    <a href="{{ route('interview.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('interview.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Interview Lab</span>
                    </a>

                    <a href="{{ route('market.overview') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('market.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Market Intel</span>
                    </a>

                    <a href="{{ route('career-coach.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('career-coach.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Career Coach</span>
                    </a>
                </div>

                {{-- Extras --}}
                <div class="px-3">
                    <div x-show="sidebarOpen" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-ink-tertiary">More</div>
                    
                    <a href="{{ route('marketplace.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('marketplace.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Marketplace</span>
                    </a>

                    <a href="{{ route('gamification.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('gamification.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Achievements</span>
                    </a>

                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ request()->routeIs('profile.*') ? 'bg-google-blue-50 text-google-blue-600' : 'text-ink-secondary hover:bg-surface-100 hover:text-ink-primary' }}" :class="!sidebarOpen && 'justify-center'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition>Settings</span>
                    </a>
                </div>
                @endif
            </nav>

            {{-- Sidebar Footer - User Profile --}}
            <div class="p-4 border-t border-surface-100">
                <div class="flex items-center gap-3" :class="!sidebarOpen && 'justify-center'">
                    <x-studai.avatar 
                        :src="auth()->user()->profile_photo_url ?? null" 
                        :name="auth()->user()->name ?? 'User'" 
                        size="sm"
                        status="online"
                    />
                    <div x-show="sidebarOpen" x-transition class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-ink-primary truncate">{{ auth()->user()->name ?? 'Guest User' }}</div>
                        <div class="text-xs text-ink-tertiary truncate">{{ auth()->user()->email ?? '' }}</div>
                    </div>
                    <button x-show="sidebarOpen" class="p-1.5 rounded-lg hover:bg-surface-100 text-ink-tertiary transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                    </button>
                </div>

                {{-- Collapse Toggle --}}
                <button 
                    @click="sidebarOpen = !sidebarOpen"
                    class="mt-4 w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-ink-tertiary hover:bg-surface-100 hover:text-ink-secondary transition-all duration-150"
                >
                    <svg class="w-4 h-4 transition-transform" :class="!sidebarOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <span x-show="sidebarOpen" x-transition>Collapse</span>
                </button>
            </div>
        </aside>

        {{-- ============================================
            MAIN CONTENT AREA
        ============================================ --}}
        <div class="flex-1 transition-all duration-300" :class="sidebarOpen ? 'ml-[280px]' : 'ml-[72px]'">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-sticky h-16 bg-white/80 backdrop-blur-xl border-b border-surface-200">
                <div class="h-full px-6 flex items-center justify-between">
                    {{-- Left: Page Title / Breadcrumb --}}
                    <div class="flex items-center gap-4">
                        @if(isset($breadcrumb))
                            {{ $breadcrumb }}
                        @else
                            <h1 class="text-lg font-semibold text-ink-primary">{{ $title ?? 'Dashboard' }}</h1>
                        @endif
                    </div>

                    {{-- Center: Search (optional) --}}
                    @if(isset($showSearch) && $showSearch)
                    <div class="hidden md:block flex-1 max-w-xl mx-8">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input 
                                type="search" 
                                placeholder="Search..." 
                                class="w-full pl-10 pr-4 py-2 text-sm bg-surface-50 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-google-blue-100 transition-all"
                            >
                        </div>
                    </div>
                    @endif

                    {{-- Right: Actions --}}
                    <div class="flex items-center gap-3">
                        {{-- AI Status --}}
                        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-google-green-50 rounded-full">
                            <span class="flex h-2 w-2 rounded-full bg-google-green-500 animate-pulse"></span>
                            <span class="text-xs font-medium text-google-green-700">AI Active</span>
                        </div>

                        {{-- Notifications --}}
                        <button class="relative p-2 rounded-lg hover:bg-surface-100 text-ink-secondary transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-google-red-500 rounded-full"></span>
                        </button>

                        {{-- Messages --}}
                        <button class="p-2 rounded-lg hover:bg-surface-100 text-ink-secondary transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </button>

                        {{-- Help --}}
                        <button class="p-2 rounded-lg hover:bg-surface-100 text-ink-secondary transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>

                        {{-- Profile Dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-2 p-1 rounded-lg hover:bg-surface-100 transition-colors">
                                <x-studai.avatar 
                                    :src="auth()->user()->profile_photo_url ?? null" 
                                    :name="auth()->user()->name ?? 'User'" 
                                    size="sm"
                                />
                                <svg class="w-4 h-4 text-ink-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div 
                                x-show="open" 
                                @click.outside="open = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-surface-200 shadow-elevation-3 py-2"
                            >
                                <div class="px-4 py-3 border-b border-surface-100">
                                    <div class="text-sm font-medium text-ink-primary">{{ auth()->user()->name ?? 'Guest' }}</div>
                                    <div class="text-xs text-ink-tertiary">{{ auth()->user()->email ?? '' }}</div>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-ink-primary hover:bg-surface-50 transition-colors">
                                    <svg class="w-4 h-4 text-ink-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profile
                                </a>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-ink-primary hover:bg-surface-50 transition-colors">
                                    <svg class="w-4 h-4 text-ink-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Settings
                                </a>
                                <div class="my-2 border-t border-surface-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-google-red-600 hover:bg-google-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="p-6">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    {{-- Toast Notifications --}}
    <x-ui.toast-container position="bottom-right" :max-toasts="5" :default-duration="4000" />

    @livewireScripts
    @stack('scripts')
</body>
</html>
