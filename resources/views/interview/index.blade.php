@extends('layouts.dashboard')

@section('title', 'Interview Practice')
@section('page-title', 'Interview Practice')
@section('page-description', 'AI-powered mock interviews & coaching')

@section('content')
<div class="space-y-6">
    {{-- Hero Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-studai.stat-card 
            title="Practice Sessions" 
            value="{{ $sessions->count() ?? 0 }}" 
            change="3 this week"
            icon="heroicon-o-video-camera"
            iconColor="blue"
        />
        <x-studai.stat-card 
            title="Avg. Score" 
            value="82" 
            suffix="%" 
            change="+8% improvement"
            icon="heroicon-o-chart-bar"
            iconColor="green"
        />
        <x-studai.stat-card 
            title="Questions Practiced" 
            value="47" 
            change="12 behavioral"
            icon="heroicon-o-question-mark-circle"
            iconColor="purple"
        />
        <x-studai.stat-card 
            title="Interview Ready" 
            value="94" 
            suffix="%" 
            change="High confidence"
            icon="heroicon-o-check-circle"
            iconColor="green"
        />
    </div>

    {{-- Quick Actions Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Start New Session --}}
        <x-studai.card variant="interactive" class="group text-center">
            <div class="w-16 h-16 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Start Practice Session</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">AI-powered mock interview with real-time feedback</p>
            <x-studai.button href="{{ route('interview.create') }}" variant="primary" class="w-full">
                Start Now
            </x-studai.button>
        </x-studai.card>

        {{-- Common Questions --}}
        <x-studai.card variant="interactive" class="group text-center">
            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Question Bank</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Browse 500+ common interview questions</p>
            <x-studai.button href="{{ route('interview.common-questions') }}" variant="secondary" class="w-full">
                Browse Questions
            </x-studai.button>
        </x-studai.card>

        {{-- Interview Tips --}}
        <x-studai.card variant="interactive" class="group text-center">
            <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Pro Tips & Guides</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Master interview techniques & strategies</p>
            <x-studai.button href="{{ route('interview.tips') }}" variant="secondary" class="w-full">
                View Tips
            </x-studai.button>
        </x-studai.card>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Sessions (2 columns) --}}
        <div class="lg:col-span-2">
            <x-studai.card>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sessions</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Your practice history</p>
                    </div>
                    <a href="#" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">View all →</a>
                </div>

                @if(isset($sessions) && $sessions->count() > 0)
                    <div class="space-y-3">
                        @foreach($sessions->take(5) as $session)
                            <div class="group flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center
                                    @if($session->completed_at) bg-green-100 dark:bg-green-900/30 @else bg-amber-100 dark:bg-amber-900/30 @endif">
                                    @if($session->completed_at)
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ $session->job_title ?? 'General Interview' }}
                                        </h4>
                                        @if($session->completed_at)
                                            <x-studai.badge color="green" size="sm">Completed</x-studai.badge>
                                        @else
                                            <x-studai.badge color="amber" size="sm">In Progress</x-studai.badge>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->questions_count ?? 0 }} questions • {{ $session->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($session->overall_score)
                                        <x-studai.ai-score :score="$session->overall_score" size="sm" />
                                    @endif
                                    @if($session->completed_at)
                                        <x-studai.button href="{{ route('interview.complete', $session) }}" variant="ghost" size="sm">
                                            View Report
                                        </x-studai.button>
                                    @else
                                        <x-studai.button href="{{ route('interview.session', $session) }}" variant="primary" size="sm">
                                            Continue
                                        </x-studai.button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">No sessions yet</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Start your first practice session to track progress</p>
                        <x-studai.button href="{{ route('interview.create') }}" variant="primary">
                            Start Practice
                        </x-studai.button>
                    </div>
                @endif
            </x-studai.card>
        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-6">
            {{-- AI Coach Insights --}}
            <x-studai.card>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">AI Coach</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Personalized tips</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="p-3 bg-studai-blue-50 dark:bg-studai-blue-900/20 rounded-xl">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            💡 <span class="font-medium">Focus on STAR method</span> — Your behavioral answers could be more structured.
                        </p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            ⭐ <span class="font-medium">Strong technical skills</span> — Your coding explanations are clear and concise.
                        </p>
                    </div>
                </div>
            </x-studai.card>

            {{-- Resources --}}
            <x-studai.card>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Learning Resources</h3>
                <div class="space-y-3">
                    <a href="{{ route('interview.star-guide') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                        <div class="w-9 h-9 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">STAR Method Guide</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Master behavioral interviews</p>
                        </div>
                    </a>
                    <a href="{{ route('interview.salary-negotiation') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                        <div class="w-9 h-9 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Salary Negotiation</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get the compensation you deserve</p>
                        </div>
                    </a>
                </div>
            </x-studai.card>

            {{-- Upcoming Interviews --}}
            <x-studai.card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Upcoming</h3>
                    <x-studai.badge color="blue">2 scheduled</x-studai.badge>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-studai-blue-100 dark:bg-studai-blue-900/30 rounded-lg flex flex-col items-center justify-center">
                            <span class="text-[10px] font-bold text-studai-blue-600 dark:text-studai-blue-400">DEC</span>
                            <span class="text-sm font-bold text-studai-blue-600 dark:text-studai-blue-400">2</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Google - Technical</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">10:00 AM PST</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex flex-col items-center justify-center">
                            <span class="text-[10px] font-bold text-green-600 dark:text-green-400">DEC</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">5</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Spotify - Culture Fit</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">2:30 PM PST</p>
                        </div>
                    </div>
                </div>
            </x-studai.card>
        </div>
    </div>
</div>
@endsection
