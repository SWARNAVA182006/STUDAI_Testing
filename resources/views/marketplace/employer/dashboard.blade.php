@extends('layouts.dashboard')

@section('title', 'Talent Marketplace - Employer')

@section('page-title', 'Talent Marketplace')

@section('content')
<div class="space-y-6">
    <!-- Header with CTA -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-gray-500">Manage your projects and hire top talent</p>
        </div>
        <a href="{{ route('marketplace.employer.create-project') }}" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Post New Project
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Open Projects -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-studai-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Open Projects</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $stats['open_projects'] ?? 0 }}</p>
        </div>

        <!-- Active Contracts -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Active Contracts</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $stats['active_contracts'] ?? 0 }}</p>
        </div>

        <!-- Pending Proposals -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Pending Proposals</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $stats['pending_proposals'] ?? 0 }}</p>
        </div>

        <!-- Total Spent -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Total Spent</p>
            <p class="text-3xl font-semibold text-gray-900">${{ number_format($stats['total_spent'] ?? 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- My Projects -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">My Projects</h2>
                    <a href="{{ route('marketplace.employer.projects') }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                        View All →
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($projects ?? [] as $project)
                        <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-medium text-gray-900">
                                            <a href="{{ route('marketplace.employer.manage-project', $project) }}" class="hover:text-studai-blue-600">
                                                {{ $project->title }}
                                            </a>
                                        </h3>
                                        @if($project->is_urgent)
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">Urgent</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $project->category }}</p>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-medium rounded-lg
                                    @switch($project->status)
                                        @case('open') bg-green-100 text-green-700 @break
                                        @case('in_progress') bg-blue-100 text-blue-700 @break
                                        @case('closed') bg-gray-100 text-gray-700 @break
                                        @default bg-yellow-100 text-yellow-700
                                    @endswitch">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-4 text-gray-500">
                                    <span class="font-medium text-gray-900">${{ number_format($project->budget_min ?? 0) }} - ${{ number_format($project->budget_max ?? 0) }}</span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        {{ $project->proposals_count ?? 0 }} proposals
                                    </span>
                                </div>
                                <a href="{{ route('marketplace.employer.manage-project', $project) }}" class="text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                                    Manage →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 mb-4">No projects yet</p>
                            <a href="{{ route('marketplace.employer.create-project') }}" class="btn-primary">
                                Post Your First Project
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Active Contracts -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Active Contracts</h2>
                    <a href="{{ route('marketplace.employer.contracts') }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                        View All →
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($activeContracts ?? [] as $contract)
                        <div class="p-4 border border-gray-200 rounded-xl hover:border-studai-blue-200 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ substr($contract->freelancer->name ?? 'F', 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $contract->project->title ?? 'Project' }}</h3>
                                        <p class="text-sm text-gray-500">{{ $contract->freelancer->name ?? 'Freelancer' }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-lg">
                                    Active
                                </span>
                            </div>
                            
                            <!-- Progress Bar -->
                            @php
                                $completedMilestones = $contract->milestones->where('status', 'paid')->count() ?? 0;
                                $totalMilestones = $contract->milestones->count() ?? 1;
                                $progress = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;
                            @endphp
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progress</span>
                                    <span class="font-medium">{{ $completedMilestones }}/{{ $totalMilestones }} milestones</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-studai-blue-600 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Value: <span class="text-gray-900 font-semibold">${{ number_format($contract->total_amount ?? 0) }}</span></span>
                                <a href="{{ route('marketplace.contracts.show', $contract) }}" class="text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                                    View Details →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-gray-500">No active contracts</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Proposals -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">New Proposals</h2>
                </div>

                <div class="space-y-4">
                    @forelse($recentProposals ?? [] as $proposal)
                        <div class="p-4 border border-gray-200 rounded-xl hover:border-studai-blue-200 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-studai-blue-500 to-studai-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ substr($proposal->freelancer->name ?? 'F', 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $proposal->freelancer->name ?? 'Freelancer' }}</h4>
                                        <p class="text-sm text-gray-500">for {{ $proposal->project->title ?? 'Project' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-green-600">${{ number_format($proposal->bid_amount) }}</div>
                                    <div class="text-gray-500 text-xs">{{ $proposal->estimated_days }} days</div>
                                </div>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ Str::limit($proposal->cover_letter, 150) }}</p>
                            
                            <div class="flex gap-2">
                                <a href="{{ route('marketplace.employer.review-proposals', $proposal->project) }}" 
                                   class="btn-secondary text-sm">
                                    Review
                                </a>
                                <form action="{{ route('marketplace.employer.hire', $proposal) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-xl hover:bg-green-700 transition-colors">
                                        Hire
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500">No new proposals</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('marketplace.employer.create-project') }}" 
                       class="flex items-center gap-3 p-3 bg-studai-blue-600 text-white rounded-xl hover:bg-studai-blue-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span class="font-medium">Post New Project</span>
                    </a>
                    <a href="{{ route('marketplace.freelancers') }}" 
                       class="flex items-center gap-3 p-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span class="font-medium">Browse Freelancers</span>
                    </a>
                    <a href="{{ route('marketplace.employer.saved') }}" 
                       class="flex items-center gap-3 p-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        <span class="font-medium">Saved Freelancers</span>
                    </a>
                </div>
            </div>

            <!-- Spending Overview -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Spending Overview</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">This Month</span>
                        <span class="font-semibold text-gray-900">${{ number_format($spending['this_month'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Last Month</span>
                        <span class="font-semibold text-gray-900">${{ number_format($spending['last_month'] ?? 0) }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Total All Time</span>
                            <span class="font-semibold text-gray-900">${{ number_format($stats['total_spent'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">In Escrow</span>
                        <span class="font-semibold text-studai-blue-600">${{ number_format($spending['in_escrow'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Saved Freelancers -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Saved Freelancers</h3>
                    <a href="{{ route('marketplace.employer.saved') }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                        View All
                    </a>
                </div>
                
                <div class="space-y-3">
                    @forelse($savedFreelancers ?? [] as $saved)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-studai-blue-500 to-studai-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                {{ substr($saved->freelancer->user->name ?? 'F', 0, 1) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $saved->freelancer->user->name ?? 'Freelancer' }}</p>
                                <p class="text-gray-500 text-xs truncate">{{ $saved->freelancer->professional_title ?? '' }}</p>
                            </div>
                            <a href="{{ route('marketplace.freelancer.show', $saved->freelancer) }}" 
                               class="text-studai-blue-600 hover:text-studai-blue-700 text-sm font-medium">
                                View
                            </a>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-4">No saved freelancers yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Tips Card -->
            <div class="bg-gradient-to-br from-studai-blue-600 to-studai-blue-700 rounded-2xl p-6 text-white">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <h3 class="font-semibold">Tips for Success</h3>
                </div>
                <ul class="space-y-2 text-sm text-studai-blue-100">
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Write clear project descriptions
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Set realistic budgets and timelines
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Review freelancer portfolios
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Use escrow for secure payments
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
