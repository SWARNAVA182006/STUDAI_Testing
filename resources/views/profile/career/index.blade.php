@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Your Career Profile</h1>
            <p class="text-lg text-gray-600">Manage your professional information and improve your job matches</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Profile Card --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $profile->headline ?? 'Complete Your Profile' }}</h2>
                            @if($profile->exists)
                                <p class="text-gray-600 mt-1">{{ $profile->current_location }}</p>
                            @endif
                        </div>
                        <a href="{{ route('profile.career.builder') }}" 
                           class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium shadow-md transition-all">
                            {{ $profile->exists ? 'Edit Profile' : 'Build Profile' }}
                        </a>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Profile Completion</span>
                            <span class="text-sm font-bold text-primary-600">{{ $completion }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-primary-500 to-secondary-500 h-3 rounded-full transition-all duration-500" 
                                 style="width: {{ $completion }}%"></div>
                        </div>
                        @if($completion < 100)
                            <p class="text-sm text-gray-500 mt-2">Complete your profile to unlock AI-powered job matching</p>
                        @endif
                    </div>

                    @if($profile->exists)
                        {{-- Summary --}}
                        @if($profile->summary)
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">About</h3>
                                <p class="text-gray-700">{{ $profile->summary }}</p>
                            </div>
                        @endif

                        {{-- Skills --}}
                        @if(!empty($profile->skills))
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Skills</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($profile->skills as $skill)
                                        <span class="px-4 py-2 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                                            {{ $skill['name'] }}
                                            @if(isset($skill['proficiency']))
                                                <span class="text-xs text-primary-600">• {{ ucfirst($skill['proficiency']) }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Experience --}}
                        @if(!empty($profile->experience))
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Experience</h3>
                                <div class="space-y-4">
                                    @foreach($profile->experience as $exp)
                                        <div class="border-l-4 border-primary-500 pl-4">
                                            <h4 class="font-semibold text-gray-900">{{ $exp['title'] }}</h4>
                                            <p class="text-gray-600">{{ $exp['company'] }}</p>
                                            <p class="text-sm text-gray-500">
                                                {{ $exp['start_date'] ?? '' }} - {{ $exp['end_date'] ?? 'Present' }}
                                            </p>
                                            @if(isset($exp['description']))
                                                <p class="text-gray-700 mt-2">{{ $exp['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Education --}}
                        @if(!empty($profile->education))
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Education</h3>
                                <div class="space-y-4">
                                    @foreach($profile->education as $edu)
                                        <div class="border-l-4 border-secondary-500 pl-4">
                                            <h4 class="font-semibold text-gray-900">{{ $edu['degree'] }}</h4>
                                            <p class="text-gray-600">{{ $edu['institution'] }}</p>
                                            <p class="text-sm text-gray-500">{{ $edu['field'] ?? '' }} • {{ $edu['graduation_year'] ?? '' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Empty State --}}
                        <div class="text-center py-12">
                            <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Profile Yet</h3>
                            <p class="text-gray-600 mb-6">Build your career profile to unlock AI-powered job matching</p>
                            <a href="{{ route('profile.career.builder') }}" 
                               class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white text-lg font-semibold rounded-lg hover:from-primary-700 hover:to-primary-800 shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Build Your Profile
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                {{-- Quick Actions --}}
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('profile.career.builder') }}" 
                           class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Edit Profile</p>
                                <p class="text-xs text-gray-500">Update your information</p>
                            </div>
                        </a>
                        
                        <a href="#" 
                           class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Upload Resume</p>
                                <p class="text-xs text-gray-500">Update from resume</p>
                            </div>
                        </a>
                        
                        <a href="#" 
                           class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">AI Suggestions</p>
                                <p class="text-xs text-gray-500">Get profile tips</p>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- Profile Strength --}}
                @if($profile->exists)
                    <div class="bg-gradient-to-br from-primary-50 to-secondary-50 rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile Strength</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Headline</span>
                                <span class="text-xs font-semibold {{ $profile->headline ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $profile->headline ? '✓' : '○' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Summary</span>
                                <span class="text-xs font-semibold {{ $profile->summary ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $profile->summary ? '✓' : '○' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Experience</span>
                                <span class="text-xs font-semibold {{ !empty($profile->experience) ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ !empty($profile->experience) ? '✓' : '○' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Education</span>
                                <span class="text-xs font-semibold {{ !empty($profile->education) ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ !empty($profile->education) ? '✓' : '○' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Skills</span>
                                <span class="text-xs font-semibold {{ !empty($profile->skills) ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ !empty($profile->skills) ? '✓' : '○' }}
                                </span>
                            </div>
                        </div>

                        @if($completion < 100)
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">Complete {{ 100 - $completion }}% more to maximize your visibility</p>
                            </div>
                        @else
                            <div class="mt-4 pt-4 border-t border-primary-200">
                                <p class="text-sm font-semibold text-green-700">🎉 Perfect! Your profile is complete</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
