@extends('layouts.app')

@section('title', 'Jobs at ' . $company->name . ' | StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Company Header --}}
    @include('companies.partials.header', ['company' => $company, 'activeTab' => 'jobs'])

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Jobs List --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Open Positions at {{ $company->name }}
                        @if($jobs->total() > 0)
                            <span class="text-gray-500 dark:text-gray-400 text-lg font-normal">({{ $jobs->total() }})</span>
                        @endif
                    </h2>
                </div>

                {{-- Filters --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48">
                            <input type="text" 
                                   placeholder="Search job titles..." 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500">
                        </div>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">All Locations</option>
                            @foreach($jobs->unique('location')->pluck('location')->filter() as $location)
                                <option value="{{ $location }}">{{ $location }}</option>
                            @endforeach
                        </select>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">All Job Types</option>
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                            <option value="remote">Remote</option>
                        </select>
                    </div>
                </div>

                {{-- Jobs List --}}
                <div class="space-y-4">
                    @forelse($jobs as $job)
                        <a href="{{ route('jobs.show', $job) }}" 
                           class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                        {{ $job->title }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        @if($job->location)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                {{ $job->location }}
                                            </span>
                                        @endif
                                        @if($job->employment_type)
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs">
                                                {{ ucfirst(str_replace('_', ' ', $job->employment_type)) }}
                                            </span>
                                        @endif
                                        @if($job->experience_level)
                                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded text-xs">
                                                {{ ucfirst($job->experience_level) }}
                                            </span>
                                        @endif
                                        @if($job->is_remote)
                                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded text-xs">
                                                Remote
                                            </span>
                                        @endif
                                    </div>

                                    @if($job->description)
                                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                            {{ Str::limit(strip_tags($job->description), 200) }}
                                        </p>
                                    @endif

                                    {{-- Skills --}}
                                    @if($job->required_skills && count($job->required_skills) > 0)
                                        <div class="flex flex-wrap gap-1 mt-3">
                                            @foreach(array_slice($job->required_skills, 0, 5) as $skill)
                                                <span class="px-2 py-0.5 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded text-xs">
                                                    {{ $skill }}
                                                </span>
                                            @endforeach
                                            @if(count($job->required_skills) > 5)
                                                <span class="px-2 py-0.5 text-gray-500 dark:text-gray-400 text-xs">
                                                    +{{ count($job->required_skills) - 5 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                {{-- Salary & Meta --}}
                                <div class="text-right flex-shrink-0">
                                    @if($job->salary_min && $job->salary_max)
                                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                            ${{ number_format($job->salary_min / 1000) }}k - ${{ number_format($job->salary_max / 1000) }}k
                                        </p>
                                    @elseif($job->salary_min)
                                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                            From ${{ number_format($job->salary_min / 1000) }}k
                                        </p>
                                    @endif
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Posted {{ $job->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Open Positions</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                {{ $company->name }} doesn't have any open positions right now.
                            </p>
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('jobs.search') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                    Browse All Jobs
                                </a>
                                @auth
                                    <button type="button" 
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        Get Notified
                                    </button>
                                @endauth
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($jobs->hasPages())
                    <div class="mt-6">
                        {{ $jobs->links() }}
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Company Overview --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">About {{ $company->name }}</h3>
                    
                    @if($company->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-4">
                            {{ $company->description }}
                        </p>
                    @endif

                    <div class="space-y-3 text-sm">
                        @if($company->industry)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $company->industry }}</span>
                            </div>
                        @endif
                        @if($company->company_size)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $company->company_size }} employees</span>
                            </div>
                        @endif
                        @if($company->founded_year)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">Founded {{ $company->founded_year }}</span>
                            </div>
                        @endif
                        @if($company->headquarters_location)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">{{ $company->headquarters_location }}</span>
                            </div>
                        @endif
                    </div>

                    @if($company->website)
                        <a href="{{ $company->website }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="mt-4 block w-full text-center px-4 py-2 border border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400 font-medium rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                            Visit Website
                        </a>
                    @endif
                </div>

                {{-- Company Rating --}}
                @if($company->average_rating)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Employee Reviews</h3>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="text-4xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($company->average_rating, 1) }}
                            </div>
                            <div>
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= round($company->average_rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $company->reviews_count ?? 0 }} reviews
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('companies.reviews', $company) }}" 
                           class="text-primary-600 dark:text-primary-400 hover:underline text-sm font-medium">
                            Read all reviews →
                        </a>
                    </div>
                @endif

                {{-- Similar Companies --}}
                @if(isset($similarCompanies) && $similarCompanies->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Similar Companies</h3>
                        <div class="space-y-3">
                            @foreach($similarCompanies as $similarCompany)
                                <a href="{{ route('companies.show', $similarCompany) }}" 
                                   class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    @if($similarCompany->logo_url)
                                        <img src="{{ $similarCompany->logo_url }}" alt="{{ $similarCompany->name }}" class="w-10 h-10 rounded-lg object-contain bg-white">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                                            <span class="text-white text-sm font-bold">{{ substr($similarCompany->name, 0, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $similarCompany->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $similarCompany->jobs_count ?? 0 }} jobs
                                        </p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
