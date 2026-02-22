@extends('layouts.app')

@section('title', 'Session History - AI Career Coach')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1">
                    <li><a href="{{ route('career-coach.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Career Coach</a></li>
                    <li><span class="mx-2 text-gray-400">/</span></li>
                    <li class="text-gray-900 dark:text-white font-medium">History</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📜 Session History</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">Review your past coaching conversations</p>
        </div>

        <!-- Sessions List -->
        <div class="space-y-4">
            @forelse($sessions as $session)
            <a href="{{ route('career-coach.session', $session) }}" 
               class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                {{ $session->getTypeLabel() }}
                            </span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full 
                                @if($session->status === 'active') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                @elseif($session->status === 'completed') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                @else bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                @endif">
                                {{ ucfirst($session->status) }}
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $session->title }}</h3>
                        
                        @if($session->summary)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            {{ $session->summary['summary'] ?? 'No summary available' }}
                        </p>
                        @endif

                        @if($session->key_insights && count($session->key_insights) > 0)
                        <div class="mt-3">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Key Insights:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(array_slice($session->key_insights, 0, 3) as $insight)
                                <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                    {{ Str::limit($insight, 40) }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    <div class="text-right ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->message_count }} messages</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ $session->created_at->format('M d, Y') }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $session->last_message_at?->diffForHumans() ?? $session->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                @if($session->action_items && count($session->action_items) > 0)
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Action Items:</p>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        @foreach(array_slice($session->action_items, 0, 3) as $item)
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            {{ is_array($item) ? ($item['task'] ?? $item) : $item }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </a>
            @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <span class="text-3xl">💬</span>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No sessions yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Start your first coaching session to see it here</p>
                <a href="{{ route('career-coach.index') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Start a Session
                </a>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($sessions->hasPages())
        <div class="mt-8">
            {{ $sessions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
