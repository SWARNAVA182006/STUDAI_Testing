@extends('layouts.app')

@section('title', 'Network Feed - StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 py-8">
        {{-- Network Navigation --}}
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Your Network</h1>
            <nav class="flex items-center space-x-4">
                <a href="{{ route('network.feed') }}" 
                   class="text-indigo-600 font-medium border-b-2 border-indigo-600 pb-1">
                    Feed
                </a>
                <a href="{{ route('network.connections') }}" 
                   class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Connections
                </a>
                <a href="{{ route('network.groups') }}" 
                   class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Groups
                </a>
                <a href="{{ route('network.mentorship') }}" 
                   class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Mentorship
                </a>
                <a href="{{ route('network.messages') }}" 
                   class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 relative">
                    Messages
                    {{-- Unread badge would go here --}}
                </a>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Feed --}}
            <div class="lg:col-span-2">
                @livewire('network.activity-feed')
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Profile Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-center">
                        <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" 
                             alt="{{ auth()->user()->name }}"
                             class="w-16 h-16 rounded-full mx-auto mb-3">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</h3>
                        <p class="text-sm text-gray-500">{{ auth()->user()->candidateProfile?->current_title ?? 'Professional' }}</p>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ auth()->user()->connections()->count() }}
                            </p>
                            <p class="text-xs text-gray-500">Connections</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ auth()->user()->followers()->count() }}
                            </p>
                            <p class="text-xs text-gray-500">Followers</p>
                        </div>
                    </div>
                </div>

                {{-- Connection Suggestions --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">People You May Know</h3>
                    <div class="space-y-3">
                        @php
                            $networkingService = app(\App\Services\NetworkingService::class);
                            $suggestions = $networkingService->getConnectionSuggestions(auth()->user(), 3);
                        @endphp
                        @foreach($suggestions as $suggestion)
                            <div class="flex items-center space-x-3">
                                <img src="{{ $suggestion->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($suggestion->name) }}" 
                                     class="w-10 h-10 rounded-full">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $suggestion->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $suggestion->candidateProfile?->current_title ?? 'Professional' }}</p>
                                </div>
                                <a href="{{ route('network.connections') }}" 
                                   class="text-indigo-600 text-sm hover:underline">
                                    Connect
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('network.connections') }}" 
                       class="block mt-4 text-center text-sm text-indigo-600 hover:underline">
                        See all suggestions →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
