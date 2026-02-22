@extends('layouts.employer')

@section('title', 'Referral Leaderboard')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li><a href="{{ route('employer.referrals.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Referrals</a></li>
                        <li><span class="mx-2 text-gray-400">/</span></li>
                        <li class="text-gray-900 dark:text-white font-medium">Leaderboard</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Referral Leaderboard</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Top referring employees ranked by successful hires</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <form action="{{ route('employer.referrals.leaderboard') }}" method="GET">
                    <select name="period" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all_time" {{ $period == 'all_time' ? 'selected' : '' }}>All Time</option>
                        <option value="this_year" {{ $period == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="this_quarter" {{ $period == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                    </select>
                </form>
            </div>
        </div>

        @if(count($rankedLeaderboard) > 0)
        <!-- Top 3 Podium -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach(array_slice($rankedLeaderboard, 0, 3) as $index => $entry)
            @php
                $colors = [
                    0 => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-400', 'badge' => 'bg-yellow-400 text-yellow-900', 'icon' => '🥇'],
                    1 => ['bg' => 'bg-gray-50 dark:bg-gray-700/50', 'border' => 'border-gray-300', 'badge' => 'bg-gray-300 text-gray-700', 'icon' => '🥈'],
                    2 => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'border' => 'border-orange-400', 'badge' => 'bg-orange-400 text-orange-900', 'icon' => '🥉'],
                ];
                $color = $colors[$index];
            @endphp
            <div class="{{ $color['bg'] }} border-2 {{ $color['border'] }} rounded-xl p-6 text-center {{ $index === 0 ? 'md:order-2' : ($index === 1 ? 'md:order-1' : 'md:order-3') }}">
                <div class="text-4xl mb-2">{{ $color['icon'] }}</div>
                <div class="relative inline-block mb-4">
                    <div class="h-20 w-20 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center mx-auto border-4 border-white dark:border-gray-800 shadow-lg">
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                            {{ strtoupper(substr($entry['referrer']->name, 0, 2)) }}
                        </span>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $entry['referrer']->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $entry['referrer']->email }}</p>
                
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $entry['successful_hires'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Successful Hires</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $entry['success_rate'] }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Success Rate</p>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Earned</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">₹{{ number_format($entry['total_earned']) }}</p>
                    @if($entry['pending_payout'] > 0)
                    <p class="text-xs text-yellow-600 dark:text-yellow-400">+ ₹{{ number_format($entry['pending_payout']) }} pending</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Full Rankings Table -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Complete Rankings</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Referrals</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Successful Hires</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Success Rate</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Earned</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rankedLeaderboard as $entry)
                    <tr class="{{ $entry['rank'] <= 3 ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($entry['rank'] <= 3)
                                <span class="text-xl">
                                    @switch($entry['rank'])
                                        @case(1) 🥇 @break
                                        @case(2) 🥈 @break
                                        @case(3) 🥉 @break
                                    @endswitch
                                </span>
                                @else
                                <span class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-sm font-medium text-gray-600 dark:text-gray-300">
                                    {{ $entry['rank'] }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                            {{ strtoupper(substr($entry['referrer']->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry['referrer']->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $entry['referrer']->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entry['total_referrals'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                {{ $entry['successful_hires'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($entry['success_rate'], 100) }}%"></div>
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $entry['success_rate'] }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">₹{{ number_format($entry['total_earned']) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if($entry['pending_payout'] > 0)
                            <span class="text-sm text-yellow-600 dark:text-yellow-400">₹{{ number_format($entry['pending_payout']) }}</span>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No referrals yet</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">The leaderboard will populate once employees start referring candidates.</p>
            <a href="{{ route('employer.referrals.index') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Go to Referrals
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
