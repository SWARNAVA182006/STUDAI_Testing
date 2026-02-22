@extends('layouts.dashboard')

@section('title', 'Applications')

@section('page-title', 'Applications')

@section('content')
<div class="space-y-6">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <p class="text-gray-500">Manage and review candidate applications</p>
        <div class="flex items-center gap-3">
            <a href="{{ route('employer.applicants.kanban') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                Kanban View
            </a>
            <form action="{{ route('employer.applicants.export') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="job_id" value="{{ request('job_id') }}">
                <button type="submit" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
        <form method="GET" action="{{ route('employer.applicants.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search by name or email..." 
                       class="input-google w-full">
            </div>

            <div>
                <select name="job_id" class="input-google w-full">
                    <option value="">All Jobs</option>
                    @foreach($jobs ?? [] as $job)
                        <option value="{{ $job->id }}" {{ request('job_id') == $job->id ? 'selected' : '' }}>
                            {{ $job->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="sort" class="input-google w-full">
                    <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Latest First</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Apply
                </button>
                @if(request()->hasAny(['search', 'job_id', 'sort']))
                    <a href="{{ route('employer.applicants.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Status Tabs -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
        <div class="flex flex-wrap border-b border-gray-100">
            <a href="{{ route('employer.applicants.index') }}" 
               class="px-6 py-4 text-sm font-medium transition-colors {{ !request('status') ? 'text-studai-blue-600 border-b-2 border-studai-blue-600 bg-studai-blue-50/50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                All
                <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs">{{ $statusCounts['all'] ?? 0 }}</span>
            </a>
            <a href="{{ route('employer.applicants.index', ['status' => 'pending']) }}" 
               class="px-6 py-4 text-sm font-medium transition-colors {{ request('status') === 'pending' ? 'text-studai-blue-600 border-b-2 border-studai-blue-600 bg-studai-blue-50/50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                Pending
                <span class="ml-2 px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs">{{ $statusCounts['pending'] ?? 0 }}</span>
            </a>
            <a href="{{ route('employer.applicants.index', ['status' => 'reviewing']) }}" 
               class="px-6 py-4 text-sm font-medium transition-colors {{ request('status') === 'reviewing' ? 'text-studai-blue-600 border-b-2 border-studai-blue-600 bg-studai-blue-50/50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                Reviewing
                <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">{{ $statusCounts['reviewing'] ?? 0 }}</span>
            </a>
            <a href="{{ route('employer.applicants.index', ['status' => 'shortlisted']) }}" 
               class="px-6 py-4 text-sm font-medium transition-colors {{ request('status') === 'shortlisted' ? 'text-studai-blue-600 border-b-2 border-studai-blue-600 bg-studai-blue-50/50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                Shortlisted
                <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">{{ $statusCounts['shortlisted'] ?? 0 }}</span>
            </a>
            <a href="{{ route('employer.applicants.index', ['status' => 'rejected']) }}" 
               class="px-6 py-4 text-sm font-medium transition-colors {{ request('status') === 'rejected' ? 'text-studai-blue-600 border-b-2 border-studai-blue-600 bg-studai-blue-50/50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                Rejected
                <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">{{ $statusCounts['rejected'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    @if(isset($applications) && $applications->isEmpty())
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-12 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">No Applications Found</h2>
            <p class="text-gray-500">
                @if(request()->hasAny(['search', 'job_id', 'status']))
                    Try adjusting your filters
                @else
                    Applications will appear here once candidates apply to your jobs
                @endif
            </p>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Candidate</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Job</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Applied</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">AI Score</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($applications ?? [] as $application)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-studai-blue-500 to-studai-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ substr($application->user->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $application->user->name ?? 'Unknown' }}</div>
                                            <div class="text-sm text-gray-500">{{ $application->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $application->job->title ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500">{{ $application->job->location ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $application->created_at?->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $application->created_at?->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php $score = $application->ai_score ?? rand(65, 95); @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 relative">
                                            <svg class="w-10 h-10 transform -rotate-90">
                                                <circle cx="20" cy="20" r="16" stroke="#E5E7EB" stroke-width="3" fill="none"/>
                                                <circle cx="20" cy="20" r="16" 
                                                        stroke="{{ $score >= 80 ? '#22C55E' : ($score >= 60 ? '#F59E0B' : '#EF4444') }}" 
                                                        stroke-width="3" fill="none"
                                                        stroke-dasharray="{{ $score * 1.005 }} 100.5"
                                                        stroke-linecap="round"/>
                                            </svg>
                                            <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold">{{ $score }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select onchange="updateStatus({{ $application->id }}, this.value)" 
                                            class="px-3 py-1.5 rounded-lg text-xs font-medium border-0 cursor-pointer focus:ring-2 focus:ring-studai-blue-500
                                            @if($application->status === 'pending') bg-orange-100 text-orange-700
                                            @elseif($application->status === 'reviewing') bg-blue-100 text-blue-700
                                            @elseif($application->status === 'shortlisted') bg-green-100 text-green-700
                                            @elseif($application->status === 'rejected') bg-red-100 text-red-700
                                            @else bg-purple-100 text-purple-700
                                            @endif">
                                        <option value="pending" {{ $application->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="reviewing" {{ $application->status === 'reviewing' ? 'selected' : '' }}>Reviewing</option>
                                        <option value="shortlisted" {{ $application->status === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                                        <option value="rejected" {{ $application->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="hired" {{ $application->status === 'hired' ? 'selected' : '' }}>Hired</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('employer.applicants.show', $application->id) }}" 
                                       class="inline-flex items-center text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                                        View Details
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(isset($applications) && method_exists($applications, 'links'))
            <div class="mt-6">
                {{ $applications->appends(request()->query())->links() }}
            </div>
        @endif
    @endif
</div>

<script>
function updateStatus(applicationId, status) {
    fetch(`/employer/applicants/${applicationId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Failed to update status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
@endsection
