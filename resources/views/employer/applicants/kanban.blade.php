@extends('layouts.app')

@section('title', 'Applicant Kanban Board')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Applicant Tracking Board</h1>
                <p class="text-gray-600">Drag and drop to update application status</p>
            </div>
            <div class="flex gap-3">
                <form method="GET" action="{{ route('employer.applicants.kanban') }}" class="inline">
                    <select name="job_id" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">All Jobs</option>
                        @foreach($jobs as $job)
                            <option value="{{ $job->id }}" {{ $jobId == $job->id ? 'selected' : '' }}>
                                {{ $job->title }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('employer.applicants.index') }}" class="inline-flex items-center px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:border-pink-500 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    List View
                </a>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Pending Column -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-orange-500 text-white px-4 py-3 font-semibold flex items-center justify-between">
                    <span>Pending</span>
                    <span class="bg-white text-orange-600 px-2 py-1 rounded-full text-xs">{{ $kanbanData['pending']->count() }}</span>
                </div>
                <div class="p-4 space-y-3 min-h-[600px]" data-status="pending">
                    @foreach($kanbanData['pending'] as $application)
                        @include('employer.applicants.partials.kanban-card', ['application' => $application])
                    @endforeach
                </div>
            </div>

            <!-- Reviewing Column -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-blue-500 text-white px-4 py-3 font-semibold flex items-center justify-between">
                    <span>Reviewing</span>
                    <span class="bg-white text-blue-600 px-2 py-1 rounded-full text-xs">{{ $kanbanData['reviewing']->count() }}</span>
                </div>
                <div class="p-4 space-y-3 min-h-[600px]" data-status="reviewing">
                    @foreach($kanbanData['reviewing'] as $application)
                        @include('employer.applicants.partials.kanban-card', ['application' => $application])
                    @endforeach
                </div>
            </div>

            <!-- Shortlisted Column -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-green-500 text-white px-4 py-3 font-semibold flex items-center justify-between">
                    <span>Shortlisted</span>
                    <span class="bg-white text-green-600 px-2 py-1 rounded-full text-xs">{{ $kanbanData['shortlisted']->count() }}</span>
                </div>
                <div class="p-4 space-y-3 min-h-[600px]" data-status="shortlisted">
                    @foreach($kanbanData['shortlisted'] as $application)
                        @include('employer.applicants.partials.kanban-card', ['application' => $application])
                    @endforeach
                </div>
            </div>

            <!-- Rejected Column -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-red-500 text-white px-4 py-3 font-semibold flex items-center justify-between">
                    <span>Rejected</span>
                    <span class="bg-white text-red-600 px-2 py-1 rounded-full text-xs">{{ $kanbanData['rejected']->count() }}</span>
                </div>
                <div class="p-4 space-y-3 min-h-[600px]" data-status="rejected">
                    @foreach($kanbanData['rejected'] as $application)
                        @include('employer.applicants.partials.kanban-card', ['application' => $application])
                    @endforeach
                </div>
            </div>

            <!-- Hired Column -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-purple-500 text-white px-4 py-3 font-semibold flex items-center justify-between">
                    <span>Hired</span>
                    <span class="bg-white text-purple-600 px-2 py-1 rounded-full text-xs">{{ $kanbanData['hired']->count() }}</span>
                </div>
                <div class="p-4 space-y-3 min-h-[600px]" data-status="hired">
                    @foreach($kanbanData['hired'] as $application)
                        @include('employer.applicants.partials.kanban-card', ['application' => $application])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Initialize drag and drop for all columns
document.querySelectorAll('[data-status]').forEach(column => {
    new Sortable(column, {
        group: 'applications',
        animation: 150,
        ghostClass: 'opacity-50',
        onEnd: function(evt) {
            const applicationId = evt.item.dataset.applicationId;
            const newStatus = evt.to.dataset.status;
            
            updateApplicationStatus(applicationId, newStatus);
        }
    });
});

function updateApplicationStatus(applicationId, status) {
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
        if (!data.success) {
            alert('Failed to update status');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
        location.reload();
    });
}
</script>
@endsection
