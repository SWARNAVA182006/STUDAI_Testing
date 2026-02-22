<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Application Details') }}
            </h2>
            <a href="{{ route('employer.applicants.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Applications
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Application Header -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold mr-4">
                                    {{ strtoupper(substr($application->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">{{ $application->user->name }}</h1>
                                    <p class="text-gray-600">{{ $application->user->email }}</p>
                                    @if($application->user->profile?->phone)
                                        <p class="text-gray-600">{{ $application->user->profile->phone }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Applied on</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $application->created_at->format('M d, Y') }}</p>
                                <p class="text-sm text-gray-500">{{ $application->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        <!-- Job Info -->
                        <div class="p-4 bg-gray-50 rounded-lg mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Applied for</h3>
                            <p class="text-lg font-semibold text-gray-900">{{ $application->job->title }}</p>
                            <p class="text-gray-600">{{ $application->job->location }} • {{ ucfirst(str_replace('_', ' ', $application->job->job_type)) }}</p>
                        </div>

                        <!-- Status Timeline -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Status</h3>
                            <div class="relative">
                                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                                <div class="space-y-4">
                                    <div class="relative flex items-start">
                                        <div class="absolute left-0 w-8 h-8 rounded-full {{ $application->status === 'pending' || $application->status === 'reviewing' || $application->status === 'shortlisted' || $application->status === 'rejected' || $application->status === 'hired' ? 'bg-blue-600' : 'bg-gray-300' }} flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-12">
                                            <p class="font-medium text-gray-900">Application Submitted</p>
                                            <p class="text-sm text-gray-500">{{ $application->created_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                    </div>

                                    @if($application->status === 'reviewing' || $application->status === 'shortlisted' || $application->status === 'rejected' || $application->status === 'hired')
                                        <div class="relative flex items-start">
                                            <div class="absolute left-0 w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-12">
                                                <p class="font-medium text-gray-900">Under Review</p>
                                                <p class="text-sm text-gray-500">{{ $application->updated_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if($application->status === 'shortlisted' || $application->status === 'hired')
                                        <div class="relative flex items-start">
                                            <div class="absolute left-0 w-8 h-8 rounded-full bg-green-600 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-12">
                                                <p class="font-medium text-gray-900">Shortlisted</p>
                                                <p class="text-sm text-gray-500">{{ $application->updated_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if($application->status === 'hired')
                                        <div class="relative flex items-start">
                                            <div class="absolute left-0 w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-12">
                                                <p class="font-medium text-gray-900">Hired</p>
                                                <p class="text-sm text-gray-500">{{ $application->updated_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if($application->status === 'rejected')
                                        <div class="relative flex items-start">
                                            <div class="absolute left-0 w-8 h-8 rounded-full bg-red-600 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-12">
                                                <p class="font-medium text-gray-900">Application Rejected</p>
                                                <p class="text-sm text-gray-500">{{ $application->updated_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($application->cover_letter)
                            <!-- Cover Letter -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cover Letter</h3>
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <p class="text-gray-700 whitespace-pre-line">{{ $application->cover_letter }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Candidate Profile -->
                    @if($application->user->profile)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Candidate Profile</h2>

                            <!-- Location -->
                            @if($application->user->profile->location)
                                <div class="mb-6">
                                    <h3 class="text-sm font-medium text-gray-500 mb-2">Location</h3>
                                    <p class="text-gray-900">{{ $application->user->profile->location }}</p>
                                </div>
                            @endif

                            <!-- Experience -->
                            @if($application->user->profile->experience && count($application->user->profile->experience) > 0)
                                <div class="mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Work Experience</h3>
                                    <div class="space-y-4">
                                        @foreach($application->user->profile->experience as $exp)
                                            <div class="border-l-2 border-blue-500 pl-4">
                                                <h4 class="font-semibold text-gray-900">{{ $exp['title'] ?? 'N/A' }}</h4>
                                                <p class="text-gray-600">{{ $exp['company'] ?? 'N/A' }}</p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $exp['start_date'] ?? 'N/A' }} - {{ $exp['end_date'] ?? 'Present' }}
                                                </p>
                                                @if(isset($exp['description']))
                                                    <p class="mt-2 text-gray-700">{{ $exp['description'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Education -->
                            @if($application->user->profile->education && count($application->user->profile->education) > 0)
                                <div class="mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Education</h3>
                                    <div class="space-y-4">
                                        @foreach($application->user->profile->education as $edu)
                                            <div class="border-l-2 border-green-500 pl-4">
                                                <h4 class="font-semibold text-gray-900">{{ $edu['degree'] ?? 'N/A' }}</h4>
                                                <p class="text-gray-600">{{ $edu['institution'] ?? 'N/A' }}</p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $edu['start_date'] ?? 'N/A' }} - {{ $edu['end_date'] ?? 'Present' }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Skills -->
                            @if($application->user->profile->skills && count($application->user->profile->skills) > 0)
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Skills</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($application->user->profile->skills as $skill)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                {{ is_array($skill) ? ($skill['name'] ?? $skill) : $skill }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Notes Section -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Internal Notes</h2>
                        
                        @if($application->notes)
                            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-gray-700 whitespace-pre-line">{{ $application->notes }}</p>
                            </div>
                        @endif

                        <form action="{{ route('employer.applicants.addNote', $application->id) }}" method="POST">
                            @csrf
                            <textarea name="notes" id="notes" rows="4" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Add or update internal notes about this candidate...">{{ old('notes', $application->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="mt-2 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Save Notes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Current Status -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Current Status</h2>
                        <div class="text-center">
                            @if($application->status === 'pending')
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium bg-orange-100 text-orange-800">
                                    Pending Review
                                </span>
                            @elseif($application->status === 'reviewing')
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium bg-blue-100 text-blue-800">
                                    Under Review
                                </span>
                            @elseif($application->status === 'shortlisted')
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium bg-green-100 text-green-800">
                                    Shortlisted
                                </span>
                            @elseif($application->status === 'rejected')
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium bg-red-100 text-red-800">
                                    Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium bg-purple-100 text-purple-800">
                                    Hired
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Change Status</h2>
                        
                        <div class="space-y-2">
                            @if($application->status !== 'reviewing')
                                <form action="{{ route('employer.applicants.updateStatus', $application->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="reviewing">
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Start Review
                                    </button>
                                </form>
                            @endif

                            @if($application->status !== 'shortlisted')
                                <form action="{{ route('employer.applicants.updateStatus', $application->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="shortlisted">
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Shortlist Candidate
                                    </button>
                                </form>
                            @endif

                            @if($application->status !== 'hired')
                                <form action="{{ route('employer.applicants.updateStatus', $application->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="hired">
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Mark as Hired
                                    </button>
                                </form>
                            @endif

                            @if($application->status !== 'rejected')
                                <form action="{{ route('employer.applicants.updateStatus', $application->id) }}" method="POST" 
                                    onsubmit="return confirm('Are you sure you want to reject this application?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Reject Application
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Resume -->
                    @if($application->user->profile?->resume_path)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">Resume</h2>
                            <a href="{{ Storage::url($application->user->profile->resume_path) }}" target="_blank" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-blue-700 hover:to-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download Resume
                            </a>
                        </div>
                    @endif

                    <!-- Contact Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Contact Information</h2>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <a href="mailto:{{ $application->user->email }}" class="text-blue-600 hover:underline">
                                    {{ $application->user->email }}
                                </a>
                            </div>
                            @if($application->user->profile?->phone)
                                <div>
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <a href="tel:{{ $application->user->profile->phone }}" class="text-blue-600 hover:underline">
                                        {{ $application->user->profile->phone }}
                                    </a>
                                </div>
                            @endif
                            @if($application->user->profile?->linkedin_url)
                                <div>
                                    <p class="text-sm text-gray-500">LinkedIn</p>
                                    <a href="{{ $application->user->profile->linkedin_url }}" target="_blank" class="text-blue-600 hover:underline">
                                        View Profile
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
