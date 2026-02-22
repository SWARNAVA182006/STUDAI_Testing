<x-layouts.dashboard :title="'My Applications'">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink-primary">My Applications</h1>
                <p class="mt-1 text-sm text-ink-secondary">Track all your job applications in one place</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-surface-200 rounded-lg text-sm font-medium text-ink-secondary hover:bg-surface-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    {{-- Filters and Search --}}
    <div class="bg-white rounded-xl border border-surface-200 p-5 mb-6">
        <form method="GET" action="{{ route('dashboard.applications') }}" class="flex flex-col md:flex-row gap-4">
            {{-- Status Filter Tabs --}}
            <div class="flex-1">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('dashboard.applications', ['status' => 'all']) }}"
                       class="px-3.5 py-1.5 rounded-lg font-medium text-sm transition-colors {{ ($status ?? 'all') === 'all' ? 'bg-google-blue-600 text-white' : 'bg-surface-100 text-ink-secondary hover:bg-surface-200' }}">
                        All ({{ $statusCounts['all'] }})
                    </a>
                    <a href="{{ route('dashboard.applications', ['status' => 'pending']) }}"
                       class="px-3.5 py-1.5 rounded-lg font-medium text-sm transition-colors {{ $status === 'pending' ? 'bg-google-yellow-600 text-white' : 'bg-surface-100 text-ink-secondary hover:bg-surface-200' }}">
                        Pending ({{ $statusCounts['pending'] }})
                    </a>
                    <a href="{{ route('dashboard.applications', ['status' => 'reviewing']) }}"
                       class="px-3.5 py-1.5 rounded-lg font-medium text-sm transition-colors {{ $status === 'reviewing' ? 'bg-google-blue-600 text-white' : 'bg-surface-100 text-ink-secondary hover:bg-surface-200' }}">
                        Reviewing ({{ $statusCounts['reviewing'] }})
                    </a>
                    <a href="{{ route('dashboard.applications', ['status' => 'shortlisted']) }}"
                       class="px-3.5 py-1.5 rounded-lg font-medium text-sm transition-colors {{ $status === 'shortlisted' ? 'bg-google-green-600 text-white' : 'bg-surface-100 text-ink-secondary hover:bg-surface-200' }}">
                        Shortlisted ({{ $statusCounts['shortlisted'] }})
                    </a>
                    <a href="{{ route('dashboard.applications', ['status' => 'rejected']) }}"
                       class="px-3.5 py-1.5 rounded-lg font-medium text-sm transition-colors {{ $status === 'rejected' ? 'bg-google-red-600 text-white' : 'bg-surface-100 text-ink-secondary hover:bg-surface-200' }}">
                        Rejected ({{ $statusCounts['rejected'] }})
                    </a>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="flex gap-2">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search by job title or company..."
                           class="pl-10 pr-4 py-2 border border-surface-200 rounded-lg text-sm focus:ring-2 focus:ring-google-blue-100 focus:border-google-blue-300 w-64">
                    <svg class="w-4 h-4 text-ink-tertiary absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="px-5 py-2 bg-google-blue-600 text-white font-medium text-sm rounded-lg hover:bg-google-blue-700 transition-colors">
                    Search
                </button>
                @if($search || ($status && $status !== 'all'))
                    <a href="{{ route('dashboard.applications') }}" class="px-4 py-2 bg-surface-100 text-ink-secondary font-medium text-sm rounded-lg hover:bg-surface-200 transition-colors">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Applications List --}}
    <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
        @if($applications->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-16 px-4">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-surface-50 rounded-xl mb-4">
                    <svg class="w-7 h-7 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-medium text-ink-primary mb-1">No applications found</h3>
                <p class="text-sm text-ink-tertiary mb-5">
                    @if($search || ($status && $status !== 'all'))
                        Try adjusting your filters or search terms.
                    @else
                        Start applying to jobs that match your skills and interests.
                    @endif
                </p>
                <a href="{{ route('jobs.search') }}" class="inline-flex items-center px-5 py-2.5 bg-google-blue-600 text-white font-medium text-sm rounded-lg hover:bg-google-blue-700 transition-colors">
                    Browse Jobs
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>
            </div>
        @else
            {{-- Applications Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-surface-100">
                    <thead class="bg-surface-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-ink-tertiary uppercase tracking-wider">
                                Job Details
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-ink-tertiary uppercase tracking-wider">
                                Applied On
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-ink-tertiary uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-ink-tertiary uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-surface-100">
                        @foreach($applications as $application)
                            <tr class="hover:bg-surface-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-google-blue-600 to-purple-500 rounded-xl flex items-center justify-center text-white font-semibold text-sm">
                                            {{ substr($application->job->company_name, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-semibold text-ink-primary">
                                                {{ $application->job->title }}
                                            </div>
                                            <div class="text-sm text-ink-secondary">
                                                {{ $application->job->company_name }}
                                            </div>
                                            <div class="flex items-center mt-1 text-xs text-ink-tertiary">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                {{ $application->job->location }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-ink-primary">
                                        {{ $application->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-ink-tertiary">
                                        {{ $application->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($application->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-google-yellow-50 text-google-yellow-700">
                                            Pending
                                        </span>
                                    @elseif($application->status === 'reviewing')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-google-blue-50 text-google-blue-700">
                                            Under Review
                                        </span>
                                    @elseif($application->status === 'shortlisted')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-google-green-50 text-google-green-700">
                                            Shortlisted
                                        </span>
                                    @elseif($application->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-google-red-50 text-google-red-700">
                                            Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-surface-100 text-ink-secondary">
                                            {{ ucfirst($application->status) }}
                                        </span>
                                    @endif

                                    @if($application->status_updated_at)
                                        <div class="text-xs text-ink-tertiary mt-1">
                                            Updated {{ $application->status_updated_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center space-x-3">
                                        <a href="{{ route('jobs.show', $application->job->id) }}"
                                           class="text-google-blue-600 hover:text-google-blue-700 font-medium text-sm">
                                            View Job
                                        </a>
                                        @if($application->cover_letter)
                                            <span class="text-surface-300">|</span>
                                            <button onclick="viewCoverLetter({{ $application->id }})"
                                                    class="text-google-blue-600 hover:text-google-blue-700 font-medium text-sm">
                                                Cover Letter
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-surface-100">
                {{ $applications->appends(['status' => $status, 'search' => $search])->links() }}
            </div>
        @endif
    </div>

    {{-- Cover Letter Modal --}}
    <div id="coverLetterModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-elevation-3 max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
                <h3 class="text-base font-semibold text-ink-primary">Cover Letter</h3>
                <button onclick="closeCoverLetterModal()" class="p-1.5 rounded-lg text-ink-tertiary hover:bg-surface-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="coverLetterContent" class="px-6 py-6 overflow-y-auto max-h-[calc(80vh-120px)]">
            </div>
            <div class="px-6 py-4 border-t border-surface-100 flex justify-end">
                <button onclick="closeCoverLetterModal()" class="px-5 py-2 bg-surface-100 text-ink-secondary font-medium text-sm rounded-lg hover:bg-surface-200 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
    function viewCoverLetter(applicationId) {
        const modal = document.getElementById('coverLetterModal');
        const content = document.getElementById('coverLetterContent');

        content.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-google-blue-600 mx-auto"></div></div>';
        modal.classList.remove('hidden');

        setTimeout(() => {
            content.innerHTML = '<div class="prose max-w-none"><p class="text-sm text-ink-secondary whitespace-pre-wrap">Cover letter content would be loaded here from the server...</p></div>';
        }, 500);
    }

    function closeCoverLetterModal() {
        document.getElementById('coverLetterModal').classList.add('hidden');
    }
    </script>
</x-layouts.dashboard>
