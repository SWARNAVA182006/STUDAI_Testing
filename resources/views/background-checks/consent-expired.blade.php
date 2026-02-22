@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mx-auto h-16 w-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
            <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        
        <h2 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">Consent Request Expired</h2>
        
        <p class="mt-4 text-gray-600 dark:text-gray-400">
            This background check authorization request has expired. Consent requests are valid for 7 days from the date they are sent.
        </p>
        
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">What should I do?</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Please contact {{ $backgroundCheck->company->name ?? 'the employer' }} to request a new authorization link. They can resend the consent request from their dashboard.
            </p>
        </div>
        
        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            If you have questions, please reach out to the employer directly.
        </p>
    </div>
</div>
@endsection
