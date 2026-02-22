@extends('layouts.app')

@section('title', 'Daily Challenges')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Daily Challenges</h1>
                <p class="text-gray-600 mt-1">Complete challenges to earn bonus points and XP</p>
            </div>
            <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Streak Banner -->
        <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 rounded-2xl shadow-lg p-6 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">🔥</div>
                    <div>
                        <div class="text-4xl font-bold">{{ $streak }} Day Streak</div>
                        <div class="text-white/80">Keep it going for bonus rewards!</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-white/80">Streak Bonus</div>
                    <div class="text-2xl font-bold">+{{ min($streak * 5, 50) }}%</div>
                </div>
            </div>
            
            <!-- Streak Calendar Preview -->
            <div class="mt-6 flex items-center justify-center gap-2">
                @for($i = 6; $i >= 0; $i--)
                @php
                    $date = now()->subDays($i);
                    $isActive = $i <= $streak - 1;
                @endphp
                <div class="text-center">
                    <div class="text-xs text-white/60 mb-1">{{ $date->format('D') }}</div>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $isActive ? 'bg-white/30' : 'bg-white/10' }}">
                        @if($isActive)
                            🔥
                        @else
                            <span class="text-white/40">{{ $date->format('d') }}</span>
                        @endif
                    </div>
                </div>
                @endfor
            </div>
        </div>

        <!-- Today's Challenges -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Today's Challenges</h2>
                <span class="text-sm text-gray-500">
                    Resets in {{ now()->endOfDay()->diffForHumans() }}
                </span>
            </div>

            <div class="space-y-4">
                @forelse($challenges as $userChallenge)
                @php 
                    $challenge = $userChallenge->challenge;
                    $diffData = App\Models\DailyChallenge::DIFFICULTIES[$challenge->difficulty] ?? App\Models\DailyChallenge::DIFFICULTIES['easy'];
                @endphp
                <div class="border-2 rounded-2xl transition-all {{ $userChallenge->is_completed ? 'border-green-200 bg-green-50' : 'border-gray-100 hover:border-indigo-200' }}">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl flex-shrink-0"
                                 style="background: {{ $diffData['color'] }}20">
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

                            <!-- Content -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-lg font-bold text-gray-900">{{ $challenge->name }}</h3>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                          style="background: {{ $diffData['color'] }}20; color: {{ $diffData['color'] }}">
                                        {{ ucfirst($challenge->difficulty) }}
                                    </span>
                                </div>
                                <p class="text-gray-600">{{ $challenge->description }}</p>

                                <!-- Progress Bar -->
                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <span class="text-gray-500">Progress</span>
                                        <span class="font-semibold {{ $userChallenge->is_completed ? 'text-green-600' : 'text-gray-900' }}">
                                            {{ $userChallenge->progress }} / {{ $userChallenge->target }}
                                            @if($userChallenge->is_completed)
                                                ✓ Complete
                                            @endif
                                        </span>
                                    </div>
                                    <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                             style="width: {{ $userChallenge->progress_percentage }}%; background: {{ $userChallenge->is_completed ? '#22C55E' : $diffData['color'] }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rewards -->
                            <div class="text-right flex-shrink-0">
                                <div class="text-lg font-bold text-yellow-600">
                                    +{{ $challenge->points_reward }} pts
                                </div>
                                <div class="text-sm text-indigo-600">
                                    +{{ $challenge->xp_reward }} XP
                                </div>
                                @if($streak > 0)
                                <div class="text-xs text-green-600 mt-1">
                                    +{{ $challenge->streak_bonus }} streak
                                </div>
                                @endif

                                @if($userChallenge->canClaim())
                                <button onclick="claimChallenge({{ $userChallenge->id }})"
                                        class="mt-3 px-4 py-2 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 transition animate-pulse">
                                    Claim Reward!
                                </button>
                                @elseif($userChallenge->is_claimed)
                                <div class="mt-3 px-4 py-2 bg-gray-100 text-gray-500 font-medium rounded-xl">
                                    Claimed ✓
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🎯</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No challenges today</h3>
                    <p class="text-gray-500">Check back tomorrow for new challenges!</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Tips -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">💡 Tips to Complete Challenges</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📝</span>
                    <div>
                        <h4 class="font-semibold text-gray-900">Keep Your Profile Updated</h4>
                        <p class="text-sm text-gray-500">Updating your profile counts towards daily challenges</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🔍</span>
                    <div>
                        <h4 class="font-semibold text-gray-900">Apply to Jobs Daily</h4>
                        <p class="text-sm text-gray-500">Each application earns points and completes challenges</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🎓</span>
                    <div>
                        <h4 class="font-semibold text-gray-900">Take Skill Tests</h4>
                        <p class="text-sm text-gray-500">Prove your skills and earn extra XP</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🔥</span>
                    <div>
                        <h4 class="font-semibold text-gray-900">Maintain Your Streak</h4>
                        <p class="text-sm text-gray-500">Longer streaks mean bigger bonuses!</p>
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
            alert(`🎉 Claimed! +${data.points} points, +${data.xp} XP`);
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
