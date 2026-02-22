@extends('layouts.app')

@section('title', 'Active Coaching Sessions - Negotiation')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-4">
                <a href="{{ route('negotiation.dashboard') }}" class="hover:text-gray-700">Negotiation</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900 dark:text-white">Active Sessions</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Active Coaching Sessions</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Resume your ongoing negotiation coaching sessions</p>
        </div>

        {{-- Sessions List --}}
        @if($activeSessions->count() > 0)
            <div class="grid gap-6">
                @foreach($activeSessions as $session)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        Active
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        Started {{ $session->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if($session->strategy)
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $session->strategy->role ?? 'Negotiation Session' }}
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ $session->strategy->company ?? 'Company Not Specified' }}
                                    </p>
                                @else
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        Negotiation Session #{{ $session->id }}
                                    </h3>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('negotiation.coaching', $session->id) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-medium rounded-lg hover:from-pink-600 hover:to-purple-700 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Resume Session
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $activeSessions->links() }}
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Active Sessions</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Start a new coaching session by creating a negotiation strategy first.</p>
                <a href="{{ route('negotiation.strategy') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-medium rounded-lg hover:from-pink-600 hover:to-purple-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Strategy
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
