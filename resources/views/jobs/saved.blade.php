@extends('layouts.app')

@section('title', 'Saved Jobs')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Saved Jobs</h1>
            <p class="text-gray-600">
                You have saved {{ $jobs->total() }} {{ Str::plural('job', $jobs->total()) }}
            </p>
        </div>

        @if($jobs->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <div class="mb-6">
                    <svg class="w-24 h-24 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">No Saved Jobs Yet</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Start exploring job opportunities and save the ones you're interested in for later review.
                </p>
                <a href="{{ route('jobs.search') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Browse Jobs
                </a>
            </div>
        @else
            <!-- Jobs Grid -->
            <div class="grid gap-6">
                @foreach($jobs as $job)
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between">
                        <!-- Job Info -->
                        <div class="flex-1 mb-4 md:mb-0">
                            <div class="flex items-start mb-3">
                                <div class="flex-shrink-0 h-14 w-14 bg-gradient-to-br from-pink-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-xl mr-4">
                                    {{ substr($job->company_name, 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-1">
                                        <a href="{{ route('jobs.show', $job->id) }}" class="hover:text-pink-600 transition-colors">
                                            {{ $job->title }}
                                        </a>
                                    </h3>
                                    <p class="text-gray-700 font-medium">{{ $job->company_name }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $job->location }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ ucwords(str_replace('-', ' ', $job->job_type)) }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    {{ ucfirst($job->experience_level) }}
                                </div>
                                @if($job->salary_min && $job->salary_max)
                                    <div class="flex items-center text-green-600 font-semibold">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ₹{{ number_format($job->salary_min / 100000, 1) }}L - ₹{{ number_format($job->salary_max / 100000, 1) }}L
                                    </div>
                                @endif
                            </div>

                            @if($job->required_skills)
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach(array_slice(json_decode($job->required_skills, true) ?? [], 0, 5) as $skill)
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                                            {{ $skill }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                Saved {{ $job->pivot->created_at->diffForHumans() }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex md:flex-col gap-3 md:ml-6">
                            <a href="{{ route('jobs.show', $job->id) }}" class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View Details
                            </a>
                            <button onclick="unsaveJob({{ $job->id }})" class="inline-flex items-center justify-center px-6 py-2.5 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:border-red-500 hover:text-red-600 transition-colors whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function unsaveJob(jobId) {
    if (!confirm('Are you sure you want to remove this job from your saved list?')) {
        return;
    }

    fetch(`/api/jobs/${jobId}/toggle-save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success !== undefined || !data.saved) {
            window.location.reload();
        } else {
            alert('Failed to remove job. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>

@endsection
