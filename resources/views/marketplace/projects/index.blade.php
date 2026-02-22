@extends('layouts.app')

@section('title', 'Browse Projects - Talent Marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Browse Projects</h1>
                <p class="text-gray-600 mt-1">Find exciting freelance opportunities</p>
            </div>
            @auth
                <a href="{{ route('marketplace.employer.create-project') }}" 
                   class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Post a Project
                </a>
            @endauth
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:w-1/4">
                <form action="{{ route('marketplace.projects') }}" method="GET" class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Filters</h3>

                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" 
                               placeholder="Keywords..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Category -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Categories</option>
                            @foreach($categories ?? ['web-development' => 'Web Development', 'mobile-apps' => 'Mobile Apps', 'ui-ux-design' => 'UI/UX Design', 'data-science' => 'Data Science', 'content-writing' => 'Content Writing', 'digital-marketing' => 'Digital Marketing'] as $slug => $name)
                                <option value="{{ $slug }}" {{ request('category') == $slug ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Budget Range -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Budget Range</label>
                        <div class="flex gap-2">
                            <input type="number" name="budget_min" value="{{ request('budget_min') }}" 
                                   placeholder="Min" 
                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <input type="number" name="budget_max" value="{{ request('budget_max') }}" 
                                   placeholder="Max"
                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Project Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Project Type</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="project_type" value="" {{ !request('project_type') ? 'checked' : '' }} class="text-indigo-600">
                                <span class="ml-2 text-gray-700">All Types</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="project_type" value="fixed_price" {{ request('project_type') == 'fixed_price' ? 'checked' : '' }} class="text-indigo-600">
                                <span class="ml-2 text-gray-700">Fixed Price</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="project_type" value="hourly" {{ request('project_type') == 'hourly' ? 'checked' : '' }} class="text-indigo-600">
                                <span class="ml-2 text-gray-700">Hourly</span>
                            </label>
                        </div>
                    </div>

                    <!-- Experience Level -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level</label>
                        <select name="experience_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Any Level</option>
                            <option value="entry" {{ request('experience_level') == 'entry' ? 'selected' : '' }}>Entry Level</option>
                            <option value="intermediate" {{ request('experience_level') == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                            <option value="expert" {{ request('experience_level') == 'expert' ? 'selected' : '' }}>Expert</option>
                        </select>
                    </div>

                    <!-- Remote Only -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="remote_only" value="1" {{ request('remote_only') ? 'checked' : '' }} class="text-indigo-600 rounded">
                            <span class="ml-2 text-gray-700">Remote Only</span>
                        </label>
                    </div>

                    <!-- Sort -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="budget_high" {{ request('sort') == 'budget_high' ? 'selected' : '' }}>Highest Budget</option>
                            <option value="budget_low" {{ request('sort') == 'budget_low' ? 'selected' : '' }}>Lowest Budget</option>
                            <option value="proposals" {{ request('sort') == 'proposals' ? 'selected' : '' }}>Fewest Proposals</option>
                            <option value="deadline" {{ request('sort') == 'deadline' ? 'selected' : '' }}>Deadline</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                            Apply Filters
                        </button>
                        <a href="{{ route('marketplace.projects') }}" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Projects List -->
            <div class="lg:w-3/4">
                <!-- Results Count -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        {{ $projects->total() ?? 0 }} projects found
                    </p>
                </div>

                <!-- Projects Grid -->
                <div class="space-y-6">
                    @forelse($projects ?? [] as $project)
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-1">
                                    <!-- Tags -->
                                    <div class="flex flex-wrap items-center gap-2 mb-3">
                                        @if($project->is_urgent)
                                            <span class="px-2.5 py-0.5 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                                🔥 Urgent
                                            </span>
                                        @endif
                                        @if($project->is_featured)
                                            <span class="px-2.5 py-0.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                                ⭐ Featured
                                            </span>
                                        @endif
                                        <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full">
                                            {{ ucwords(str_replace('-', ' ', $project->category)) }}
                                        </span>
                                        <span class="px-2.5 py-0.5 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">
                                            {{ $project->project_type == 'fixed_price' ? 'Fixed Price' : 'Hourly' }}
                                        </span>
                                    </div>

                                    <!-- Title -->
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                        <a href="{{ route('marketplace.project.show', $project) }}" class="hover:text-indigo-600 transition">
                                            {{ $project->title }}
                                        </a>
                                    </h3>

                                    <!-- Description -->
                                    <p class="text-gray-600 mb-4 line-clamp-2">
                                        {{ Str::limit($project->description, 200) }}
                                    </p>

                                    <!-- Skills -->
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        @foreach($project->skills_required ?? [] as $skill)
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <!-- Meta Info -->
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Posted {{ $project->published_at?->diffForHumans() ?? 'Recently' }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            {{ $project->proposals_count ?? 0 }} proposals
                                        </span>
                                        @if($project->deadline)
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                Due {{ $project->deadline->format('M d, Y') }}
                                            </span>
                                        @endif
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ $project->allows_remote ? 'Remote' : ($project->location ?? 'On-site') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Budget & Action -->
                                <div class="lg:text-right lg:min-w-[180px]">
                                    <div class="text-2xl font-bold text-green-600 mb-2">
                                        @if($project->project_type == 'hourly')
                                            ₹{{ number_format($project->hourly_rate_min ?? 0) }} - ₹{{ number_format($project->hourly_rate_max ?? 0) }}/hr
                                        @else
                                            ₹{{ number_format($project->budget_min ?? 0) }} - ₹{{ number_format($project->budget_max ?? 0) }}
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 mb-4">
                                        {{ ucfirst($project->experience_level ?? 'Any') }} Level
                                    </div>
                                    <a href="{{ route('marketplace.project.show', $project) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                                        View Details
                                    </a>
                                </div>
                            </div>

                            <!-- Employer Info -->
                            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center">
                                <img src="{{ $project->employer->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($project->employer->name ?? 'E') }}" 
                                     alt="{{ $project->employer->name ?? 'Employer' }}"
                                     class="w-8 h-8 rounded-full mr-3">
                                <div class="text-sm">
                                    <span class="text-gray-900 font-medium">{{ $project->employer->name ?? 'Employer' }}</span>
                                    @if($project->company)
                                        <span class="text-gray-500">• {{ $project->company->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl shadow-md p-12 text-center">
                            <div class="text-gray-400 text-6xl mb-4">🔍</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No projects found</h3>
                            <p class="text-gray-500 mb-6">Try adjusting your filters or search query</p>
                            <a href="{{ route('marketplace.projects') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                Clear all filters
                            </a>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if(isset($projects) && $projects->hasPages())
                    <div class="mt-8">
                        {{ $projects->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
