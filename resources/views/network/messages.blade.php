@extends('layouts.app')

@section('title', 'Messages - StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 py-8">
        {{-- Network Navigation --}}
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Messages</h1>
            <nav class="flex items-center space-x-4">
                <a href="{{ route('network.feed') }}" 
                   class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
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
                   class="text-indigo-600 font-medium border-b-2 border-indigo-600 pb-1">
                    Messages
                </a>
            </nav>
        </div>

        @livewire('network.messaging-center')
    </div>
</div>
@endsection
