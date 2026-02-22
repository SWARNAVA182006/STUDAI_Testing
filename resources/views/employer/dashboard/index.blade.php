@extends('layouts.dashboard')

@section('title', 'Employer Dashboard')

@section('page-title', 'Employer Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-studai-blue-600 to-studai-blue-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold mb-1">Welcome back, {{ $company->name ?? 'Employer' }}!</h1>
                <p class="text-studai-blue-100">Here's what's happening with your job postings</p>
            </div>
            <a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center px-5 py-2.5 bg-white text-studai-blue-600 font-medium rounded-xl hover:bg-studai-blue-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Post New Job
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Jobs -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-studai-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">{{ $activeJobs ?? 0 }} Active</span>
            </div>
            <p class="text-sm text-gray-500 mb-1">Total Jobs</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $totalJobs ?? 0 }}</p>
        </div>

        <!-- Total Applications -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-orange-600 bg-orange-50 px-2 py-1 rounded-full">+{{ $newApplications ?? 0 }} this week</span>
            </div>
            <p class="text-sm text-gray-500 mb-1">Total Applications</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $totalApplications ?? 0 }}</p>
        </div>

        <!-- Pending Review -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Pending Review</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $applicationsByStatus['pending'] ?? 0 }}</p>
            <a href="{{ route('employer.applicants.index', ['status' => 'pending']) }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium mt-2 inline-block">
                Review Now →
            </a>
        </div>

        <!-- Shortlisted -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-1">Shortlisted</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $applicationsByStatus['shortlisted'] ?? 0 }}</p>
            <a href="{{ route('employer.applicants.index', ['status' => 'shortlisted']) }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium mt-2 inline-block">
                View All →
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Applications -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Applications</h2>
                    <a href="{{ route('employer.applicants.index') }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                        View All →
                    </a>
                </div>

                @if(isset($recentApplications) && $recentApplications->isEmpty())
                    <div class="text-center py-12">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500">No applications yet</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentApplications ?? [] as $application)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-studai-blue-500 to-studai-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ substr($application->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $application->user->name ?? 'Unknown' }}</h3>
                                        <p class="text-sm text-gray-500">{{ $application->job->title ?? 'Unknown Position' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        @if($application->status === 'pending') bg-orange-100 text-orange-700
                                        @elseif($application->status === 'reviewing') bg-blue-100 text-blue-700
                                        @elseif($application->status === 'shortlisted') bg-green-100 text-green-700
                                        @elseif($application->status === 'rejected') bg-red-100 text-red-700
                                        @else bg-gray-100 text-gray-700
                                        @endif">
                                        {{ ucfirst($application->status) }}
                                    </span>
                                    <a href="{{ route('employer.applicants.show', $application->id) }}" class="text-gray-400 hover:text-studai-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Active Jobs with Application Counts -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Your Job Postings</h2>
                    <a href="{{ route('employer.jobs.index') }}" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium">
                        View All →
                    </a>
                </div>

                @if(isset($jobsWithApplications) && $jobsWithApplications->isEmpty())
                    <div class="text-center py-12">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 mb-4">No jobs posted yet</p>
                        <a href="{{ route('employer.jobs.create') }}" class="btn-primary">
                            Post Your First Job
                        </a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($jobsWithApplications ?? [] as $job)
                            <div class="p-4 border border-gray-200 rounded-xl hover:border-studai-blue-200 transition-colors">
                                <div class="flex items-start justify-between mb-3">
                                    <h3 class="font-semibold text-gray-900">{{ $job->title }}</h3>
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium
                                        @if($job->status === 'published') bg-green-100 text-green-700
                                        @elseif($job->status === 'draft') bg-gray-100 text-gray-700
                                        @else bg-red-100 text-red-700
                                        @endif">
                                        {{ ucfirst($job->status) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-500 mb-4">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $job->location }}
                                    </span>
                                    <span>•</span>
                                    <span>{{ ucwords(str_replace('-', ' ', $job->job_type)) }}</span>
                                    <span>•</span>
                                    <span>Posted {{ $job->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-semibold text-gray-900">{{ $job->total_applications ?? 0 }} Applications</span>
                                        @if(($job->new_applications ?? 0) > 0)
                                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
                                                {{ $job->new_applications }} New
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('employer.jobs.show', $job->id) }}" class="text-studai-blue-600 hover:text-studai-blue-700 text-sm font-medium">
                                        Manage →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('employer.jobs.create') }}" class="flex items-center gap-3 p-3 bg-studai-blue-600 text-white rounded-xl hover:bg-studai-blue-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="font-medium">Post New Job</span>
                    </a>
                    <a href="{{ route('employer.applicants.kanban') }}" class="flex items-center gap-3 p-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        <span class="font-medium">Kanban Board</span>
                    </a>
                    <a href="{{ route('employer.profile.show') }}" class="flex items-center gap-3 p-3 bg-gray-50 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="font-medium">Company Profile</span>
                    </a>
                </div>
            </div>

            <!-- Application Status Breakdown -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Pipeline</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Pending</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $applicationsByStatus['pending'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Reviewing</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $applicationsByStatus['reviewing'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Shortlisted</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $applicationsByStatus['shortlisted'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Hired</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $applicationsByStatus['hired'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Rejected</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $applicationsByStatus['rejected'] ?? 0 }}</span>
                    </div>
                </div>

                <!-- Pipeline Visualization -->
                <div class="mt-6">
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden flex">
                        @php
                            $total = array_sum($applicationsByStatus ?? []);
                            $pendingPct = $total > 0 ? (($applicationsByStatus['pending'] ?? 0) / $total) * 100 : 0;
                            $reviewingPct = $total > 0 ? (($applicationsByStatus['reviewing'] ?? 0) / $total) * 100 : 0;
                            $shortlistedPct = $total > 0 ? (($applicationsByStatus['shortlisted'] ?? 0) / $total) * 100 : 0;
                            $hiredPct = $total > 0 ? (($applicationsByStatus['hired'] ?? 0) / $total) * 100 : 0;
                        @endphp
                        <div class="bg-orange-500 h-full" style="width: {{ $pendingPct }}%"></div>
                        <div class="bg-blue-500 h-full" style="width: {{ $reviewingPct }}%"></div>
                        <div class="bg-green-500 h-full" style="width: {{ $shortlistedPct }}%"></div>
                        <div class="bg-purple-500 h-full" style="width: {{ $hiredPct }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Jobs -->
            @if(isset($topJobs) && $topJobs->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Jobs</h3>
                <div class="space-y-4">
                    @foreach($topJobs as $index => $job)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-{{ $index === 0 ? 'yellow' : ($index === 1 ? 'gray' : 'orange') }}-100 flex items-center justify-center text-sm font-semibold text-{{ $index === 0 ? 'yellow' : ($index === 1 ? 'gray' : 'orange') }}-700">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 truncate">{{ $job->title }}</h4>
                                <p class="text-xs text-gray-500">{{ $job->applications_count }} applications</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
