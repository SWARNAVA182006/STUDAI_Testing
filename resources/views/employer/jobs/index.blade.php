@extends('layouts.app')

@section('title', 'My Jobs')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Job Postings</h1>
                <p class="text-gray-600">Manage your job listings</p>
            </div>
            <a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Post New Job
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" action="{{ route('employer.jobs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by job title..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                <!-- Expiry Filter -->
                <div>
                    <select name="expiry" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">All Jobs</option>
                        <option value="active" {{ request('expiry') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="expired" {{ request('expiry') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>

                <div class="md:col-span-4 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['search', 'status', 'expiry']))
                        <a href="{{ route('employer.jobs.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                            Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Status Tabs -->
        <div class="bg-white rounded-xl shadow-lg mb-6">
            <div class="flex border-b border-gray-200">
                <a href="{{ route('employer.jobs.index') }}" class="px-6 py-4 text-sm font-semibold {{ !request('status') ? 'text-pink-600 border-b-2 border-pink-600' : 'text-gray-600 hover:text-gray-900' }}">
                    All Jobs
                    <span class="ml-2 px-2 py-1 bg-gray-100 rounded-full text-xs">{{ $statusCounts['all'] }}</span>
                </a>
                <a href="{{ route('employer.jobs.index', ['status' => 'published']) }}" class="px-6 py-4 text-sm font-semibold {{ request('status') === 'published' ? 'text-pink-600 border-b-2 border-pink-600' : 'text-gray-600 hover:text-gray-900' }}">
                    Published
                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">{{ $statusCounts['published'] }}</span>
                </a>
                <a href="{{ route('employer.jobs.index', ['status' => 'draft']) }}" class="px-6 py-4 text-sm font-semibold {{ request('status') === 'draft' ? 'text-pink-600 border-b-2 border-pink-600' : 'text-gray-600 hover:text-gray-900' }}">
                    Draft
                    <span class="ml-2 px-2 py-1 bg-gray-100 rounded-full text-xs">{{ $statusCounts['draft'] }}</span>
                </a>
                <a href="{{ route('employer.jobs.index', ['status' => 'closed']) }}" class="px-6 py-4 text-sm font-semibold {{ request('status') === 'closed' ? 'text-pink-600 border-b-2 border-pink-600' : 'text-gray-600 hover:text-gray-900' }}">
                    Closed
                    <span class="ml-2 px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">{{ $statusCounts['closed'] }}</span>
                </a>
            </div>
        </div>

        @if($jobs->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">No Jobs Found</h2>
                <p class="text-gray-600 mb-8">
                    @if(request()->hasAny(['search', 'status', 'expiry']))
                        Try adjusting your filters
                    @else
                        Start posting jobs to find great candidates
                    @endif
                </p>
                <a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all">
                    Post Your First Job
                </a>
            </div>
        @else
            <!-- Jobs List -->
            <div class="space-y-4">
                @foreach($jobs as $job)
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-gray-900">{{ $job->title }}</h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($job->status === 'published') bg-green-100 text-green-700
                                        @elseif($job->status === 'draft') bg-gray-100 text-gray-700
                                        @else bg-red-100 text-red-700
                                        @endif">
                                        {{ ucfirst($job->status) }}
                                    </span>
                                    @if($job->expires_at < now())
                                        <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-semibold">
                                            Expired
                                        </span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                        {{ $job->location }}
                                    </span>
                                    <span>•</span>
                                    <span>{{ ucwords(str_replace('-', ' ', $job->job_type)) }}</span>
                                    <span>•</span>
                                    <span>{{ ucfirst($job->experience_level) }} Level</span>
                                    <span>•</span>
                                    <span>Posted {{ $job->created_at->diffForHumans() }}</span>
                                    <span>•</span>
                                    <span>Expires {{ $job->expires_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('employer.jobs.show', $job->id) }}" class="p-2 text-gray-600 hover:text-pink-600 transition-colors" title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('employer.jobs.edit', $job->id) }}" class="p-2 text-gray-600 hover:text-pink-600 transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-6">
                                <div>
                                    <p class="text-sm text-gray-600">Total Applications</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $job->applications_count }}</p>
                                </div>
                                @if($job->status === 'published')
                                    <div class="flex gap-2">
                                        <form action="{{ route('employer.jobs.close', $job->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 font-semibold rounded-lg hover:bg-red-200 transition-colors">
                                                Close Job
                                            </button>
                                        </form>
                                    </div>
                                @elseif($job->status === 'closed' && $job->expires_at > now())
                                    <form action="{{ route('employer.jobs.reopen', $job->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-4 py-2 bg-green-100 text-green-700 font-semibold rounded-lg hover:bg-green-200 transition-colors">
                                            Reopen Job
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('employer.jobs.duplicate', $job->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                        Duplicate
                                    </button>
                                </form>
                            </div>
                            <a href="{{ route('employer.applicants.index', ['job_id' => $job->id]) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all">
                                View Applications
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $jobs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
