@extends('layouts.app')

@section('title', 'Share Interview Experience at ' . $company->name . ' | StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('companies.interviews', $company) }}" 
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to {{ $company->name }} Interviews
            </a>
            
            <div class="flex items-center gap-4">
                @if($company->logo_url)
                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="w-16 h-16 rounded-lg object-contain bg-white p-2">
                @else
                    <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <span class="text-white text-xl font-bold">{{ substr($company->name, 0, 2) }}</span>
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Share Interview Experience</h1>
                    <p class="text-gray-600 dark:text-gray-400">Help others prepare for interviews at {{ $company->name }}</p>
                </div>
            </div>
        </div>

        {{-- Interview Form --}}
        <livewire:reviews.submit-interview :company="$company" />

        {{-- Guidelines --}}
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Tips for a Helpful Interview Review
            </h3>
            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Describe the interview process step by step
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Share specific questions you were asked (as many as you remember)
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Include tips that would help someone prepare
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Mention the interview format (phone, video, in-person, technical, behavioral)
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Don't include names of interviewers or confidential information
                </li>
            </ul>
        </div>

        {{-- Stats --}}
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Contribution Matters</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div class="p-4">
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format(App\Models\InterviewExperience::where('status', 'approved')->count()) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Interview experiences shared</p>
                </div>
                <div class="p-4">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format(App\Models\Company::has('interviewExperiences')->count()) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Companies with interview data</p>
                </div>
                <div class="p-4">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        @php
                            $totalQuestions = App\Models\InterviewExperience::where('status', 'approved')
                                ->whereNotNull('interview_questions')
                                ->get()
                                ->sum(fn($exp) => is_array($exp->interview_questions) ? count($exp->interview_questions) : 0);
                        @endphp
                        {{ number_format($totalQuestions) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Interview questions collected</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
