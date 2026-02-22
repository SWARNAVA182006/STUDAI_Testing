@extends('layouts.app')

@section('title', 'Agent Applications')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('agent.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Application History</h1>
            </div>
            <p class="text-gray-600">Track all applications submitted by your autonomous agent</p>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" action="{{ route('agent.applications') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Status Filter --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Needs Approval</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                {{-- Outcome Filter --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Outcome</label>
                    <select name="outcome" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Outcomes</option>
                        <option value="interview_scheduled" {{ request('outcome') === 'interview_scheduled' ? 'selected' : '' }}>Interview Scheduled</option>
                        <option value="offer_received" {{ request('outcome') === 'offer_received' ? 'selected' : '' }}>Offer Received</option>
                        <option value="accepted" {{ request('outcome') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="rejected" {{ request('outcome') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="withdrawn" {{ request('outcome') === 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                        <option value="no_response" {{ request('outcome') === 'no_response' ? 'selected' : '' }}>No Response</option>
                    </select>
                </div>

                {{-- Date Range --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">From Date</label>
                    <input type="date" 
                           name="from_date" 
                           value="{{ request('from_date') }}"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">To Date</label>
                    <input type="date" 
                           name="to_date" 
                           value="{{ request('to_date') }}"
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                {{-- Action Buttons --}}
                <div class="md:col-span-4 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-primary to-primary-light text-white font-semibold rounded-lg hover:shadow-lg transition-all">
                        <i data-lucide="filter" class="w-4 h-4 inline mr-2"></i>
                        Apply Filters
                    </button>
                    <a href="{{ route('agent.applications') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                        Clear Filters
                    </a>
                    <div class="ml-auto">
                        <select name="sort_by" class="px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Date Applied</option>
                            <option value="match_score" {{ request('sort_by') === 'match_score' ? 'selected' : '' }}>Match Score</option>
                            <option value="company_name" {{ request('sort_by') === 'company_name' ? 'selected' : '' }}>Company</option>
                        </select>
                        <select name="sort_order" class="ml-2 px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="desc" {{ request('sort_order', 'desc') === 'desc' ? 'selected' : '' }}>Newest First</option>
                            <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Oldest First</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        {{-- Statistics Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-lg p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $applications->total() }}</p>
                <p class="text-sm text-gray-600">Total</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 text-center border-l-4 border-blue-500">
                <p class="text-2xl font-bold text-blue-600">{{ $statusCounts['submitted'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Submitted</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 text-center border-l-4 border-green-500">
                <p class="text-2xl font-bold text-green-600">{{ $outcomeCounts['interview_scheduled'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Interviews</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 text-center border-l-4 border-yellow-500">
                <p class="text-2xl font-bold text-yellow-600">{{ $pendingCount ?? 0 }}</p>
                <p class="text-sm text-gray-600">Pending</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 text-center border-l-4 border-red-500">
                <p class="text-2xl font-bold text-red-600">{{ $outcomeCounts['rejected'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Rejected</p>
            </div>
        </div>

        {{-- Applications List --}}
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            @if($applications->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Job Details</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Match</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Outcome</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Applied</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($applications as $application)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ $application->job_title }}</p>
                                            <p class="text-sm text-gray-600">{{ $application->company_name }}</p>
                                            @if($application->location)
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <i data-lucide="map-pin" class="w-3 h-3 inline"></i>
                                                    {{ $application->location }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($application->match_score)
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full {{ $application->match_score >= 80 ? 'bg-green-500' : ($application->match_score >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                                         style="width: {{ $application->match_score }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900">{{ round($application->match_score) }}%</span>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($application->status === 'submitted')
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">Submitted</span>
                                        @elseif($application->status === 'pending')
                                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Pending</span>
                                        @elseif($application->status === 'pending_approval')
                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">Needs Approval</span>
                                        @elseif($application->status === 'failed')
                                            <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Failed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($application->outcome)
                                            @if($application->outcome === 'interview_scheduled')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                    <i data-lucide="calendar-check" class="w-3 h-3 inline"></i>
                                                    Interview
                                                </span>
                                            @elseif($application->outcome === 'offer_received')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                    <i data-lucide="gift" class="w-3 h-3 inline"></i>
                                                    Offer
                                                </span>
                                            @elseif($application->outcome === 'accepted')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                                    <i data-lucide="check-circle" class="w-3 h-3 inline"></i>
                                                    Accepted
                                                </span>
                                            @elseif($application->outcome === 'rejected')
                                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                                    <i data-lucide="x-circle" class="w-3 h-3 inline"></i>
                                                    Rejected
                                                </span>
                                            @elseif($application->outcome === 'withdrawn')
                                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">Withdrawn</span>
                                            @elseif($application->outcome === 'no_response')
                                                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">No Response</span>
                                            @endif
                                        @else
                                            <span class="text-sm text-gray-400">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-gray-900">{{ $application->created_at->format('M d, Y') }}</p>
                                        <p class="text-xs text-gray-500">{{ $application->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            @if($application->job)
                                                <a href="{{ route('jobs.show', $application->job_id) }}" 
                                                   class="text-primary hover:text-primary-dark" 
                                                   title="View Job">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                            @if($application->customized_resume_path)
                                                <a href="{{ route('agent.download-resume', $application->id) }}" 
                                                   class="text-blue-600 hover:text-blue-800" 
                                                   title="Download Resume">
                                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                            @if($application->cover_letter_path)
                                                <a href="{{ route('agent.download-cover-letter', $application->id) }}" 
                                                   class="text-green-600 hover:text-green-800" 
                                                   title="Download Cover Letter">
                                                    <i data-lucide="file" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                            @if($application->status === 'pending_approval')
                                                <form action="{{ route('agent.approve-application', $application->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="text-green-600 hover:text-green-800" 
                                                            title="Approve">
                                                        <i data-lucide="check" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('agent.reject-application', $application->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-800" 
                                                            title="Reject">
                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $applications->appends(request()->query())->links() }}
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Applications Found</h3>
                    <p class="text-gray-600 mb-4">
                        @if(request()->hasAny(['status', 'outcome', 'from_date', 'to_date']))
                            Try adjusting your filters to see more results.
                        @else
                            Your agent hasn't submitted any applications yet.
                        @endif
                    </p>
                    @if(!request()->hasAny(['status', 'outcome', 'from_date', 'to_date']))
                        <a href="{{ route('agent.dashboard') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-primary-light text-white font-semibold rounded-lg hover:shadow-xl transition-all">
                            <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>
                            Back to Dashboard
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
