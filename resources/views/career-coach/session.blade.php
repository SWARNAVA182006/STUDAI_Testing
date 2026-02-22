@extends('layouts.app')

@section('title', $session->title . ' - AI Career Coach')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('career-coach.index') }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="font-semibold text-gray-900 dark:text-white">{{ $session->title }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->getTypeLabel() }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs rounded-full 
                    @if($session->status === 'active') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                    @endif">
                    {{ ucfirst($session->status) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 overflow-hidden">
        @livewire('career-coach-chat', ['session' => $session])
    </div>
</div>
@endsection
