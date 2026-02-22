@extends('layouts.app')

@section('title', 'Talent Marketplace - StudAI Career')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    StudAI Talent Marketplace
                </h1>
                <p class="text-xl text-indigo-100 max-w-3xl mx-auto mb-8">
                    Connect with verified freelancers and find exciting projects. Secure escrow payments, skill badges, and seamless collaboration.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('marketplace.projects') }}" 
                       class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Browse Projects
                    </a>
                    <a href="{{ route('marketplace.freelancers') }}" 
                       class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Find Freelancers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-indigo-600">{{ number_format($stats['total_projects'] ?? 0) }}</div>
                <div class="text-gray-600 text-sm mt-1">Open Projects</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['total_freelancers'] ?? 0) }}</div>
                <div class="text-gray-600 text-sm mt-1">Active Freelancers</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-green-600">{{ number_format($stats['completed_contracts'] ?? 0) }}</div>
                <div class="text-gray-600 text-sm mt-1">Completed Projects</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="text-3xl font-bold text-pink-600">₹{{ number_format(($stats['total_value'] ?? 0) / 100000, 1) }}L+</div>
                <div class="text-gray-600 text-sm mt-1">Value Transacted</div>
            </div>
        </div>
    </div>

    <!-- Featured Projects -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Featured Projects</h2>
            <a href="{{ route('marketplace.projects') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                View All →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($featuredProjects ?? [] as $project)
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $project->is_urgent ? 'bg-red-100 text-red-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $project->is_urgent ? '🔥 Urgent' : $project->category }}
                            </span>
                            @if($project->is_featured)
                                <span class="text-yellow-500">⭐</span>
                            @endif
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                            <a href="{{ route('marketplace.project.show', $project) }}" class="hover:text-indigo-600">
                                {{ $project->title }}
                            </a>
                        </h3>
                        
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                            {{ Str::limit($project->description, 120) }}
                        </p>

                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach(array_slice($project->skills_required ?? [], 0, 3) as $skill)
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                    {{ $skill }}
                                </span>
                            @endforeach
                            @if(count($project->skills_required ?? []) > 3)
                                <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">
                                    +{{ count($project->skills_required) - 3 }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-lg font-bold text-green-600">
                                {{ $project->budget_display ?? '₹' . number_format($project->budget_min) . ' - ₹' . number_format($project->budget_max) }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $project->proposals_count ?? 0 }} proposals
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📋</div>
                    <h3 class="text-lg font-medium text-gray-900">No projects yet</h3>
                    <p class="text-gray-500 mt-2">Be the first to post a project!</p>
                    @auth
                        <a href="{{ route('marketplace.employer.create-project') }}" 
                           class="inline-flex items-center mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Post a Project
                        </a>
                    @endauth
                </div>
            @endforelse
        </div>
    </div>

    <!-- Top Freelancers -->
    <div class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Top Rated Freelancers</h2>
                <a href="{{ route('marketplace.freelancers') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    View All →
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse($topFreelancers ?? [] as $profile)
                    <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-md hover:shadow-lg transition p-6 text-center">
                        <div class="relative inline-block mb-4">
                            <img src="{{ $profile->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($profile->user->name) }}" 
                                 alt="{{ $profile->user->name }}"
                                 class="w-20 h-20 rounded-full mx-auto object-cover border-4 border-white shadow">
                            @if($profile->is_verified)
                                <div class="absolute -bottom-1 -right-1 bg-blue-500 text-white p-1 rounded-full">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <h3 class="font-semibold text-gray-900">
                            <a href="{{ route('marketplace.freelancer.show', $profile) }}" class="hover:text-indigo-600">
                                {{ $profile->user->name }}
                            </a>
                        </h3>
                        <p class="text-indigo-600 text-sm mb-2">{{ $profile->professional_title }}</p>
                        
                        <div class="flex items-center justify-center gap-1 mb-3">
                            <span class="text-yellow-400">★</span>
                            <span class="font-semibold">{{ number_format($profile->average_rating, 1) }}</span>
                            <span class="text-gray-400 text-sm">({{ $profile->total_reviews }})</span>
                        </div>

                        <div class="flex flex-wrap justify-center gap-1 mb-4">
                            @foreach(array_slice($profile->skills ?? [], 0, 3) as $skill)
                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-full">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        </div>

                        <div class="text-lg font-bold text-gray-900">
                            {{ $profile->hourly_rate_display ?? '₹' . number_format($profile->hourly_rate) . '/hr' }}
                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">👥</div>
                        <h3 class="text-lg font-medium text-gray-900">No freelancers yet</h3>
                        <p class="text-gray-500 mt-2">Create your freelancer profile to get started!</p>
                        @auth
                            <a href="{{ route('marketplace.freelancer.profile') }}" 
                               class="inline-flex items-center mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                Create Profile
                            </a>
                        @endauth
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Browse by Category</h2>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($categories ?? ['Web Development', 'Mobile Apps', 'UI/UX Design', 'Data Science', 'Content Writing', 'Digital Marketing', 'Video Editing', 'Virtual Assistant', 'Translation', 'Accounting', 'Legal', 'Other'] as $category)
                <a href="{{ route('marketplace.projects', ['category' => Str::slug($category)]) }}" 
                   class="bg-white rounded-xl shadow-md hover:shadow-lg hover:scale-105 transition p-6 text-center group">
                    <div class="text-3xl mb-2">
                        @switch(Str::slug($category))
                            @case('web-development')
                                💻
                                @break
                            @case('mobile-apps')
                                📱
                                @break
                            @case('uiux-design')
                                🎨
                                @break
                            @case('data-science')
                                📊
                                @break
                            @case('content-writing')
                                ✍️
                                @break
                            @case('digital-marketing')
                                📣
                                @break
                            @case('video-editing')
                                🎬
                                @break
                            @case('virtual-assistant')
                                🤖
                                @break
                            @case('translation')
                                🌍
                                @break
                            @case('accounting')
                                📈
                                @break
                            @case('legal')
                                ⚖️
                                @break
                            @default
                                📁
                        @endswitch
                    </div>
                    <div class="font-medium text-gray-900 group-hover:text-indigo-600 transition">
                        {{ $category }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
            <p class="text-xl text-indigo-100 mb-8">
                Whether you're looking to hire talent or find freelance work, we've got you covered.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('marketplace.employer.dashboard') }}" 
                       class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Post a Project
                    </a>
                    <a href="{{ route('marketplace.freelancer.dashboard') }}" 
                       class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Find Work
                    </a>
                @else
                    <a href="{{ route('register') }}" 
                       class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">
                        Get Started Free
                    </a>
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition">
                        Sign In
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Features Grid -->
    <div class="bg-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-12 text-center">Why Choose Our Marketplace?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Secure Escrow Payments</h3>
                    <p class="text-gray-600">Your payments are held securely until you approve the work. Both parties are protected.</p>
                </div>

                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Verified Skill Badges</h3>
                    <p class="text-gray-600">Freelancers earn verified badges to showcase their expertise and stand out from the crowd.</p>
                </div>

                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Milestone Tracking</h3>
                    <p class="text-gray-600">Break projects into milestones, track progress, and release payments as work is completed.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
