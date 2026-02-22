@extends('layouts.app')

@section('title', $company->name . ' Interview Questions | StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Company Header --}}
    @include('companies.partials.header', ['company' => $company, 'activeTab' => 'interviews'])

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Interviews List --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Interview Overview --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Interview Experience at {{ $company->name }}
                    </h2>
                    
                    @php
                        $interviewStats = $company->interviewExperiences()
                            ->where('status', 'approved')
                            ->selectRaw('
                                COUNT(*) as total,
                                SUM(CASE WHEN outcome = "got_offer" THEN 1 ELSE 0 END) as got_offer,
                                SUM(CASE WHEN outcome = "declined_offer" THEN 1 ELSE 0 END) as declined,
                                SUM(CASE WHEN outcome = "no_offer" THEN 1 ELSE 0 END) as no_offer,
                                SUM(CASE WHEN outcome = "pending" THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN experience = "positive" THEN 1 ELSE 0 END) as positive,
                                SUM(CASE WHEN experience = "neutral" THEN 1 ELSE 0 END) as neutral,
                                SUM(CASE WHEN experience = "negative" THEN 1 ELSE 0 END) as negative
                            ')
                            ->first();
                        
                        $totalWithExperience = ($interviewStats->positive ?? 0) + ($interviewStats->neutral ?? 0) + ($interviewStats->negative ?? 0);
                        $positivePercent = $totalWithExperience > 0 ? round(($interviewStats->positive / $totalWithExperience) * 100) : 0;
                        
                        // Calculate average difficulty from enum values
                        $difficultyMap = ['easy' => 1, 'average' => 2, 'difficult' => 3, 'very_difficult' => 4];
                        $difficultiesRaw = $company->interviewExperiences()
                            ->where('status', 'approved')
                            ->whereNotNull('difficulty')
                            ->pluck('difficulty');
                        $avgDifficulty = $difficultiesRaw->count() > 0 
                            ? $difficultiesRaw->map(fn($d) => $difficultyMap[$d] ?? 0)->avg() 
                            : 0;
                    @endphp
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                {{ $interviewStats->total ?? 0 }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Interviews Shared</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $positivePercent }}%
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Positive Experience</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {{ number_format($avgDifficulty, 1) }}/4
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Avg Difficulty</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            @php
                                $totalOutcomes = ($interviewStats->got_offer ?? 0) + ($interviewStats->no_offer ?? 0) + ($interviewStats->declined ?? 0);
                                $offerRate = $totalOutcomes > 0 ? round((($interviewStats->got_offer + $interviewStats->declined) / $totalOutcomes) * 100) : 0;
                            @endphp
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $offerRate }}%
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Got Offer</p>
                        </div>
                    </div>

                    {{-- Experience Breakdown --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-2">
                                <div class="h-full bg-green-500 rounded-full" style="width: {{ $totalWithExperience > 0 ? ($interviewStats->positive / $totalWithExperience) * 100 : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ $interviewStats->positive ?? 0 }}</span> Positive
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-2">
                                <div class="h-full bg-gray-400 rounded-full" style="width: {{ $totalWithExperience > 0 ? ($interviewStats->neutral / $totalWithExperience) * 100 : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <span class="text-gray-600 dark:text-gray-400 font-medium">{{ $interviewStats->neutral ?? 0 }}</span> Neutral
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-2">
                                <div class="h-full bg-red-500 rounded-full" style="width: {{ $totalWithExperience > 0 ? ($interviewStats->negative / $totalWithExperience) * 100 : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <span class="text-red-600 dark:text-red-400 font-medium">{{ $interviewStats->negative ?? 0 }}</span> Negative
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Interview Filters --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48">
                            <input type="text" 
                                   placeholder="Search positions or questions..." 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500">
                        </div>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">All Outcomes</option>
                            <option value="accepted">Got Offer - Accepted</option>
                            <option value="declined">Got Offer - Declined</option>
                            <option value="rejected">No Offer</option>
                            <option value="no_response">No Response</option>
                        </select>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">All Experiences</option>
                            <option value="positive">Positive</option>
                            <option value="neutral">Neutral</option>
                            <option value="negative">Negative</option>
                        </select>
                    </div>
                </div>

                {{-- Interview Experiences List --}}
                <div class="space-y-4">
                    @forelse($interviews as $interview)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                            {{-- Header --}}
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $interview->job_title }} Interview
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        @if($interview->department)
                                            <span>{{ $interview->department }}</span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                        @endif
                                        @if($interview->location)
                                            <span>{{ $interview->location }}</span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                        @endif
                                        <span>{{ $interview->interview_date ? $interview->interview_date->format('M Y') : 'Date not specified' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    {{-- Experience Badge --}}
                                    @switch($interview->experience)
                                        @case('positive')
                                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-medium">
                                                👍 Positive
                                            </span>
                                            @break
                                        @case('neutral')
                                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm font-medium">
                                                😐 Neutral
                                            </span>
                                            @break
                                        @case('negative')
                                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm font-medium">
                                                👎 Negative
                                            </span>
                                            @break
                                    @endswitch
                                    
                                    {{-- Outcome Badge --}}
                                    @switch($interview->outcome)
                                        @case('got_offer')
                                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-medium">
                                                ✓ Got Offer{{ $interview->accepted_offer ? ' - Accepted' : '' }}
                                            </span>
                                            @break
                                        @case('declined_offer')
                                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm font-medium">
                                                Declined Offer
                                            </span>
                                            @break
                                        @case('no_offer')
                                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm font-medium">
                                                No Offer
                                            </span>
                                            @break
                                        @case('pending')
                                            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 rounded-full text-sm font-medium">
                                                Pending
                                            </span>
                                            @break
                                        @case('withdrew')
                                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium">
                                                Withdrew
                                            </span>
                                            @break
                                    @endswitch
                                </div>
                            </div>

                            {{-- Difficulty & Duration --}}
                            <div class="flex flex-wrap items-center gap-4 mb-4 text-sm">
                                @if($interview->difficulty)
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600 dark:text-gray-400">Difficulty:</span>
                                        <span class="px-2 py-1 rounded text-xs font-medium
                                            @switch($interview->difficulty)
                                                @case('easy') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 @break
                                                @case('average') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300 @break
                                                @case('difficult') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 @break
                                                @case('very_difficult') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 @break
                                            @endswitch
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $interview->difficulty)) }}
                                        </span>
                                    </div>
                                @endif
                                @if($interview->interview_duration)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $interview->interview_duration)) }}</span>
                                    </div>
                                @endif
                                @if($interview->application_method)
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs">
                                        {{ ucfirst(str_replace('_', ' ', $interview->application_method)) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Application & Process --}}
                            <div class="space-y-3 mb-4">
                                @if($interview->application_source)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-gray-900 dark:text-white">How I Applied:</span>
                                        {{ ucfirst(str_replace('_', ' ', $interview->application_source)) }}
                                    </p>
                                @endif
                                
                                @if($interview->interview_process)
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">Interview Process:</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $interview->interview_process }}</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Interview Questions --}}
                            @if($interview->interview_questions && count($interview->interview_questions) > 0)
                                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Interview Questions
                                    </p>
                                    <ul class="space-y-2">
                                        @foreach(array_slice($interview->interview_questions, 0, 5) as $question)
                                            <li class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                                <span class="text-primary-500 mt-1">•</span>
                                                {{ $question }}
                                            </li>
                                        @endforeach
                                        @if(count($interview->interview_questions) > 5)
                                            <li class="text-sm text-primary-600 dark:text-primary-400 cursor-pointer hover:underline">
                                                +{{ count($interview->interview_questions) - 5 }} more questions
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            {{-- Tips --}}
                            @if($interview->tips_for_interview)
                                <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-300 mb-1 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                        Tips for Candidates
                                    </p>
                                    <p class="text-sm text-green-700 dark:text-green-400">{{ $interview->tips_for_interview }}</p>
                                </div>
                            @endif

                            {{-- Footer --}}
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    Shared {{ $interview->created_at->diffForHumans() }}
                                </span>
                                <div class="flex items-center gap-4">
                                    <button class="flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                        </svg>
                                        <span>Helpful ({{ $interview->helpful_votes ?? 0 }})</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Interview Experiences Yet</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Be the first to share your interview experience at {{ $company->name }}!
                            </p>
                            <a href="{{ route('companies.interviews.create', $company) }}" 
                               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                Share Your Experience
                            </a>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($interviews->hasPages())
                    <div class="mt-6">
                        {{ $interviews->links() }}
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Share Interview CTA --}}
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-2">Share Your Interview</h3>
                    <p class="text-blue-100 text-sm mb-4">
                        Help others prepare! Share your interview questions and experience anonymously.
                    </p>
                    <a href="{{ route('companies.interviews.create', $company) }}" 
                       class="block w-full text-center px-4 py-2 bg-white text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-colors">
                        Add Interview
                    </a>
                </div>

                {{-- Interview by Position --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Popular Positions</h3>
                    <div class="space-y-3">
                        @php
                            $popularPositions = $company->interviewExperiences()
                                ->where('status', 'approved')
                                ->selectRaw('job_title, COUNT(*) as count')
                                ->groupBy('job_title')
                                ->orderByDesc('count')
                                ->limit(8)
                                ->get();
                        @endphp
                        @forelse($popularPositions as $position)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $position->job_title }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">
                                    {{ $position->count }} {{ Str::plural('interview', $position->count) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No interview data available yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Application Sources --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">How People Applied</h3>
                    @php
                        $applicationSources = $company->interviewExperiences()
                            ->where('status', 'approved')
                            ->whereNotNull('application_source')
                            ->selectRaw('application_source, COUNT(*) as count')
                            ->groupBy('application_source')
                            ->orderByDesc('count')
                            ->get();
                        $totalApplications = $applicationSources->sum('count');
                    @endphp
                    <div class="space-y-3">
                        @forelse($applicationSources as $source)
                            @php
                                $percent = $totalApplications > 0 ? round(($source->count / $totalApplications) * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $source->application_source)) }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $percent }}%</span>
                                </div>
                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No application data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
