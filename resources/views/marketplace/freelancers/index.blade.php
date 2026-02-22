@extends('layouts.app')

@section('title', 'Find Freelancers - Talent Marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Find Freelancers</h1>
                <p class="text-gray-600 mt-1">Connect with verified talent for your projects</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:w-1/4">
                <form action="{{ route('marketplace.freelancers') }}" method="GET" class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Filters</h3>

                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" 
                               placeholder="Name, skills..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Skills -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Skills</label>
                        <input type="text" name="skills" value="{{ request('skills') }}" 
                               placeholder="e.g., Laravel, React, Python"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-500 mt-1">Separate multiple skills with commas</p>
                    </div>

                    <!-- Hourly Rate -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hourly Rate (₹)</label>
                        <div class="flex gap-2">
                            <input type="number" name="rate_min" value="{{ request('rate_min') }}" 
                                   placeholder="Min"
                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <input type="number" name="rate_max" value="{{ request('rate_max') }}" 
                                   placeholder="Max"
                                   class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
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

                    <!-- Verified Only -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="verified_only" value="1" {{ request('verified_only') ? 'checked' : '' }} class="text-indigo-600 rounded">
                            <span class="ml-2 text-gray-700">Verified Only</span>
                        </label>
                    </div>

                    <!-- Top Rated -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="top_rated" value="1" {{ request('top_rated') ? 'checked' : '' }} class="text-indigo-600 rounded">
                            <span class="ml-2 text-gray-700">Top Rated (4.5+)</span>
                        </label>
                    </div>

                    <!-- Availability -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Availability</label>
                        <select name="availability" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Any</option>
                            <option value="full_time" {{ request('availability') == 'full_time' ? 'selected' : '' }}>Full-time</option>
                            <option value="part_time" {{ request('availability') == 'part_time' ? 'selected' : '' }}>Part-time</option>
                            <option value="hourly" {{ request('availability') == 'hourly' ? 'selected' : '' }}>Hourly</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Highest Rated</option>
                            <option value="reviews" {{ request('sort') == 'reviews' ? 'selected' : '' }}>Most Reviews</option>
                            <option value="rate_low" {{ request('sort') == 'rate_low' ? 'selected' : '' }}>Lowest Rate</option>
                            <option value="rate_high" {{ request('sort') == 'rate_high' ? 'selected' : '' }}>Highest Rate</option>
                            <option value="projects" {{ request('sort') == 'projects' ? 'selected' : '' }}>Most Projects</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                            Apply Filters
                        </button>
                        <a href="{{ route('marketplace.freelancers') }}" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Freelancers List -->
            <div class="lg:w-3/4">
                <!-- Results Count -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        {{ $freelancers->total() ?? 0 }} freelancers found
                    </p>
                </div>

                <!-- Freelancers Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($freelancers ?? [] as $profile)
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition overflow-hidden">
                            <div class="p-6">
                                <!-- Profile Header -->
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="relative">
                                        <img src="{{ $profile->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($profile->user->name) }}" 
                                             alt="{{ $profile->user->name }}"
                                             class="w-16 h-16 rounded-full object-cover border-2 border-gray-100">
                                        @if($profile->is_verified)
                                            <div class="absolute -bottom-1 -right-1 bg-blue-500 text-white p-1 rounded-full">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">
                                            <a href="{{ route('marketplace.freelancer.show', $profile) }}" class="hover:text-indigo-600 transition">
                                                {{ $profile->user->name }}
                                            </a>
                                        </h3>
                                        <p class="text-indigo-600 text-sm font-medium">{{ $profile->professional_title }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="flex items-center text-yellow-500">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                <span class="ml-1 text-gray-900 font-medium">{{ number_format($profile->average_rating, 1) }}</span>
                                            </span>
                                            <span class="text-gray-400 text-sm">({{ $profile->total_reviews }} reviews)</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-gray-900">
                                            ₹{{ number_format($profile->hourly_rate) }}/hr
                                        </div>
                                    </div>
                                </div>

                                <!-- Bio -->
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    {{ Str::limit($profile->bio, 120) }}
                                </p>

                                <!-- Skills -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach(array_slice($profile->skills ?? [], 0, 4) as $skill)
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                            {{ $skill }}
                                        </span>
                                    @endforeach
                                    @if(count($profile->skills ?? []) > 4)
                                        <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">
                                            +{{ count($profile->skills) - 4 }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Badges -->
                                @if($profile->badges && $profile->badges->isNotEmpty())
                                    <div class="flex gap-2 mb-4">
                                        @foreach($profile->badges->take(3) as $userBadge)
                                            <span class="inline-flex items-center px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-full" 
                                                  title="{{ $userBadge->badge->name }}">
                                                {{ $userBadge->badge->icon ?? '🏆' }} {{ $userBadge->badge->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Stats -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100 text-sm">
                                    <div class="flex gap-4 text-gray-500">
                                        <span>{{ $profile->completed_projects ?? 0 }} projects</span>
                                        <span>{{ $profile->success_rate ?? 0 }}% success</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $profile->availability == 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($profile->availability ?? 'Available') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="px-6 py-4 bg-gray-50 flex gap-3">
                                <a href="{{ route('marketplace.freelancer.show', $profile) }}" 
                                   class="flex-1 px-4 py-2 bg-indigo-600 text-white text-center font-medium rounded-lg hover:bg-indigo-700 transition">
                                    View Profile
                                </a>
                                @auth
                                    <a href="{{ route('marketplace.employer.invite', $profile) }}" 
                                       class="px-4 py-2 border border-indigo-600 text-indigo-600 font-medium rounded-lg hover:bg-indigo-50 transition">
                                        Invite
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 bg-white rounded-xl shadow-md p-12 text-center">
                            <div class="text-gray-400 text-6xl mb-4">👥</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No freelancers found</h3>
                            <p class="text-gray-500 mb-6">Try adjusting your filters or search query</p>
                            <a href="{{ route('marketplace.freelancers') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                Clear all filters
                            </a>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if(isset($freelancers) && $freelancers->hasPages())
                    <div class="mt-8">
                        {{ $freelancers->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
