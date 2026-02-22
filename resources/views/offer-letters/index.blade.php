@extends('layouts.app')

@php
    $isEmployer = auth()->user()->hasAnyRole(['employer', 'recruiter', 'admin']);
    $routePrefix = $isEmployer ? 'offer-letters.' : 'candidate.offers.';
@endphp

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Offer Letters</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @if($isEmployer)
                        Manage offer letters for candidates
                    @else
                        View and respond to your job offers
                    @endif
                </p>
            </div>
            @if($isEmployer)
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('offer-letters.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Offer Letter
                </a>
            </div>
            @endif
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <form method="GET" action="{{ route($routePrefix . 'index') }}" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search by job title or candidate name..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="w-full sm:w-48">
                    <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="viewed" {{ request('status') === 'viewed' ? 'selected' : '' }}>Viewed</option>
                        <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                        <option value="counter_offered" {{ request('status') === 'counter_offered' ? 'selected' : '' }}>Counter Offered</option>
                        <option value="withdrawn" {{ request('status') === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    Filter
                </button>
            </form>
        </div>

        <!-- Offers List -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg">
            @if($offers->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No offer letters</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if(auth()->user()->hasAnyRole(['employer', 'recruiter', 'admin']))
                            Get started by creating a new offer letter.
                        @else
                            You don't have any offer letters yet.
                        @endif
                    </p>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($offers as $offer)
                    <a href="{{ route($routePrefix . 'show', $offer) }}" class="block hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                                            {{ $offer->job_title }}
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @switch($offer->status)
                                                @case('draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @break
                                                @case('sent')
                                                @case('viewed')
                                                @case('under_review') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 @break
                                                @case('accepted') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                                                @case('declined')
                                                @case('withdrawn')
                                                @case('expired') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                                                @case('counter_offered') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                                                @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endswitch
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $offer->status)) }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:gap-4 text-sm text-gray-500 dark:text-gray-400">
                                        @if(auth()->user()->hasAnyRole(['employer', 'recruiter']))
                                            <span class="flex items-center">
                                                <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                {{ $offer->candidate->name ?? 'Unknown' }}
                                            </span>
                                        @else
                                            <span class="flex items-center">
                                                <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                {{ $offer->company->name ?? 'Unknown Company' }}
                                            </span>
                                        @endif
                                        <span class="flex items-center">
                                            <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $offer->formatted_salary }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Start: {{ $offer->start_date?->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-4 text-right">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                        ${{ number_format($offer->total_compensation, 0) }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Total Compensation
                                    </div>
                                    @if($offer->is_expired)
                                        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
                                            Expired
                                        </div>
                                    @elseif($offer->offer_expiry_date)
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Expires {{ $offer->offer_expiry_date->format('M d') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $offers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
