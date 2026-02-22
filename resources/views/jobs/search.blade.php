@extends('layouts.dashboard')

@section('title', 'Job Search')
@section('page-title', 'Find Jobs')
@section('page-description', 'Discover opportunities matched by AI')

@section('content')
<div x-data="{ 
    selectedJob: null,
    showFilters: true,
    activeFilters: 0
}" class="h-[calc(100vh-180px)]">
    {{-- Search Bar --}}
    <form method="GET" action="{{ route('jobs.search') }}" class="mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            {{-- Main Search --}}
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       name="keyword" 
                       value="{{ request('keyword') }}" 
                       placeholder="Job title, skills, or company..." 
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-soft focus:ring-2 focus:ring-studai-blue-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400 transition-all">
            </div>
            {{-- Location Search --}}
            <div class="lg:w-64 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                </div>
                <select name="location" class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-soft focus:ring-2 focus:ring-studai-blue-500 focus:border-transparent text-gray-900 dark:text-white appearance-none cursor-pointer transition-all">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location }}" {{ request('location') == $location ? 'selected' : '' }}>{{ $location }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Search Button --}}
            <x-studai.button type="submit" variant="primary" class="lg:w-auto">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </x-studai.button>
        </div>
    </form>

    {{-- 3-Column Layout --}}
    <div class="flex gap-6 h-full">
        {{-- Left: Filters Panel --}}
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <x-studai.card class="sticky top-0 h-fit max-h-[calc(100vh-250px)] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Filters</h3>
                    @if(request()->hasAny(['keyword', 'location', 'experience_level', 'job_type', 'salary_min']))
                        <a href="{{ route('jobs.search') }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700">Clear all</a>
                    @endif
                </div>

                <form method="GET" action="{{ route('jobs.search') }}" class="space-y-6">
                    <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                    <input type="hidden" name="location" value="{{ request('location') }}">

                    {{-- Experience Level --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Experience Level</h4>
                        <div class="space-y-2">
                            @foreach($experienceLevels as $level)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="experience_level[]" value="{{ $level }}" 
                                           {{ in_array($level, (array)request('experience_level')) ? 'checked' : '' }}
                                           class="w-4 h-4 text-studai-blue-600 border-gray-300 rounded focus:ring-studai-blue-500">
                                    <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ ucfirst($level) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Job Type --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Job Type</h4>
                        <div class="space-y-2">
                            @foreach($jobTypes as $type)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="job_type[]" value="{{ $type }}"
                                           {{ in_array($type, (array)request('job_type')) ? 'checked' : '' }}
                                           class="w-4 h-4 text-studai-blue-600 border-gray-300 rounded focus:ring-studai-blue-500">
                                    <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ ucwords(str_replace('-', ' ', $type)) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Salary Range --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Salary Range</h4>
                        <div class="space-y-3">
                            <x-studai.input type="number" name="salary_min" :value="request('salary_min')" placeholder="Min (LPA)" size="sm" />
                            <x-studai.input type="number" name="salary_max" :value="request('salary_max')" placeholder="Max (LPA)" size="sm" />
                        </div>
                    </div>

                    {{-- Work Mode --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Work Mode</h4>
                        <div class="flex flex-wrap gap-2">
                            <label class="cursor-pointer">
                                <input type="checkbox" name="remote" value="1" class="sr-only peer" {{ request('remote') ? 'checked' : '' }}>
                                <span class="inline-flex px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-full peer-checked:bg-studai-blue-50 peer-checked:border-studai-blue-500 peer-checked:text-studai-blue-600 dark:peer-checked:bg-studai-blue-900/20 transition-all">Remote</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="hybrid" value="1" class="sr-only peer" {{ request('hybrid') ? 'checked' : '' }}>
                                <span class="inline-flex px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-full peer-checked:bg-studai-blue-50 peer-checked:border-studai-blue-500 peer-checked:text-studai-blue-600 dark:peer-checked:bg-studai-blue-900/20 transition-all">Hybrid</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="onsite" value="1" class="sr-only peer" {{ request('onsite') ? 'checked' : '' }}>
                                <span class="inline-flex px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-full peer-checked:bg-studai-blue-50 peer-checked:border-studai-blue-500 peer-checked:text-studai-blue-600 dark:peer-checked:bg-studai-blue-900/20 transition-all">On-site</span>
                            </label>
                        </div>
                    </div>

                    <x-studai.button type="submit" variant="primary" class="w-full">Apply Filters</x-studai.button>
                </form>
            </x-studai.card>
        </aside>

        {{-- Center: Job List --}}
        <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
            {{-- Results Header --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $jobs->total() }}</span> jobs found
                        @if(request('keyword'))
                            for "<span class="font-medium">{{ request('keyword') }}</span>"
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select name="sort" form="sort-form" onchange="this.form.submit()" class="text-sm bg-transparent border-0 text-gray-600 dark:text-gray-400 focus:ring-0 cursor-pointer pr-8">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="salary_high" {{ request('sort') == 'salary_high' ? 'selected' : '' }}>Highest Salary</option>
                        <option value="relevant" {{ request('sort') == 'relevant' ? 'selected' : '' }}>Most Relevant</option>
                    </select>
                    <form id="sort-form" method="GET" action="{{ route('jobs.search') }}" class="hidden">
                        @foreach(request()->except('sort') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                    </form>
                </div>
            </div>

            @if($jobs->isEmpty())
                {{-- Empty State --}}
                <x-studai.card class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No jobs found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Try adjusting your search or filters</p>
                    <x-studai.button href="{{ route('jobs.search') }}" variant="primary">
                        View All Jobs
                    </x-studai.button>
                </x-studai.card>
            @else
                {{-- Job Cards List --}}
                <div class="space-y-3">
                    @foreach($jobs as $job)
                        <div @click="selectedJob = {{ $job->id }}" 
                             :class="{ 'ring-2 ring-studai-blue-500 border-studai-blue-500': selectedJob === {{ $job->id }} }"
                             class="group cursor-pointer bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:border-studai-blue-300 dark:hover:border-studai-blue-700 hover:shadow-soft transition-all duration-200">
                            <div class="flex items-start gap-4">
                                {{-- Company Logo --}}
                                <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-soft">
                                    {{ substr($job->company_name, 0, 1) }}
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-studai-blue-600 transition-colors line-clamp-1">
                                                {{ $job->title }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $job->company_name }}</p>
                                        </div>
                                        @auth
                                            <x-studai.ai-score :score="rand(75, 98)" size="sm" />
                                        @endauth
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 mt-3">
                                        <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            </svg>
                                            {{ $job->location }}
                                        </span>
                                        <span class="text-gray-300 dark:text-gray-600">•</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('-', ' ', $job->job_type)) }}</span>
                                        @if($job->salary_min && $job->salary_max)
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                            <span class="text-xs font-medium text-green-600 dark:text-green-400">
                                                ₹{{ number_format($job->salary_min / 100000, 1) }}L - ₹{{ number_format($job->salary_max / 100000, 1) }}L
                                            </span>
                                        @endif
                                    </div>

                                    @if($job->required_skills)
                                        <div class="flex flex-wrap gap-1.5 mt-3">
                                            @foreach(array_slice(json_decode($job->required_skills, true) ?? [], 0, 4) as $skill)
                                                <x-studai.chip size="xs">{{ $skill }}</x-studai.chip>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50 dark:border-gray-700">
                                        <span class="text-xs text-gray-400">{{ $job->created_at->diffForHumans() }}</span>
                                        @auth
                                            <button onclick="event.stopPropagation(); toggleSave({{ $job->id }})" 
                                                    id="save-btn-{{ $job->id }}"
                                                    class="p-1.5 text-gray-400 hover:text-studai-blue-600 hover:bg-studai-blue-50 dark:hover:bg-studai-blue-900/20 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                                </svg>
                                            </button>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6 pb-4">
                    {{ $jobs->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

        {{-- Right: Job Detail Panel --}}
        <aside class="hidden xl:block w-96 flex-shrink-0">
            <div x-show="!selectedJob" class="h-full flex items-center justify-center">
                <x-studai.card class="text-center py-12 w-full">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Select a job</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Click on a job card to see details here</p>
                </x-studai.card>
            </div>

            <template x-if="selectedJob">
                <x-studai.card class="sticky top-0 h-fit max-h-[calc(100vh-250px)] overflow-y-auto">
                    {{-- This would be dynamically loaded via AJAX/Livewire in production --}}
                    <div class="space-y-6">
                        {{-- Header --}}
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">
                                G
                            </div>
                            <div class="flex-1">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Senior Software Engineer</h2>
                                <p class="text-gray-600 dark:text-gray-400">Google</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <x-studai.badge color="green">Active</x-studai.badge>
                                    <span class="text-xs text-gray-400">Posted 2 days ago</span>
                                </div>
                            </div>
                        </div>

                        {{-- AI Match --}}
                        <div class="p-4 bg-gradient-to-r from-studai-blue-50 to-purple-50 dark:from-studai-blue-900/20 dark:to-purple-900/20 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">AI Match Score</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Based on your profile & skills</p>
                                </div>
                                <x-studai.ai-score :score="96" size="lg" />
                            </div>
                        </div>

                        {{-- Quick Info --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Salary</p>
                                <p class="font-semibold text-gray-900 dark:text-white">$180k - $250k</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Location</p>
                                <p class="font-semibold text-gray-900 dark:text-white">Remote</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Experience</p>
                                <p class="font-semibold text-gray-900 dark:text-white">5+ years</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Type</p>
                                <p class="font-semibold text-gray-900 dark:text-white">Full-time</p>
                            </div>
                        </div>

                        {{-- Required Skills --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Required Skills</h4>
                            <div class="flex flex-wrap gap-2">
                                <x-studai.chip size="sm" color="green">Python ✓</x-studai.chip>
                                <x-studai.chip size="sm" color="green">React ✓</x-studai.chip>
                                <x-studai.chip size="sm" color="green">Node.js ✓</x-studai.chip>
                                <x-studai.chip size="sm" color="amber">Kubernetes</x-studai.chip>
                                <x-studai.chip size="sm" color="gray">GraphQL</x-studai.chip>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <x-studai.button variant="primary" class="flex-1">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Apply Now
                            </x-studai.button>
                            <x-studai.button variant="secondary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                            </x-studai.button>
                        </div>

                        {{-- View Full Details Link --}}
                        <a href="#" class="block text-center text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                            View Full Job Description →
                        </a>
                    </div>
                </x-studai.card>
            </template>
        </aside>
    </div>
</div>

@auth
<script>
function toggleSave(jobId) {
    fetch(`/api/jobs/${jobId}/toggle-save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const btn = document.getElementById(`save-btn-${jobId}`);
        if (data.saved) {
            btn.classList.add('text-studai-blue-600', 'bg-studai-blue-50');
            btn.querySelector('svg').setAttribute('fill', 'currentColor');
        } else {
            btn.classList.remove('text-studai-blue-600', 'bg-studai-blue-50');
            btn.querySelector('svg').setAttribute('fill', 'none');
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endauth

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
@endsection
