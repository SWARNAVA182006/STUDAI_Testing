@extends('layouts.app')

@section('title', 'AI Career Coach')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-4xl">🎯</span> AI Career Coach
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Your personal AI-powered career advisor</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sessions</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_sessions'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Goals</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_goals'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Goals</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['completed_goals'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Check-ins Done</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['checkins_completed'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Start New Session -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Start a Coaching Session</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @php
                            $sessionTypes = [
                                'general_advice' => ['icon' => '💬', 'label' => 'General Advice', 'desc' => 'Ask anything'],
                                'career_planning' => ['icon' => '🗺️', 'label' => 'Career Planning', 'desc' => 'Map your path'],
                                'skill_development' => ['icon' => '📚', 'label' => 'Skills', 'desc' => 'Level up'],
                                'job_search' => ['icon' => '🔍', 'label' => 'Job Search', 'desc' => 'Find opportunities'],
                                'interview_prep' => ['icon' => '🎤', 'label' => 'Interview Prep', 'desc' => 'Practice & prepare'],
                                'salary_negotiation' => ['icon' => '💰', 'label' => 'Negotiation', 'desc' => 'Get what you deserve'],
                            ];
                        @endphp
                        @foreach($sessionTypes as $type => $info)
                        <button onclick="startSession('{{ $type }}')" 
                                class="flex flex-col items-center p-4 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all group">
                            <span class="text-3xl mb-2">{{ $info['icon'] }}</span>
                            <span class="font-medium text-gray-900 dark:text-white text-sm">{{ $info['label'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $info['desc'] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Active Goals -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Active Goals</h2>
                        <a href="{{ route('career-coach.goals') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
                    </div>
                    @forelse($goals as $goal)
                    <div class="mb-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $goal->title }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $goal->getCategoryLabel() }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                @if($goal->priority === 'critical') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                @elseif($goal->priority === 'high') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                @else bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300
                                @endif">
                                {{ ucfirst($goal->priority) }}
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-500 dark:text-gray-400">Progress</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $goal->progress_percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ $goal->progress_percentage }}%"></div>
                            </div>
                        </div>
                        @if($goal->target_date)
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Target: {{ $goal->target_date->format('M d, Y') }}
                            @if($goal->isOverdue())
                            <span class="text-red-600 dark:text-red-400">(Overdue)</span>
                            @elseif($goal->getDaysRemaining() <= 7)
                            <span class="text-orange-600 dark:text-orange-400">({{ $goal->getDaysRemaining() }} days left)</span>
                            @endif
                        </p>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">No active goals yet</p>
                        <a href="{{ route('career-coach.goals') }}" class="mt-2 inline-block text-indigo-600 dark:text-indigo-400 hover:underline">Create your first goal</a>
                    </div>
                    @endforelse
                </div>

                <!-- Recent Sessions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sessions</h2>
                        <a href="{{ route('career-coach.history') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
                    </div>
                    <div class="space-y-3">
                        @forelse($sessions as $session)
                        <a href="{{ route('career-coach.session', $session) }}" 
                           class="block p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ $session->title }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->getTypeLabel() }} • {{ $session->message_count }} messages</p>
                                </div>
                                <span class="text-sm text-gray-400 dark:text-gray-500">{{ $session->last_message_at?->diffForHumans() ?? $session->created_at->diffForHumans() }}</span>
                            </div>
                        </a>
                        @empty
                        <p class="text-center py-4 text-gray-500 dark:text-gray-400">No sessions yet. Start your first coaching session above!</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Pending Check-ins -->
                @if($pendingCheckins->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>📅</span> Pending Check-ins
                    </h2>
                    @foreach($pendingCheckins as $checkin)
                    <div class="p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 mb-3">
                        <p class="font-medium text-indigo-900 dark:text-indigo-300">Weekly Check-in</p>
                        <p class="text-sm text-indigo-700 dark:text-indigo-400">{{ $checkin->scheduled_for->format('l, M d') }}</p>
                        <div class="mt-2 flex gap-2">
                            <button onclick="startCheckin({{ $checkin->id }})" class="px-3 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700">Start</button>
                            <button onclick="skipCheckin({{ $checkin->id }})" class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Skip</button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Suggestions -->
                @if($suggestions->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span>💡</span> Suggestions
                    </h2>
                    @foreach($suggestions as $suggestion)
                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 mb-3 relative">
                        <button onclick="dismissSuggestion({{ $suggestion->id }})" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <p class="font-medium text-yellow-900 dark:text-yellow-300 pr-6">{{ $suggestion->title }}</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">{{ Str::limit($suggestion->content, 100) }}</p>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
                    <div class="space-y-2">
                        <a href="{{ route('career-coach.goals') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="text-xl">🎯</span>
                            <span class="text-gray-900 dark:text-white">Manage Goals</span>
                        </a>
                        <a href="{{ route('career-coach.preferences') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="text-xl">⚙️</span>
                            <span class="text-gray-900 dark:text-white">Preferences</span>
                        </a>
                        <a href="{{ route('career-coach.history') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="text-xl">📜</span>
                            <span class="text-gray-900 dark:text-white">Session History</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function startSession(type) {
    try {
        const response = await fetch('{{ route("career-coach.session.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ type }),
        });
        
        const data = await response.json();
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (error) {
        console.error('Failed to start session:', error);
    }
}

async function startCheckin(checkinId) {
    try {
        const response = await fetch(`/career-coach/checkins/${checkinId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        
        const data = await response.json();
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        }
    } catch (error) {
        console.error('Failed to start check-in:', error);
    }
}

async function skipCheckin(checkinId) {
    if (!confirm('Skip this check-in?')) return;
    
    try {
        await fetch(`/career-coach/checkins/${checkinId}/skip`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        
        location.reload();
    } catch (error) {
        console.error('Failed to skip check-in:', error);
    }
}

async function dismissSuggestion(suggestionId) {
    try {
        await fetch(`/career-coach/suggestions/${suggestionId}/dismiss`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        
        location.reload();
    } catch (error) {
        console.error('Failed to dismiss suggestion:', error);
    }
}
</script>
@endpush
@endsection
