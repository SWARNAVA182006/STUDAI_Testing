{{-- Resume List Page --}}
@extends('layouts.dashboard')

@section('title', 'Resume Builder')
@section('page-title', 'Resume Builder')
@section('page-description', 'Create ATS-optimized resumes with AI')

@section('content')
<div class="space-y-6">
    {{-- Header Actions --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">My Resumes</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $resumes->count() }} resume{{ $resumes->count() !== 1 ? 's' : '' }} created</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <x-studai.button href="{{ route('resume.create') }}" variant="primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Resume
            </x-studai.button>
        </div>
    </div>

    @if($resumes->isEmpty())
        {{-- Empty State --}}
        <x-studai.card class="text-center py-16">
            <div class="w-20 h-20 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-3xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Create Your First Resume</h2>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-8">
                Build an ATS-optimized resume with AI assistance. Our smart builder helps you create professional resumes that get noticed.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <x-studai.button href="{{ route('resume.create') }}" variant="primary" size="lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Create with AI
                </x-studai.button>
                <x-studai.button variant="secondary" size="lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload Existing
                </x-studai.button>
            </div>
        </x-studai.card>
    @else
        {{-- Resume Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($resumes as $resume)
                <x-studai.card variant="interactive" class="group overflow-hidden">
                    {{-- Preview Thumbnail --}}
                    <div class="h-40 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 -mx-6 -mt-6 mb-4 relative flex items-center justify-center">
                        <div class="w-24 h-32 bg-white dark:bg-gray-800 rounded-lg shadow-lg flex items-center justify-center border border-gray-200 dark:border-gray-600">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        @if($resume->is_default)
                            <div class="absolute top-3 right-3">
                                <x-studai.badge color="green" size="sm">Default</x-studai.badge>
                            </div>
                        @endif
                        {{-- Quick Actions Overlay --}}
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="{{ route('resume.edit', $resume) }}" class="p-2 bg-white rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <a href="{{ route('resume.preview', $resume) }}" class="p-2 bg-white rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('resume.export.pdf', $resume) }}" class="p-2 bg-white rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Resume Info --}}
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate mb-1">{{ $resume->title }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $resume->full_name }}</p>

                        {{-- ATS Score --}}
                        @if($resume->ats_score)
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">ATS Score</span>
                                        <span class="text-xs font-semibold 
                                            @if($resume->ats_score === 'excellent') text-green-600
                                            @elseif($resume->ats_score === 'good') text-studai-blue-600
                                            @elseif($resume->ats_score === 'fair') text-amber-600
                                            @else text-red-600 @endif">
                                            {{ ucfirst($resume->ats_score) }}
                                        </span>
                                    </div>
                                    <x-studai.progress 
                                        :value="$resume->ats_score === 'excellent' ? 95 : ($resume->ats_score === 'good' ? 75 : ($resume->ats_score === 'fair' ? 50 : 25))"
                                        :color="$resume->ats_score === 'excellent' ? 'green' : ($resume->ats_score === 'good' ? 'blue' : ($resume->ats_score === 'fair' ? 'amber' : 'red'))"
                                        size="sm"
                                    />
                                </div>
                            </div>
                        @endif

                        {{-- Completion Progress --}}
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Completion</span>
                                <span class="text-xs font-semibold text-purple-600">{{ $resume->getCompletionPercentage() }}%</span>
                            </div>
                            <x-studai.progress :value="$resume->getCompletionPercentage()" color="purple" size="sm" />
                        </div>

                        {{-- Stats Row --}}
                        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mb-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ $resume->view_count }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                {{ $resume->download_count }}
                            </span>
                            <span class="text-gray-300 dark:text-gray-600">•</span>
                            <span>{{ $resume->updated_at->diffForHumans() }}</span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2" x-data="{ open: false }">
                            <x-studai.button href="{{ route('resume.edit', $resume) }}" variant="primary" size="sm" class="flex-1">
                                Edit
                            </x-studai.button>
                            <x-studai.button href="{{ route('resume.preview', $resume) }}" variant="secondary" size="sm" class="flex-1">
                                Preview
                            </x-studai.button>
                            <div class="relative">
                                <x-studai.button @click="open = !open" variant="ghost" size="sm" class="px-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                    </svg>
                                </x-studai.button>
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-10">
                                    <a href="{{ route('resume.export.pdf', $resume) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download PDF
                                    </a>
                                    <a href="{{ route('resume.export.docx', $resume) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Download DOCX
                                    </a>
                                    <form action="{{ route('resume.duplicate', $resume) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            Duplicate
                                        </button>
                                    </form>
                                    <form action="{{ route('resume.set-default', $resume) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                            Set as Default
                                        </button>
                                    </form>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <form action="{{ route('resume.destroy', $resume) }}" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to delete this resume?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-studai.card>
            @endforeach

            {{-- Create New Card --}}
            <a href="{{ route('resume.create') }}" class="group flex flex-col items-center justify-center min-h-[320px] border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl hover:border-studai-blue-400 dark:hover:border-studai-blue-500 hover:bg-studai-blue-50/50 dark:hover:bg-studai-blue-900/10 transition-all cursor-pointer">
                <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-studai-blue-100 dark:group-hover:bg-studai-blue-900/30 group-hover:scale-110 transition-all">
                    <svg class="w-7 h-7 text-gray-400 group-hover:text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-studai-blue-600">Create New Resume</span>
            </a>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $resumes->links() }}
        </div>
    @endif
</div>
@endsection
