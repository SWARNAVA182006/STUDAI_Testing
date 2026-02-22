{{-- Company Header Partial - Reusable across company pages --}}
@php
    $tabs = [
        'overview' => ['label' => 'Overview', 'route' => 'companies.show'],
        'reviews' => ['label' => 'Reviews', 'route' => 'companies.reviews', 'count' => $company->reviews_count ?? $company->reviews()->approved()->count()],
        'salaries' => ['label' => 'Salaries', 'route' => 'companies.salaries', 'count' => $company->salaryReports()->where('status', 'approved')->count()],
        'interviews' => ['label' => 'Interviews', 'route' => 'companies.interviews', 'count' => $company->interviewExperiences()->where('status', 'approved')->count()],
        'jobs' => ['label' => 'Jobs', 'route' => 'companies.jobs', 'count' => $company->jobs()->active()->count()],
    ];
@endphp

<div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            {{-- Company Logo --}}
            <div class="flex-shrink-0">
                @if($company->logo_url)
                    <img src="{{ $company->logo_url }}" 
                         alt="{{ $company->name }} logo" 
                         class="w-20 h-20 md:w-24 md:h-24 rounded-xl object-contain bg-gray-100 dark:bg-gray-700 p-2">
                @else
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                        <span class="text-white text-2xl md:text-3xl font-bold">{{ substr($company->name, 0, 2) }}</span>
                    </div>
                @endif
            </div>

            {{-- Company Info --}}
            <div class="flex-1">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $company->name }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-600 dark:text-gray-400">
                            @if($company->industry)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    {{ $company->industry }}
                                </span>
                            @endif
                            @if($company->headquarters_location)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $company->headquarters_location }}
                                </span>
                            @endif
                            @if($company->company_size)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    {{ $company->company_size }} employees
                                </span>
                            @endif
                            @if($company->website)
                                <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer" 
                                   class="flex items-center gap-1 text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                    Website
                                </a>
                            @endif
                        </div>

                        {{-- Ratings Summary --}}
                        <div class="flex items-center gap-4 mt-3">
                            @if($company->average_rating)
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ number_format($company->average_rating, 1) }}
                                    </span>
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= round($company->average_rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        ({{ $company->reviews_count ?? $company->reviews()->approved()->count() }} reviews)
                                    </span>
                                </div>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">No ratings yet</span>
                            @endif

                            @if($company->recommend_percentage)
                                <div class="flex items-center gap-1 text-sm">
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $company->recommend_percentage }}%</span>
                                    <span class="text-gray-500 dark:text-gray-400">recommend</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3">
                        @auth
                            <form action="{{ route('companies.follow', $company) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                    Follow
                                </button>
                            </form>
                        @endauth
                        
                        <a href="{{ route('companies.reviews.create', $company) }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Write Review
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex space-x-1 overflow-x-auto pb-px" aria-label="Company sections">
            @foreach($tabs as $key => $tab)
                <a href="{{ route($tab['route'], $company) }}" 
                   class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ ($activeTab ?? 'overview') === $key 
                       ? 'border-primary-500 text-primary-600 dark:text-primary-400' 
                       : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    {{ $tab['label'] }}
                    @if(isset($tab['count']) && $tab['count'] > 0)
                        <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ ($activeTab ?? 'overview') === $key 
                            ? 'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400' 
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</div>
