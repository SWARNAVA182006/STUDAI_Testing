@extends('layouts.app')

@section('title', 'Gamification Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <!-- Hero Section with Level & Points -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                <!-- Level Badge -->
                <div class="flex items-center gap-6">
                    <div class="relative">
                        <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm border-4 border-white/30">
                            <span class="text-4xl font-bold">{{ $profile['level'] }}</span>
                        </div>
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-yellow-400 text-yellow-900 px-3 py-0.5 rounded-full text-xs font-semibold">
                            LEVEL
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold">Welcome back, {{ auth()->user()->name }}!</h1>
                        <p class="text-white/80 mt-1">Keep up the momentum – you're making great progress!</p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="flex flex-wrap gap-4">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
                        <div class="text-3xl font-bold">{{ number_format($profile['total_points']) }}</div>
                        <div class="text-white/80 text-sm">Total Points</div>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
                        <div class="text-3xl font-bold flex items-center gap-1">
                            <span>🔥</span>{{ $profile['current_streak'] }}
                        </div>
                        <div class="text-white/80 text-sm">Day Streak</div>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 text-center">
                        <div class="text-3xl font-bold">#{{ $profile['rank'] ?: '—' }}</div>
                        <div class="text-white/80 text-sm">Global Rank</div>
                    </div>
                </div>
            </div>

            <!-- XP Progress Bar -->
            <div class="mt-6">
                <div class="flex justify-between text-sm mb-2">
                    <span>Level {{ $profile['level'] }}</span>
                    <span>{{ number_format($profile['xp_current']) }} / {{ number_format($profile['xp_required']) }} XP</span>
                    <span>Level {{ $profile['level'] + 1 }}</span>
                </div>
                <div class="h-4 bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full transition-all duration-500"
                         style="width: {{ $profile['xp_progress'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Profile Completion -->
                @if($profileCompletion['percentage'] < 100)
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Complete Your Profile</h2>
                        <span class="text-2xl font-bold text-indigo-600">{{ $profileCompletion['percentage'] }}%</span>
                    </div>
                    <div class="h-3 bg-gray-200 rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                             style="width: {{ $profileCompletion['percentage'] }}%"></div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($profileCompletion['sections'] as $section => $completed)
                        <div class="flex items-center gap-2 p-2 rounded-lg {{ $completed ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-gray-500' }}">
                            @if($completed)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                </svg>
                            @endif
                            <span class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $section) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Daily Challenges -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Daily Challenges</h2>
                        <a href="{{ route('gamification.challenges') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                            View All →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($challenges as $userChallenge)
                        @php $challenge = $userChallenge->challenge; @endphp
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl {{ $userChallenge->is_completed ? 'border-2 border-green-200' : '' }}">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl
                                {{ $userChallenge->is_completed ? 'bg-green-100' : 'bg-indigo-100' }}">
                                @if($userChallenge->is_completed)
                                    ✅
                                @else
                                    @switch($challenge->difficulty)
                                        @case('easy') 🎯 @break
                                        @case('medium') ⚡ @break
                                        @case('hard') 🔥 @break
                                    @endswitch
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $challenge->name }}</h3>
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                        {{ $challenge->difficulty === 'easy' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $challenge->difficulty === 'medium' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                        {{ $challenge->difficulty === 'hard' ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ ucfirst($challenge->difficulty) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-0.5">{{ $challenge->description }}</p>
                                <div class="mt-2">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-500">Progress:</span>
                                        <div class="flex-1 h-2 bg-gray-200 rounded-full max-w-[150px]">
                                            <div class="h-full bg-indigo-500 rounded-full transition-all" 
                                                 style="width: {{ $userChallenge->progress_percentage }}%"></div>
                                        </div>
                                        <span class="font-medium">{{ $userChallenge->progress }}/{{ $userChallenge->target }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-indigo-600">+{{ $challenge->points_reward }} pts</div>
                                <div class="text-xs text-gray-500">+{{ $challenge->xp_reward }} XP</div>
                                @if($userChallenge->canClaim())
                                <button onclick="claimChallenge({{ $userChallenge->id }})"
                                        class="mt-2 px-3 py-1 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition">
                                    Claim!
                                </button>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-500">
                            <p>No challenges available today. Check back tomorrow!</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Achievements -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Recent Achievements</h2>
                        <a href="{{ route('gamification.achievements') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                            View All →
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @forelse($achievements as $userAchievement)
                        @php $achievement = $userAchievement->achievement; @endphp
                        <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border border-yellow-200">
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl"
                                 style="background: {{ App\Models\Achievement::TIERS[$achievement->tier]['color'] ?? '#CD7F32' }}20">
                                {{ $achievement->icon ?? '🏆' }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $achievement->name }}</h3>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                          style="background: {{ App\Models\Achievement::TIERS[$achievement->tier]['color'] ?? '#CD7F32' }}20; 
                                                 color: {{ App\Models\Achievement::TIERS[$achievement->tier]['color'] ?? '#CD7F32' }}">
                                        {{ ucfirst($achievement->tier) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-0.5 line-clamp-1">{{ $achievement->description }}</p>
                                <p class="text-xs text-gray-400 mt-1">Unlocked {{ $userAchievement->unlocked_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="col-span-2 text-center py-8 text-gray-500">
                            <p class="text-3xl mb-2">🎯</p>
                            <p>Complete activities to unlock achievements!</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Current Event Banner -->
                @if($event)
                <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-2xl">🎉</span>
                        <span class="text-sm font-medium bg-white/20 px-2 py-0.5 rounded-full">LIVE EVENT</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2">{{ $event->name }}</h3>
                    <p class="text-white/80 text-sm mb-4">{{ Str::limit($event->description, 100) }}</p>
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span>{{ $event->remaining_time }}</span>
                        <span>{{ $event->xp_multiplier }}x XP Bonus</span>
                    </div>
                    <div class="h-2 bg-white/20 rounded-full">
                        <div class="h-full bg-white rounded-full" style="width: {{ $event->progress_percentage }}%"></div>
                    </div>
                    <a href="{{ route('gamification.events') }}" 
                       class="block w-full mt-4 text-center py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition">
                        View Event →
                    </a>
                </div>
                @endif

                <!-- Featured Badges -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Featured Badges</h3>
                        <a href="{{ route('gamification.badges') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        @forelse($badges as $userBadge)
                        @php $badge = $userBadge->badge; @endphp
                        <div class="relative group">
                            <div class="aspect-square rounded-xl flex items-center justify-center text-3xl border-2 cursor-pointer transition-all hover:scale-105"
                                 style="background: {{ $badge->color }}15; border-color: {{ $badge->color }}">
                                {{ $badge->icon }}
                            </div>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                {{ $badge->name }}
                            </div>
                        </div>
                        @empty
                        <div class="col-span-3 text-center py-6 text-gray-500">
                            <p class="text-3xl mb-2">🏅</p>
                            <p class="text-sm">Earn badges by completing activities!</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Explore</h3>
                    <div class="space-y-2">
                        <a href="{{ route('gamification.leaderboards') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-2xl">🏆</span>
                            <div>
                                <div class="font-medium text-gray-900">Leaderboards</div>
                                <div class="text-sm text-gray-500">Compete globally</div>
                            </div>
                        </a>
                        <a href="{{ route('gamification.rewards') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-2xl">🎁</span>
                            <div>
                                <div class="font-medium text-gray-900">Rewards Store</div>
                                <div class="text-sm text-gray-500">{{ number_format($profile['available_points']) }} points to spend</div>
                            </div>
                        </a>
                        <a href="{{ route('gamification.referrals') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-2xl">👥</span>
                            <div>
                                <div class="font-medium text-gray-900">Referrals</div>
                                <div class="text-sm text-gray-500">Invite friends, earn rewards</div>
                            </div>
                        </a>
                        <a href="{{ route('gamification.activity') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-2xl">📊</span>
                            <div>
                                <div class="font-medium text-gray-900">Activity History</div>
                                <div class="text-sm text-gray-500">View your progress</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Your Stats</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Achievements</span>
                            <span class="font-bold text-gray-900">{{ $profile['achievements_count'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Badges</span>
                            <span class="font-bold text-gray-900">{{ $profile['badges_count'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Best Streak</span>
                            <span class="font-bold text-gray-900">{{ $profile['longest_streak'] }} days</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Global Rank</span>
                            <span class="font-bold text-gray-900">#{{ $profile['rank'] ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function claimChallenge(challengeId) {
    fetch(`/gamification/challenges/${challengeId}/claim`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            alert(`Claimed! +${data.points} points, +${data.xp} XP`);
            window.location.reload();
        } else {
            alert(data.message || 'Failed to claim reward');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
@endpush
@endsection
