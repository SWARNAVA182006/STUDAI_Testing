@extends('layouts.app')

@section('title', 'Gamification Stats')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Your Stats</h1>
                <p class="text-gray-600 mt-1">Detailed breakdown of your gamification progress</p>
            </div>
            <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Level & XP Section -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 rounded-3xl shadow-2xl p-8 mb-8 text-white">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Level Display -->
                <div class="text-center lg:text-left">
                    <div class="text-sm opacity-80 mb-2">Current Level</div>
                    <div class="flex items-center justify-center lg:justify-start gap-4">
                        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center">
                            <span class="text-4xl font-black">{{ $profile->level }}</span>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ $profile->title ?? 'Career Explorer' }}</div>
                            <div class="text-white/80">{{ number_format($profile->total_xp) }} Total XP</div>
                        </div>
                    </div>
                </div>

                <!-- XP Progress -->
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span>Level {{ $profile->level }} Progress</span>
                        <span>{{ number_format($profile->xp_current) }} / {{ number_format($profile->xp_required) }} XP</span>
                    </div>
                    <div class="h-6 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white/80 rounded-full transition-all duration-500"
                             style="width: {{ $profile->level_progress }}%"></div>
                    </div>
                    <div class="text-sm text-white/80 mt-2">
                        {{ number_format($profile->xp_required - $profile->xp_current) }} XP to Level {{ $profile->level + 1 }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <div class="text-4xl mb-2">💰</div>
                <div class="text-3xl font-bold text-gray-900">{{ number_format($profile->available_points) }}</div>
                <div class="text-sm text-gray-500">Available Points</div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <div class="text-4xl mb-2">🔥</div>
                <div class="text-3xl font-bold text-gray-900">{{ $profile->current_streak }}</div>
                <div class="text-sm text-gray-500">Day Streak</div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <div class="text-4xl mb-2">🏆</div>
                <div class="text-3xl font-bold text-gray-900">{{ $stats['achievements_count'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Achievements</div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 text-center">
                <div class="text-4xl mb-2">🎖️</div>
                <div class="text-3xl font-bold text-gray-900">{{ $stats['badges_count'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Badges</div>
            </div>
        </div>

        <!-- Detailed Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Activity Stats -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Activity Breakdown</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📝</span>
                            <span class="font-medium text-gray-700">Job Applications</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ $stats['applications_count'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🎯</span>
                            <span class="font-medium text-gray-700">Challenges Completed</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ $stats['challenges_completed'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">📚</span>
                            <span class="font-medium text-gray-700">Skills Validated</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ $stats['skills_validated'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">💼</span>
                            <span class="font-medium text-gray-700">Interviews Practiced</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ $stats['interviews_practiced'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🎁</span>
                            <span class="font-medium text-gray-700">Rewards Redeemed</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ $stats['rewards_redeemed'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Streak Stats -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Streak Records</h2>
                
                <div class="space-y-6">
                    <!-- Current Streak -->
                    <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-orange-100 to-red-100 rounded-xl">
                        <div class="text-4xl">🔥</div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600">Current Streak</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $profile->current_streak }} Days</div>
                        </div>
                        @if($profile->current_streak > 0)
                        <div class="text-green-600 font-semibold">Active!</div>
                        @endif
                    </div>

                    <!-- Longest Streak -->
                    <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-yellow-100 to-amber-100 rounded-xl">
                        <div class="text-4xl">👑</div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600">Longest Streak</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $profile->longest_streak }} Days</div>
                        </div>
                        @if($profile->current_streak >= $profile->longest_streak && $profile->current_streak > 0)
                        <div class="text-amber-600 font-semibold">Personal Best!</div>
                        @endif
                    </div>

                    <!-- Streak Freezes -->
                    <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-blue-100 to-cyan-100 rounded-xl">
                        <div class="text-4xl">❄️</div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-600">Streak Freezes Available</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $profile->streak_freezes ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- Streak Bonus Info -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Streak Bonuses</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div class="flex justify-between">
                                <span>7-Day Streak:</span>
                                <span class="font-medium">+10% Points Bonus</span>
                            </div>
                            <div class="flex justify-between">
                                <span>14-Day Streak:</span>
                                <span class="font-medium">+20% Points Bonus</span>
                            </div>
                            <div class="flex justify-between">
                                <span>30-Day Streak:</span>
                                <span class="font-medium">+50% Points Bonus + Special Badge</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Position -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Your Rankings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl">
                    <div class="text-4xl mb-2">🌍</div>
                    <div class="text-3xl font-bold text-indigo-600">#{{ $stats['global_rank'] ?? '—' }}</div>
                    <div class="text-sm text-gray-600">Global Rank</div>
                </div>
                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl">
                    <div class="text-4xl mb-2">📊</div>
                    <div class="text-3xl font-bold text-green-600">#{{ $stats['weekly_rank'] ?? '—' }}</div>
                    <div class="text-sm text-gray-600">Weekly Rank</div>
                </div>
                <div class="text-center p-6 bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl">
                    <div class="text-4xl mb-2">🏢</div>
                    <div class="text-3xl font-bold text-amber-600">#{{ $stats['industry_rank'] ?? '—' }}</div>
                    <div class="text-sm text-gray-600">Industry Rank</div>
                </div>
            </div>

            @if(!$profile->show_on_leaderboard)
            <div class="mt-6 p-4 bg-yellow-50 rounded-xl text-center">
                <p class="text-yellow-700">
                    <span class="font-semibold">You're hidden from leaderboards.</span>
                    <a href="{{ route('gamification.leaderboards') }}" class="underline hover:no-underline">Enable visibility</a> to see your ranking.
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
