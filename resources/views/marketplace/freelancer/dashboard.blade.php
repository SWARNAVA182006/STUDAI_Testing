@extends('layouts.app')

@section('title', 'Freelancer Dashboard - Talent Marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Freelancer Dashboard</h1>
                <p class="text-gray-600 mt-1">Manage your projects and earnings</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <a href="{{ route('marketplace.freelancer.profile') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Edit Profile
                </a>
                <a href="{{ route('marketplace.projects') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Find Projects
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Earnings</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">₹{{ number_format($stats['total_earnings'] ?? 0) }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Earnings</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">₹{{ number_format($stats['pending_earnings'] ?? 0) }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Projects</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['ongoing_projects'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Proposals</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['active_proposals'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Active Contracts -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Active Contracts</h2>
                        <a href="{{ route('marketplace.freelancer.contracts') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                            View All →
                        </a>
                    </div>

                    <div class="space-y-4">
                        @forelse($activeContracts ?? [] as $contract)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">
                                            <a href="{{ route('marketplace.contracts.show', $contract) }}" class="hover:text-indigo-600">
                                                {{ $contract->project->title ?? 'Project' }}
                                            </a>
                                        </h3>
                                        <p class="text-gray-500 text-sm">with {{ $contract->employer->name ?? 'Client' }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        {{ $contract->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ ucfirst($contract->status) }}
                                    </span>
                                </div>
                                
                                <!-- Progress Bar -->
                                @php
                                    $completedMilestones = $contract->milestones->where('status', 'paid')->count();
                                    $totalMilestones = $contract->milestones->count();
                                    $progress = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;
                                @endphp
                                <div class="mb-3">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Progress</span>
                                        <span>{{ $completedMilestones }}/{{ $totalMilestones }} milestones</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Value: <span class="text-gray-900 font-medium">₹{{ number_format($contract->total_amount ?? 0) }}</span></span>
                                    <a href="{{ route('marketplace.contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-4xl mb-2">📋</div>
                                <p class="text-gray-500 mb-4">No active contracts</p>
                                <a href="{{ route('marketplace.projects') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                    Browse Projects →
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Proposals -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Proposals</h2>
                        <a href="{{ route('marketplace.freelancer.proposals') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                            View All →
                        </a>
                    </div>

                    <div class="space-y-4">
                        @forelse($recentProposals ?? [] as $proposal)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">
                                            <a href="{{ route('marketplace.project.show', $proposal->project) }}" class="hover:text-indigo-600">
                                                {{ $proposal->project->title ?? 'Project' }}
                                            </a>
                                        </h3>
                                        <p class="text-gray-500 text-sm">Submitted {{ $proposal->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @switch($proposal->status)
                                            @case('pending') bg-yellow-100 text-yellow-700 @break
                                            @case('accepted') bg-green-100 text-green-700 @break
                                            @case('rejected') bg-red-100 text-red-700 @break
                                            @default bg-gray-100 text-gray-700
                                        @endswitch">
                                        {{ ucfirst($proposal->status) }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">
                                        Your bid: <span class="text-gray-900 font-medium">₹{{ number_format($proposal->bid_amount) }}</span>
                                    </span>
                                    <span class="text-gray-600">
                                        {{ $proposal->estimated_days }} days
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-4xl mb-2">📝</div>
                                <p class="text-gray-500 mb-4">No proposals yet</p>
                                <a href="{{ route('marketplace.projects') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                    Browse Projects →
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recommended Projects -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Recommended for You</h2>
                        <a href="{{ route('marketplace.projects') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                            View All →
                        </a>
                    </div>

                    <div class="space-y-4">
                        @forelse($recommendedProjects ?? [] as $project)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">
                                            <a href="{{ route('marketplace.project.show', $project) }}" class="hover:text-indigo-600">
                                                {{ $project->title }}
                                            </a>
                                        </h3>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach(array_slice($project->skills_required ?? [], 0, 3) as $skill)
                                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-green-600">
                                            ₹{{ number_format($project->budget_min ?? 0) }}+
                                        </div>
                                        <p class="text-gray-500 text-xs">{{ $project->proposals_count ?? 0 }} proposals</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-4xl mb-2">🎯</div>
                                <p class="text-gray-500">Update your profile to get personalized recommendations</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Profile Completion -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Profile Strength</h3>
                    @php
                        $profileScore = $profile ? ($profile->bio ? 20 : 0) + ($profile->skills ? 20 : 0) + ($profile->hourly_rate ? 20 : 0) + ($profile->portfolio ? 20 : 0) + ($profile->is_verified ? 20 : 0) : 0;
                    @endphp
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Completion</span>
                            <span class="font-medium text-gray-900">{{ $profileScore }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="h-3 rounded-full transition-all duration-500
                                {{ $profileScore >= 80 ? 'bg-green-500' : ($profileScore >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                 style="width: {{ $profileScore }}%"></div>
                        </div>
                    </div>
                    
                    @if($profileScore < 100)
                        <p class="text-gray-600 text-sm mb-4">Complete your profile to attract more clients</p>
                        <a href="{{ route('marketplace.freelancer.profile') }}" 
                           class="block w-full px-4 py-2 bg-indigo-600 text-white text-center font-medium rounded-lg hover:bg-indigo-700 transition">
                            Complete Profile
                        </a>
                    @else
                        <p class="text-green-600 text-sm flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Profile Complete!
                        </p>
                    @endif
                </div>

                <!-- Ratings Overview -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Your Ratings</h3>
                    <div class="flex items-center justify-center mb-4">
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-2">
                                <svg class="w-8 h-8 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-3xl font-bold text-gray-900 ml-2">{{ number_format($profile->average_rating ?? 0, 1) }}</span>
                            </div>
                            <p class="text-gray-500 text-sm">{{ $profile->total_reviews ?? 0 }} reviews</p>
                        </div>
                    </div>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Success Rate</span>
                            <span class="font-medium text-green-600">{{ $profile->success_rate ?? 100 }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">On-time Delivery</span>
                            <span class="font-medium text-gray-900">{{ $stats['ontime_rate'] ?? 100 }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Response Rate</span>
                            <span class="font-medium text-gray-900">{{ $stats['response_rate'] ?? 100 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Skill Badges -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">Skill Badges</h3>
                        <a href="{{ route('marketplace.freelancer.badges') }}" class="text-indigo-600 hover:text-indigo-700 text-sm">
                            View All
                        </a>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($badges ?? [] as $userBadge)
                            <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-2xl mr-3">{{ $userBadge->badge->icon ?? '🏆' }}</span>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $userBadge->badge->name }}</p>
                                    <p class="text-gray-500 text-xs">{{ $userBadge->badge->category }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-gray-500 text-sm mb-2">No badges yet</p>
                                <a href="{{ route('marketplace.freelancer.badges') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                    Earn Badges
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('marketplace.projects') }}" 
                           class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="text-gray-700">Find Projects</span>
                        </a>
                        <a href="{{ route('marketplace.freelancer.proposals') }}" 
                           class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-gray-700">My Proposals</span>
                        </a>
                        <a href="{{ route('marketplace.freelancer.earnings') }}" 
                           class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-gray-700">Earnings</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
