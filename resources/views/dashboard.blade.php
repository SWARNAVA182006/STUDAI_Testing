@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Your career command center')

@section('content')
<div class="space-y-6">
    {{-- Welcome Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Good {{ now()->format('H') < 12 ? 'morning' : (now()->format('H') < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name ?? 'there' }}! 👋
            </h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Here's what's happening with your career today</p>
        </div>
        <div class="flex items-center gap-3">
            <x-studai.button variant="secondary" size="sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Export Report
            </x-studai.button>
            <x-studai.button variant="primary" size="sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Application
            </x-studai.button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-studai.stat-card 
            title="Resume Score" 
            value="87" 
            suffix="%" 
            change="12% from last week"
            icon="heroicon-o-document-text"
            iconColor="blue"
        />
        <x-studai.stat-card 
            title="AI Match Rate" 
            value="94" 
            suffix="%" 
            change="8% improvement"
            icon="heroicon-o-bolt"
            iconColor="green"
        />
        <x-studai.stat-card 
            title="Applications" 
            value="24" 
            change="3 this week"
            icon="heroicon-o-clipboard-document-list"
            iconColor="purple"
        />
        <x-studai.stat-card 
            title="Interviews" 
            value="5" 
            change="2 scheduled"
            icon="heroicon-o-video-camera"
            iconColor="yellow"
        />
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recommended Jobs (2 columns) --}}
        <div class="lg:col-span-2">
            <x-studai.card>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recommended Jobs</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">AI-powered matches based on your profile</p>
                    </div>
                    <a href="#" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700 flex items-center gap-1">
                        View all
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div class="space-y-4">
                    {{-- Job Card 1 --}}
                    <div class="group p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-studai-blue-200 dark:hover:border-studai-blue-800 hover:bg-studai-blue-50/50 dark:hover:bg-studai-blue-900/10 transition-all duration-200 cursor-pointer">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                G
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-studai-blue-600 transition-colors">Senior Software Engineer</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Google • Mountain View, CA</p>
                                    </div>
                                    <x-studai.ai-score :score="96" size="sm" />
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <x-studai.chip size="sm">$180k - $250k</x-studai.chip>
                                    <x-studai.chip size="sm" color="blue">Remote</x-studai.chip>
                                    <x-studai.chip size="sm" color="gray">Full-time</x-studai.chip>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Job Card 2 --}}
                    <div class="group p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-studai-blue-200 dark:hover:border-studai-blue-800 hover:bg-studai-blue-50/50 dark:hover:bg-studai-blue-900/10 transition-all duration-200 cursor-pointer">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                S
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-studai-blue-600 transition-colors">Staff Product Designer</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Spotify • New York, NY</p>
                                    </div>
                                    <x-studai.ai-score :score="92" size="sm" />
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <x-studai.chip size="sm">$160k - $220k</x-studai.chip>
                                    <x-studai.chip size="sm" color="purple">Hybrid</x-studai.chip>
                                    <x-studai.chip size="sm" color="gray">Full-time</x-studai.chip>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Job Card 3 --}}
                    <div class="group p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-studai-blue-200 dark:hover:border-studai-blue-800 hover:bg-studai-blue-50/50 dark:hover:bg-studai-blue-900/10 transition-all duration-200 cursor-pointer">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                M
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-studai-blue-600 transition-colors">Engineering Manager</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Meta • Menlo Park, CA</p>
                                    </div>
                                    <x-studai.ai-score :score="89" size="sm" />
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <x-studai.chip size="sm">$200k - $300k</x-studai.chip>
                                    <x-studai.chip size="sm" color="blue">Remote</x-studai.chip>
                                    <x-studai.chip size="sm" color="gray">Full-time</x-studai.chip>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-studai.card>
        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-6">
            {{-- AI Agent Status --}}
            <x-studai.card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">AI Agent</h3>
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-sm text-green-600 dark:text-green-400">Active</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Jobs Analyzed</span>
                        <span class="font-medium text-gray-900 dark:text-white">147 today</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Applications Sent</span>
                        <span class="font-medium text-gray-900 dark:text-white">12 today</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Queue</span>
                        <span class="font-medium text-gray-900 dark:text-white">8 pending</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="#" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700 flex items-center justify-center gap-2">
                        View Agent Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </x-studai.card>

            {{-- Upcoming Interviews --}}
            <x-studai.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Upcoming Interviews</h3>
                    <x-studai.badge color="blue">2 this week</x-studai.badge>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex flex-col items-center justify-center">
                            <span class="text-xs font-bold text-studai-blue-600 dark:text-studai-blue-400">MAR</span>
                            <span class="text-sm font-bold text-studai-blue-600 dark:text-studai-blue-400">15</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">Google - Technical</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">10:00 AM PST</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex flex-col items-center justify-center">
                            <span class="text-xs font-bold text-green-600 dark:text-green-400">MAR</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">18</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">Spotify - Culture Fit</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">2:30 PM PST</p>
                        </div>
                    </div>
                </div>
            </x-studai.card>

            {{-- Skill Gaps --}}
            <x-studai.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Skill Gaps</h3>
                    <a href="#" class="text-sm text-studai-blue-600 hover:text-studai-blue-700">Improve</a>
                </div>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">System Design</span>
                            <span class="text-gray-900 dark:text-white font-medium">72%</span>
                        </div>
                        <x-studai.progress :value="72" color="amber" size="sm" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Kubernetes</span>
                            <span class="text-gray-900 dark:text-white font-medium">58%</span>
                        </div>
                        <x-studai.progress :value="58" color="red" size="sm" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 dark:text-gray-400">GraphQL</span>
                            <span class="text-gray-900 dark:text-white font-medium">85%</span>
                        </div>
                        <x-studai.progress :value="85" color="green" size="sm" />
                    </div>
                </div>
            </x-studai.card>
        </div>
    </div>

    {{-- Application Timeline --}}
    <x-studai.card>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Application Timeline</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Track your application journey</p>
            </div>
            <div class="flex items-center gap-2">
                <x-studai.chip size="sm" color="green">3 Active</x-studai.chip>
                <x-studai.chip size="sm" color="gray">2 Pending</x-studai.chip>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Company</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Position</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Applied</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">AI Score</th>
                        <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">G</div>
                                <span class="font-medium text-gray-900 dark:text-white">Google</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">Senior Software Engineer</td>
                        <td class="py-4 px-4">
                            <x-studai.badge color="blue" dot>Interview</x-studai.badge>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">Mar 8, 2024</td>
                        <td class="py-4 px-4">
                            <x-studai.ai-score :score="96" size="xs" />
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-teal-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">S</div>
                                <span class="font-medium text-gray-900 dark:text-white">Spotify</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">Staff Product Designer</td>
                        <td class="py-4 px-4">
                            <x-studai.badge color="green" dot>Offer</x-studai.badge>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">Mar 1, 2024</td>
                        <td class="py-4 px-4">
                            <x-studai.ai-score :score="92" size="xs" />
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">M</div>
                                <span class="font-medium text-gray-900 dark:text-white">Meta</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">Engineering Manager</td>
                        <td class="py-4 px-4">
                            <x-studai.badge color="amber" dot>Review</x-studai.badge>
                        </td>
                        <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">Mar 10, 2024</td>
                        <td class="py-4 px-4">
                            <x-studai.ai-score :score="89" size="xs" />
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-studai.card>
</div>
@endsection
