@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mx-auto h-16 w-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <h2 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">Consent Already Provided</h2>
        
        <p class="mt-4 text-gray-600 dark:text-gray-400">
            You have already authorized this background check. The verification process is {{ $backgroundCheck->isInProgress() ? 'currently in progress' : ($backgroundCheck->isCompleted() ? 'complete' : 'pending') }}.
        </p>
        
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between text-sm mb-3">
                <span class="text-gray-500 dark:text-gray-400">Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($backgroundCheck->isCompleted()) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                    @elseif($backgroundCheck->isInProgress()) bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                    @endif
                ">
                    {{ ucfirst(str_replace('_', ' ', $backgroundCheck->status)) }}
                </span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Consent Given</span>
                <span class="text-gray-900 dark:text-white">{{ $backgroundCheck->consent_received_at->format('M d, Y g:i A') }}</span>
            </div>
        </div>
        
        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            You can close this window. No further action is needed.
        </p>
    </div>
</div>
@endsection
