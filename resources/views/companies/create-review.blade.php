@extends('layouts.app')

@section('title', 'Write a Review for ' . $company->name . ' | StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('companies.show', $company) }}" 
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to {{ $company->name }}
            </a>
            
            <div class="flex items-center gap-4">
                @if($company->logo_url)
                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="w-16 h-16 rounded-lg object-contain bg-white p-2">
                @else
                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                        <span class="text-white text-xl font-bold">{{ substr($company->name, 0, 2) }}</span>
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Write a Review</h1>
                    <p class="text-gray-600 dark:text-gray-400">Share your experience at {{ $company->name }}</p>
                </div>
            </div>
        </div>

        {{-- Review Form --}}
        <livewire:reviews.submit-review :company="$company" />

        {{-- Guidelines --}}
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Review Guidelines
            </h3>
            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Be honest and specific about your experience
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Include both positives and areas for improvement
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Your review is anonymous - your name won't be shown
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Don't include personal information or confidential data
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Avoid offensive language or personal attacks
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
