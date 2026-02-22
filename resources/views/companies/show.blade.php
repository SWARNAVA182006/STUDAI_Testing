@extends('layouts.app')

@section('title', $company->name . ' - Reviews & Ratings | StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Company Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-6">
                    {{-- Logo --}}
                    <div class="w-24 h-24 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-sm">
                        @if ($company->logo)
                            <img src="{{ $company->logo }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-4xl font-bold text-gray-400">{{ substr($company->name, 0, 1) }}</span>
                        @endif
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            {{ $company->name }}
                            @if ($company->is_verified)
                                <svg class="w-7 h-7 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </h1>

                        <div class="flex flex-wrap items-center gap-3 text-gray-600 dark:text-gray-400 mt-2">
                            @if ($company->industry)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    {{ $company->industry }}
                                </span>
                            @endif
                            @if ($company->headquarters)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $company->headquarters }}
                                </span>
                            @endif
                            @if ($company->company_size)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    {{ $company->company_size }} employees
                                </span>
                            @endif
                            @if ($company->website)
                                <a href="{{ $company->website }}" target="_blank" class="inline-flex items-center gap-1 text-primary-600 hover:underline">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    Website
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @auth
                        <form action="{{ route('companies.follow', $company) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 border {{ $company->isFollowedBy(auth()->user()) ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300' }} rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <svg class="w-5 h-5" fill="{{ $company->isFollowedBy(auth()->user()) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                {{ $company->isFollowedBy(auth()->user()) ? 'Following' : 'Follow' }}
                            </button>
                        </form>
                    @endauth

                    <a href="{{ route('companies.reviews.create', $company) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl shadow-sm transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Write a Review
                    </a>
                </div>
            </div>

            {{-- Navigation Tabs --}}
            <nav class="flex gap-1 mt-8 overflow-x-auto border-t border-gray-200 dark:border-gray-700 pt-4">
                <a href="{{ route('companies.show', $company) }}" class="px-4 py-2 rounded-lg font-medium text-sm {{ request()->routeIs('companies.show') ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition whitespace-nowrap">
                    Overview
                </a>
                <a href="{{ route('companies.reviews', $company) }}" class="px-4 py-2 rounded-lg font-medium text-sm {{ request()->routeIs('companies.reviews') ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition whitespace-nowrap">
                    Reviews ({{ $ratingSummary['review_count'] ?? 0 }})
                </a>
                <a href="{{ route('companies.salaries', $company) }}" class="px-4 py-2 rounded-lg font-medium text-sm {{ request()->routeIs('companies.salaries') ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition whitespace-nowrap">
                    Salaries ({{ $ratingSummary['salary_count'] ?? 0 }})
                </a>
                <a href="{{ route('companies.interviews', $company) }}" class="px-4 py-2 rounded-lg font-medium text-sm {{ request()->routeIs('companies.interviews') ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition whitespace-nowrap">
                    Interviews ({{ $ratingSummary['interview_count'] ?? 0 }})
                </a>
                <a href="{{ route('companies.jobs', $company) }}" class="px-4 py-2 rounded-lg font-medium text-sm {{ request()->routeIs('companies.jobs') ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition whitespace-nowrap">
                    Jobs ({{ $company->jobs->count() }})
                </a>
            </nav>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Left Column --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Rating Overview Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Employee Ratings</h2>

                    <div class="flex flex-col md:flex-row md:items-center gap-8">
                        {{-- Overall Rating --}}
                        <div class="text-center">
                            <div class="text-6xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($ratingSummary['overall_rating'] ?? 0, 1) }}
                            </div>
                            <div class="flex items-center justify-center mt-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-6 h-6 {{ $i <= round($ratingSummary['overall_rating'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $ratingSummary['review_count'] ?? 0 }} reviews</p>
                        </div>

                        {{-- Rating Breakdown --}}
                        <div class="flex-1">
                            @foreach (($ratingSummary['ratings_breakdown'] ?? []) as $stars => $data)
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="w-4 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stars }}</span>
                                    <div class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-400 rounded-full transition-all duration-500" style="width: {{ $data['percentage'] }}%"></div>
                                    </div>
                                    <span class="w-12 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $data['percentage'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Category Ratings --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        @foreach ([
                            'culture' => ['label' => 'Culture', 'icon' => '🎭'],
                            'compensation' => ['label' => 'Pay', 'icon' => '💰'],
                            'work_life_balance' => ['label' => 'Work-Life', 'icon' => '⚖️'],
                            'career_growth' => ['label' => 'Growth', 'icon' => '📈'],
                            'management' => ['label' => 'Management', 'icon' => '👔'],
                        ] as $key => $info)
                            @php $rating = $ratingSummary['category_ratings'][$key] ?? null; @endphp
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="text-2xl mb-1">{{ $info['icon'] }}</div>
                                <div class="text-lg font-bold {{ $rating ? ($rating >= 4 ? 'text-green-600' : ($rating >= 3 ? 'text-yellow-600' : 'text-red-600')) : 'text-gray-400' }}">
                                    {{ $rating ? number_format($rating, 1) : 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $info['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Reviews --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recent Reviews</h2>
                        <a href="{{ route('companies.reviews', $company) }}" class="text-primary-600 hover:text-primary-700 font-medium text-sm">View all →</a>
                    </div>

                    <div class="space-y-6">
                        @forelse ($recentReviews as $review)
                            <div class="pb-6 border-b border-gray-100 dark:border-gray-700 last:pb-0 last:border-0">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                            @if ($review->review_title)
                                                <span class="font-semibold text-gray-900 dark:text-white">{{ $review->review_title }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $review->display_author }} • {{ $review->job_title }} • {{ $review->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-green-600 dark:text-green-400 font-medium mb-1">✓ Pros</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ $review->pros }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-red-600 dark:text-red-400 font-medium mb-1">✗ Cons</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ $review->cons }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 dark:text-gray-400 py-8">No reviews yet. Be the first to share your experience!</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">At a Glance</h3>
                    <div class="space-y-4">
                        @if ($ratingSummary['recommend_rate'])
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Recommend to friend</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ $ratingSummary['recommend_rate'] }}%</span>
                            </div>
                        @endif
                        @if ($ratingSummary['ceo_approval'])
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">CEO Approval</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ $ratingSummary['ceo_approval'] }}%</span>
                            </div>
                        @endif
                        @if ($interviewStats['avg_difficulty'] ?? null)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Interview Difficulty</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $interviewStats['avg_difficulty'] }}/5</span>
                            </div>
                        @endif
                        @if ($interviewStats['positive_rate'] ?? null)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Positive Interview Exp.</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ $interviewStats['positive_rate'] }}%</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Salary Overview --}}
                @if ($salaryStats['count'] ?? 0 > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Salaries</h3>
                            <a href="{{ route('companies.salaries', $company) }}" class="text-sm text-primary-600 hover:underline">View all</a>
                        </div>
                        <div class="text-center py-4">
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($salaryStats['median'] / 100) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Median total compensation</p>
                            <p class="text-xs text-gray-400 mt-2">${{ number_format($salaryStats['min'] / 100) }} - ${{ number_format($salaryStats['max'] / 100) }}</p>
                        </div>
                        <a href="{{ route('companies.salaries.create', $company) }}" class="block mt-4 text-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            + Add Your Salary
                        </a>
                    </div>
                @endif

                {{-- Open Jobs --}}
                @if ($company->jobs->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Open Jobs</h3>
                            <a href="{{ route('companies.jobs', $company) }}" class="text-sm text-primary-600 hover:underline">View all</a>
                        </div>
                        <div class="space-y-3">
                            @foreach ($company->jobs->take(3) as $job)
                                <a href="{{ route('jobs.show', $job) }}" class="block p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $job->title }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $job->location }}</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Contribute Section --}}
                <div class="bg-gradient-to-br from-primary-50 to-blue-50 dark:from-primary-900/30 dark:to-blue-900/30 rounded-2xl p-6 border border-primary-100 dark:border-primary-800">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Help others by sharing</h3>
                    <div class="space-y-2">
                        <a href="{{ route('companies.reviews.create', $company) }}" class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition">
                            <span class="text-2xl">📝</span>
                            <span class="font-medium text-gray-900 dark:text-white">Write a Review</span>
                        </a>
                        <a href="{{ route('companies.salaries.create', $company) }}" class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition">
                            <span class="text-2xl">💰</span>
                            <span class="font-medium text-gray-900 dark:text-white">Share Salary</span>
                        </a>
                        <a href="{{ route('companies.interviews.create', $company) }}" class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition">
                            <span class="text-2xl">🎤</span>
                            <span class="font-medium text-gray-900 dark:text-white">Share Interview</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
