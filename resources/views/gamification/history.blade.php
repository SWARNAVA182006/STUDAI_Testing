@extends('layouts.app')

@section('title', 'Gamification History')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Activity History</h1>
                <p class="text-gray-600 mt-1">Track your points, XP, and achievements over time</p>
            </div>
            <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white rounded-xl shadow-lg p-2 mb-8">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('gamification.history', ['type' => 'all']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition {{ $type === 'all' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    All Activity
                </a>
                <a href="{{ route('gamification.history', ['type' => 'points']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition {{ $type === 'points' ? 'bg-yellow-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    💰 Points
                </a>
                <a href="{{ route('gamification.history', ['type' => 'xp']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition {{ $type === 'xp' ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    ✨ XP
                </a>
                <a href="{{ route('gamification.history', ['type' => 'achievements']) }}"
                   class="px-4 py-2 rounded-lg font-medium transition {{ $type === 'achievements' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    🏆 Achievements
                </a>
            </div>
        </div>

        <!-- Stats Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Points Earned -->
            <div class="bg-gradient-to-br from-yellow-400 to-amber-500 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">Total Points Earned</div>
                        <div class="text-3xl font-bold">
                            {{ number_format($pointsHistory->sum('amount')) }}
                        </div>
                    </div>
                    <div class="text-4xl">💰</div>
                </div>
            </div>

            <!-- Total XP Earned -->
            <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">Total XP Earned</div>
                        <div class="text-3xl font-bold">
                            {{ number_format($xpHistory->sum('amount')) }}
                        </div>
                    </div>
                    <div class="text-4xl">✨</div>
                </div>
            </div>

            <!-- Activities -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90">Total Activities</div>
                        <div class="text-3xl font-bold">
                            {{ number_format($activities->count()) }}
                        </div>
                    </div>
                    <div class="text-4xl">📊</div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-900">Recent Activity</h2>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($activities as $activity)
                <div class="p-6 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                            @switch($activity->activity_type)
                                @case('achievement_unlocked') bg-green-100 @break
                                @case('badge_earned') bg-purple-100 @break
                                @case('level_up') bg-blue-100 @break
                                @case('points_earned') bg-yellow-100 @break
                                @case('xp_earned') bg-indigo-100 @break
                                @case('streak_bonus') bg-orange-100 @break
                                @default bg-gray-100
                            @endswitch">
                            <span class="text-2xl">
                                @switch($activity->activity_type)
                                    @case('achievement_unlocked') 🏆 @break
                                    @case('badge_earned') 🎖️ @break
                                    @case('level_up') ⬆️ @break
                                    @case('points_earned') 💰 @break
                                    @case('xp_earned') ✨ @break
                                    @case('streak_bonus') 🔥 @break
                                    @case('challenge_completed') 🎯 @break
                                    @case('reward_redeemed') 🎁 @break
                                    @default 📣
                                @endswitch
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-gray-900">
                                    {{ ucwords(str_replace('_', ' ', $activity->activity_type)) }}
                                </h3>
                                @if($activity->points_earned)
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-yellow-100 text-yellow-700">
                                    +{{ $activity->points_earned }} pts
                                </span>
                                @endif
                                @if($activity->xp_earned)
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700">
                                    +{{ $activity->xp_earned }} XP
                                </span>
                                @endif
                            </div>
                            <p class="text-gray-600 text-sm">{{ $activity->description }}</p>
                        </div>

                        <!-- Timestamp -->
                        <div class="text-sm text-gray-400 flex-shrink-0">
                            {{ $activity->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-12 text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No activity yet</h3>
                    <p class="text-gray-500">Start using the platform to earn points and achievements!</p>
                </div>
                @endforelse
            </div>

            @if($activities->count() >= 50)
            <div class="p-6 bg-gray-50 text-center">
                <button class="text-indigo-600 hover:text-indigo-700 font-medium">
                    Load More Activity
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
