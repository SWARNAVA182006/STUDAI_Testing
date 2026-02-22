@extends('layouts.app')

@section('title', 'Company Reviews & Ratings - StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-white mb-2">Company Reviews & Ratings</h1>
            <p class="text-primary-100 text-lg">Discover what it's really like to work at top companies</p>

            {{-- Search Bar --}}
            <form action="{{ route('companies.index') }}" method="GET" class="mt-8 max-w-2xl">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search companies..." class="w-full pl-12 pr-4 py-4 rounded-xl border-0 shadow-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-white/50">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="submit" class="px-8 py-4 bg-white text-primary-600 font-semibold rounded-xl shadow-lg hover:bg-gray-50 transition">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Filters Sidebar --}}
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-24">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Filters</h3>

                    {{-- Industry Filter --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Industry</label>
                        <select name="industry" onchange="this.form.submit()" form="filter-form" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Industries</option>
                            @foreach ($industries as $industry)
                                <option value="{{ $industry }}" {{ ($filters['industry'] ?? '') === $industry ? 'selected' : '' }}>{{ $industry }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Rating Filter --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minimum Rating</label>
                        <div class="space-y-2">
                            @foreach ([4, 3, 2, 1] as $rating)
                                <a href="{{ route('companies.index', array_merge($filters, ['min_rating' => $rating])) }}" class="flex items-center gap-2 px-3 py-2 rounded-lg {{ ($filters['min_rating'] ?? '') == $rating ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                    <div class="flex">
                                        @for ($i = 1; $i <= $rating; $i++)
                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endfor
                                    </div>
                                    <span class="text-sm">& up</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if (!empty(array_filter($filters)))
                        <a href="{{ route('companies.index') }}" class="text-sm text-primary-600 hover:underline">Clear all filters</a>
                    @endif
                </div>
            </div>

            {{-- Company List --}}
            <div class="flex-1">
                {{-- Sort Bar --}}
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $companies->total() }}</span> companies
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">Sort by:</span>
                        <select onchange="window.location = this.value" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="{{ route('companies.index', array_merge($filters, ['sort' => 'reviews'])) }}" {{ ($filters['sort'] ?? 'reviews') === 'reviews' ? 'selected' : '' }}>Most Reviews</option>
                            <option value="{{ route('companies.index', array_merge($filters, ['sort' => 'rating'])) }}" {{ ($filters['sort'] ?? '') === 'rating' ? 'selected' : '' }}>Highest Rated</option>
                            <option value="{{ route('companies.index', array_merge($filters, ['sort' => 'name'])) }}" {{ ($filters['sort'] ?? '') === 'name' ? 'selected' : '' }}>A-Z</option>
                        </select>
                    </div>
                </div>

                {{-- Companies Grid --}}
                <div class="space-y-4">
                    @forelse ($companies as $company)
                        <a href="{{ route('companies.show', $company) }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition group">
                            <div class="flex items-start gap-4">
                                {{-- Logo --}}
                                <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if ($company->logo)
                                        <img src="{{ $company->logo }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-2xl font-bold text-gray-400">{{ substr($company->name, 0, 1) }}</span>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="font-semibold text-lg text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition">
                                                {{ $company->name }}
                                                @if ($company->is_verified)
                                                    <svg class="inline-block w-5 h-5 text-blue-500 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                @endif
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                @if ($company->industry)
                                                    <span>{{ $company->industry }}</span>
                                                    <span>•</span>
                                                @endif
                                                @if ($company->headquarters)
                                                    <span>{{ $company->headquarters }}</span>
                                                    <span>•</span>
                                                @endif
                                                @if ($company->company_size)
                                                    <span>{{ $company->company_size }} employees</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Rating --}}
                                        <div class="text-right flex-shrink-0">
                                            @if ($company->avg_overall_rating)
                                                <div class="flex items-center gap-1">
                                                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($company->avg_overall_rating, 1) }}</span>
                                                    <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                </div>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $company->review_count }} reviews</p>
                                            @else
                                                <p class="text-sm text-gray-400">No reviews yet</p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Quick Stats --}}
                                    <div class="flex flex-wrap gap-4 mt-4">
                                        @if ($company->recommend_rate)
                                            <div class="text-sm">
                                                <span class="text-green-600 dark:text-green-400 font-semibold">{{ $company->recommend_rate }}%</span>
                                                <span class="text-gray-500 dark:text-gray-400">recommend</span>
                                            </div>
                                        @endif
                                        @if ($company->salary_count)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $company->salary_count }} salaries
                                            </div>
                                        @endif
                                        @if ($company->interview_count)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $company->interview_count }} interviews
                                            </div>
                                        @endif
                                        @if ($company->jobs_count)
                                            <div class="text-sm text-primary-600 dark:text-primary-400">
                                                {{ $company->jobs_count }} open jobs
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No companies found</h3>
                            <p class="text-gray-500 dark:text-gray-400">Try adjusting your search or filters</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if ($companies->hasPages())
                    <div class="mt-8">
                        {{ $companies->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="filter-form" action="{{ route('companies.index') }}" method="GET">
    <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}">
</form>
@endsection
