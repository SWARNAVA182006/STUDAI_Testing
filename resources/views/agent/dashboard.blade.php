@extends('layouts.dashboard')

@section('title', 'AI Agent')
@section('page-title', 'Autonomous Agent')
@section('page-description', 'Your AI-powered job application assistant')

@section('content')
<div class="space-y-6">
    @if(!$configured)
        {{-- Not Configured - Onboarding State --}}
        <div class="flex items-center justify-center min-h-[60vh]">
            <x-studai.card class="max-w-2xl w-full text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-3">Configure Your AI Agent</h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-8">
                    Set up your autonomous assistant to discover, analyze, and apply to jobs that match your preferences — 24/7.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="p-4 bg-studai-blue-50 dark:bg-studai-blue-900/20 rounded-2xl">
                        <div class="w-10 h-10 bg-studai-blue-100 dark:bg-studai-blue-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-studai-blue-600 dark:text-studai-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-1">Auto Discovery</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Scans job boards hourly</p>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-1">AI Analysis</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Smart job matching</p>
                    </div>
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-2xl">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-800 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-1">Auto Apply</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Submits applications 24/7</p>
                    </div>
                </div>

                <x-studai.button href="{{ route('agent.configure') }}" variant="primary" size="lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Get Started
                </x-studai.button>
            </x-studai.card>
        </div>
    @else
        {{-- Agent Header with Status --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @if($config->is_active && !$config->is_paused)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500 border-2 border-white dark:border-gray-900"></span>
                        </span>
                    @endif
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">AI Agent</h1>
                    <div class="flex items-center gap-2 mt-1">
                        @if($config->is_active && !$config->is_paused)
                            <x-studai.badge color="green" dot>Active</x-studai.badge>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Running since {{ $config->activated_at->format('M d') }}</span>
                        @elseif($config->is_paused)
                            <x-studai.badge color="amber" dot>Paused</x-studai.badge>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Temporarily suspended</span>
                        @else
                            <x-studai.badge color="gray">Inactive</x-studai.badge>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Not running</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if(!$config->is_active)
                    <form action="{{ route('agent.activate') }}" method="POST">
                        @csrf
                        <x-studai.button type="submit" variant="primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Activate
                        </x-studai.button>
                    </form>
                @elseif($config->is_paused)
                    <form action="{{ route('agent.resume') }}" method="POST">
                        @csrf
                        <x-studai.button type="submit" variant="primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            </svg>
                            Resume
                        </x-studai.button>
                    </form>
                @else
                    <form action="{{ route('agent.pause') }}" method="POST">
                        @csrf
                        <x-studai.button type="submit" variant="secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pause
                        </x-studai.button>
                    </form>
                @endif
                <x-studai.button href="{{ route('agent.configure') }}" variant="ghost">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </x-studai.button>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-studai.stat-card 
                title="Jobs Analyzed" 
                value="{{ $statistics['total_analyzed'] ?? 0 }}" 
                change="147 today"
                icon="heroicon-o-magnifying-glass"
                iconColor="blue"
            />
            <x-studai.stat-card 
                title="Applications Sent" 
                value="{{ $statistics['total_applications'] }}" 
                change="{{ $statistics['today_applications'] }} today"
                icon="heroicon-o-paper-airplane"
                iconColor="green"
            />
            <x-studai.stat-card 
                title="Success Rate" 
                value="{{ $statistics['success_rate'] }}" 
                suffix="%" 
                change="{{ $statistics['successful_applications'] }} interviews"
                icon="heroicon-o-check-circle"
                iconColor="purple"
            />
            <x-studai.stat-card 
                title="Daily Limit" 
                value="{{ $limits['daily_remaining'] }}/{{ $limits['daily_limit'] }}" 
                change="Remaining today"
                icon="heroicon-o-clock"
                iconColor="yellow"
            />
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Job Queue (2 columns) --}}
            <div class="lg:col-span-2">
                <x-studai.card>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Application Queue</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jobs pending application</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-studai.chip size="sm" color="amber">{{ $statistics['pending_applications'] ?? 8 }} pending</x-studai.chip>
                            <a href="{{ route('agent.applications') }}" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                                View all →
                            </a>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse($recentApplications->take(5) as $application)
                            <div class="group flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-sm">
                                    {{ substr($application->company_name, 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $application->job_title }}</h4>
                                        @if($application->match_score)
                                            <x-studai.ai-score :score="round($application->match_score)" size="xs" />
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $application->company_name }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($application->status === 'submitted')
                                        <x-studai.badge color="blue" size="sm">Submitted</x-studai.badge>
                                    @elseif($application->status === 'pending')
                                        <x-studai.badge color="amber" size="sm">Pending</x-studai.badge>
                                    @elseif($application->status === 'pending_approval')
                                        <x-studai.badge color="purple" size="sm">Needs Review</x-studai.badge>
                                    @elseif($application->status === 'failed')
                                        <x-studai.badge color="red" size="sm">Failed</x-studai.badge>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $application->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">No applications yet</p>
                                <p class="text-sm text-gray-400 mt-1">Your agent will start applying once activated</p>
                            </div>
                        @endforelse
                    </div>
                </x-studai.card>
            </div>

            {{-- Right Sidebar --}}
            <div class="space-y-6">
                {{-- AI Activity Feed --}}
                <x-studai.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white">AI Activity</h3>
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-studai-blue-600 dark:text-studai-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">Scanning LinkedIn...</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Just now</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">Applied to Google</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">2 min ago</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">Found 12 new matches</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">5 min ago</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">Tailored resume for Meta</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">8 min ago</p>
                            </div>
                        </div>
                    </div>
                </x-studai.card>

                {{-- Daily Insights --}}
                <x-studai.card>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Today's Insights</h3>
                    <div class="space-y-4">
                        <div class="p-3 bg-studai-blue-50 dark:bg-studai-blue-900/20 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                <span class="text-sm font-medium text-studai-blue-700 dark:text-studai-blue-300">Trending Skill</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">"Kubernetes" appears in 73% of your matched jobs</p>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-green-700 dark:text-green-300">Best Performance</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Remote positions have 2x higher response rate</p>
                        </div>
                    </div>
                </x-studai.card>

                {{-- Quick Actions --}}
                <x-studai.card>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('agent.applications') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            <div class="w-9 h-9 bg-studai-blue-100 dark:bg-studai-blue-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">View All Applications</span>
                        </a>
                        <a href="{{ route('agent.metrics') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            <div class="w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Performance Metrics</span>
                        </a>
                        @if($config->enable_learning)
                            <a href="{{ route('agent.learning') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                                <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">AI Learning Insights</span>
                            </a>
                        @endif
                    </div>
                </x-studai.card>
            </div>
        </div>
    @endif
</div>
@endsection
