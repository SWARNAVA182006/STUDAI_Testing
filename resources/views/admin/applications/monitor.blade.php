@extends('layouts.admin')

@section('title', 'Application Monitor')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Application Monitor</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.applications.export', request()->query()) }}" 
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Export CSV
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Applications</h3>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
            <p class="text-sm text-gray-600 mt-1">All time</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Submitted</h3>
            <p class="text-3xl font-bold text-green-600">{{ number_format($stats['submitted']) }}</p>
            <p class="text-sm text-gray-600 mt-1">Successfully sent</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Response Rate</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['response_rate'] }}%</p>
            <p class="text-sm text-gray-600 mt-1">Got response</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Interview Rate</h3>
            <p class="text-3xl font-bold text-purple-600">
                {{ $stats['submitted'] > 0 ? round(($stats['got_interview'] / $stats['submitted']) * 100, 1) : 0 }}%
            </p>
            <p class="text-sm text-gray-600 mt-1">{{ $stats['got_interview'] }} interviews</p>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">By Status</h3>
            <div class="space-y-2">
                @foreach($stats['by_status'] as $status => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                        <span class="font-semibold">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">By Method</h3>
            <div class="space-y-2">
                @foreach($stats['by_method'] as $method => $count)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $method ?? 'Unknown') }}</span>
                        <span class="font-semibold">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Timeline</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Today</span>
                    <span class="font-semibold">{{ number_format($stats['today']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">This Week</span>
                    <span class="font-semibold">{{ number_format($stats['this_week']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">This Month</span>
                    <span class="font-semibold">{{ number_format($stats['this_month']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="{{ route('admin.applications.monitor') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Job title or company..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="requires_manual" {{ request('status') === 'requires_manual' ? 'selected' : '' }}>Requires Manual</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Application Status</label>
                <select name="application_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="submitted" {{ request('application_status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="viewed" {{ request('application_status') === 'viewed' ? 'selected' : '' }}>Viewed</option>
                    <option value="screening" {{ request('application_status') === 'screening' ? 'selected' : '' }}>Screening</option>
                    <option value="interviewing" {{ request('application_status') === 'interviewing' ? 'selected' : '' }}>Interviewing</option>
                    <option value="offered" {{ request('application_status') === 'offered' ? 'selected' : '' }}>Offered</option>
                    <option value="rejected" {{ request('application_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Submission Method</label>
                <select name="submission_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Methods</option>
                    <option value="email" {{ request('submission_method') === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="api" {{ request('submission_method') === 'api' ? 'selected' : '' }}>API</option>
                    <option value="external_url" {{ request('submission_method') === 'external_url' ? 'selected' : '' }}>External URL</option>
                    <option value="manual_review" {{ request('submission_method') === 'manual_review' ? 'selected' : '' }}>Manual Review</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input type="date" 
                       name="date_from" 
                       value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input type="date" 
                       name="date_to" 
                       value="{{ request('date_to') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('admin.applications.monitor') }}" 
                   class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ATS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($applications as $application)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $application->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $application->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium">{{ $application->discoveredJob->job_title ?? 'N/A' }}</div>
                            <div class="text-gray-500">{{ $application->discoveredJob->company_name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $application->status === 'submitted' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $application->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $application->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $application->status === 'requires_manual' ? 'bg-blue-100 text-blue-800' : '' }}
                            ">
                                {{ ucfirst($application->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($application->application_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ ucfirst(str_replace('_', ' ', $application->submission_method ?? 'N/A')) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $application->ats_optimization_score ? round($application->ats_optimization_score) . '%' : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $application->submitted_at?->format('M d, Y H:i') ?? 'Not yet' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('admin.applications.show', $application) }}" 
                               class="text-blue-600 hover:text-blue-900">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            No applications found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($applications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
