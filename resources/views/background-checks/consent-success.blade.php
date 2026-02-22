@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mx-auto h-16 w-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
            <svg class="h-8 w-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h2 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">Consent Received</h2>
        
        <p class="mt-4 text-gray-600 dark:text-gray-400">
            Thank you for authorizing the background check. {{ $backgroundCheck->company->name ?? 'The employer' }} has been notified and the verification process will begin shortly.
        </p>
        
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">What happens next?</h3>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 text-left">
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-indigo-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    The background check provider will begin verifying your information
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-indigo-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    You may be contacted if additional information is needed
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-indigo-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    The process typically takes {{ $backgroundCheck->estimated_completion_days ?? 3-5 }} business days
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-indigo-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    You can request a copy of the completed report
                </li>
            </ul>
        </div>
        
        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            You can close this window now.
        </p>
    </div>
</div>
@endsection
