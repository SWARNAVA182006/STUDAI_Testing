@extends('layouts.app')

@section('title', 'Leaderboards')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Leaderboards</h1>
                <p class="text-gray-600 mt-1">See how you rank against other professionals</p>
            </div>
            <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ← Back to Dashboard
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Your Position -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">Your Position</h3>
                    <div class="text-center mb-4">
                        <div class="text-5xl font-bold mb-1">
                            @if($userRank > 0)
                                #{{ $userRank }}
                            @else
                                —
                            @endif
                        </div>
                        <div class="text-white/80">Global Rank</div>
                    </div>
                    <div class="space-y-3 pt-4 border-t border-white/20">
                        <div class="flex justify-between">
                            <span class="text-white/80">Level</span>
                            <span class="font-semibold">{{ $profile->level }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-white/80">Total Points</span>
                            <span class="font-semibold">{{ number_format($profile->total_points) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-white/80">Achievements</span>
                            <span class="font-semibold">{{ $profile->achievements_unlocked }}</span>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard Settings -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Settings</h3>
                    <form action="{{ route('gamification.leaderboards.settings') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="show_on_leaderboard" 
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           {{ $profile->show_on_leaderboard ? 'checked' : '' }}>
                                    <span class="text-gray-700">Show on leaderboard</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                                <input type="text" name="display_name" 
                                       value="{{ $profile->leaderboard_display_name }}"
                                       placeholder="Anonymous name (optional)"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                                <select name="primary_industry" 
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Industry</option>
                                    @foreach($industries as $ind)
                                    <option value="{{ $ind }}" {{ $profile->primary_industry === $ind ? 'selected' : '' }}>
                                        {{ $ind }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Leaderboard Type</h3>
                    <div class="space-y-2">
                        <a href="{{ route('gamification.leaderboards', ['type' => 'global']) }}"
                           class="flex items-center gap-3 p-3 rounded-xl transition {{ $type === 'global' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}">
                            <span class="text-xl">🌍</span>
                            <span class="font-medium">Global</span>
                        </a>
                        <a href="{{ route('gamification.leaderboards', ['type' => 'industry', 'industry' => $profile->primary_industry ?? 'Technology']) }}"
                           class="flex items-center gap-3 p-3 rounded-xl transition {{ $type === 'industry' ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}">
                            <span class="text-xl">🏢</span>
                            <span class="font-medium">Industry</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Leaderboard -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <!-- Leaderboard Header -->
                    <div class="bg-gradient-to-r from-yellow-400 via-orange-400 to-red-400 p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-white">
                                @if($type === 'global')
                                    🌍 Global Leaderboard
                                @else
                                    🏢 {{ $selectedIndustry ?? 'Industry' }} Leaderboard
                                @endif
                            </h2>
                            
                            @if($type === 'industry')
                            <select onchange="window.location.href = '{{ route('gamification.leaderboards') }}?type=industry&industry=' + this.value"
                                    class="rounded-lg border-0 text-sm font-medium bg-white/20 text-white placeholder-white/80">
                                @foreach($industries as $ind)
                                <option value="{{ $ind }}" {{ $selectedIndustry === $ind ? 'selected' : '' }} class="text-gray-900">
                                    {{ $ind }}
                                </option>
                                @endforeach
                            </select>
                            @endif
                        </div>
                    </div>

                    <!-- Top 3 Podium -->
                    @if($leaderboard->count() >= 3)
                    <div class="bg-gradient-to-b from-gray-50 to-white p-8">
                        <div class="flex items-end justify-center gap-4">
                            <!-- 2nd Place -->
                            <div class="text-center">
                                <div class="w-20 h-20 mx-auto bg-gray-200 rounded-full flex items-center justify-center text-3xl mb-2 ring-4 ring-gray-300">
                                    🥈
                                </div>
                                <div class="font-bold text-gray-900">{{ $leaderboard[1]['display_name'] ?? 'Anonymous' }}</div>
                                <div class="text-sm text-gray-500">Level {{ $leaderboard[1]['level'] }}</div>
                                <div class="text-lg font-bold text-gray-700">{{ number_format($leaderboard[1]['total_points']) }}</div>
                                <div class="h-24 w-20 bg-gray-200 rounded-t-lg mt-2 flex items-center justify-center">
                                    <span class="text-2xl font-bold text-gray-500">#2</span>
                                </div>
                            </div>
                            
                            <!-- 1st Place -->
                            <div class="text-center -mt-8">
                                <div class="w-24 h-24 mx-auto bg-yellow-100 rounded-full flex items-center justify-center text-4xl mb-2 ring-4 ring-yellow-400 animate-pulse">
                                    🥇
                                </div>
                                <div class="font-bold text-gray-900 text-lg">{{ $leaderboard[0]['display_name'] ?? 'Anonymous' }}</div>
                                <div class="text-sm text-gray-500">Level {{ $leaderboard[0]['level'] }}</div>
                                <div class="text-xl font-bold text-yellow-600">{{ number_format($leaderboard[0]['total_points']) }}</div>
                                <div class="h-32 w-24 bg-yellow-400 rounded-t-lg mt-2 flex items-center justify-center">
                                    <span class="text-3xl font-bold text-yellow-800">#1</span>
                                </div>
                            </div>
                            
                            <!-- 3rd Place -->
                            <div class="text-center">
                                <div class="w-20 h-20 mx-auto bg-orange-100 rounded-full flex items-center justify-center text-3xl mb-2 ring-4 ring-orange-300">
                                    🥉
                                </div>
                                <div class="font-bold text-gray-900">{{ $leaderboard[2]['display_name'] ?? 'Anonymous' }}</div>
                                <div class="text-sm text-gray-500">Level {{ $leaderboard[2]['level'] }}</div>
                                <div class="text-lg font-bold text-orange-700">{{ number_format($leaderboard[2]['total_points']) }}</div>
                                <div class="h-16 w-20 bg-orange-200 rounded-t-lg mt-2 flex items-center justify-center">
                                    <span class="text-2xl font-bold text-orange-600">#3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Leaderboard Table -->
                    <div class="divide-y divide-gray-100">
                        @foreach($leaderboard->skip(3) as $entry)
                        <div class="flex items-center gap-4 p-4 hover:bg-gray-50 transition {{ $entry['user_id'] === auth()->id() ? 'bg-indigo-50' : '' }}">
                            <!-- Rank -->
                            <div class="w-12 text-center">
                                <span class="text-lg font-bold {{ $entry['user_id'] === auth()->id() ? 'text-indigo-600' : 'text-gray-400' }}">
                                    #{{ $entry['rank'] }}
                                </span>
                            </div>
                            
                            <!-- User Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900">
                                        {{ $entry['display_name'] }}
                                        @if($entry['user_id'] === auth()->id())
                                        <span class="text-indigo-600">(You)</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Level {{ $entry['level'] }} • {{ $entry['achievements'] ?? 0 }} achievements
                                </div>
                            </div>
                            
                            <!-- Stats -->
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900">{{ number_format($entry['total_points']) }}</div>
                                <div class="text-sm text-gray-500">points</div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Empty State -->
                    @if($leaderboard->isEmpty())
                    <div class="p-12 text-center">
                        <div class="text-6xl mb-4">🏆</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">No rankings yet</h3>
                        <p class="text-gray-500">Be the first to join the leaderboard!</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
