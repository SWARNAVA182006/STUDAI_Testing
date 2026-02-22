@extends('layouts.app')

@section('title', $project->title . ' - Talent Marketplace')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('marketplace.index') }}" class="text-gray-500 hover:text-indigo-600">
                        Marketplace
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <a href="{{ route('marketplace.projects') }}" class="text-gray-500 hover:text-indigo-600 ml-1 md:ml-2">
                            Projects
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700 ml-1 md:ml-2 font-medium truncate max-w-xs">
                            {{ $project->title }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="lg:w-2/3">
                <!-- Project Header -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        @if($project->is_urgent)
                            <span class="px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full">
                                🔥 Urgent
                            </span>
                        @endif
                        @if($project->is_featured)
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">
                                ⭐ Featured
                            </span>
                        @endif
                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm font-medium rounded-full">
                            {{ ucwords(str_replace('-', ' ', $project->category)) }}
                        </span>
                        <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                            {{ $project->project_type == 'fixed_price' ? 'Fixed Price' : 'Hourly' }}
                        </span>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                        {{ $project->title }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-4 text-gray-500 text-sm">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Posted {{ $project->published_at?->diffForHumans() ?? 'Recently' }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            {{ $project->allows_remote ? 'Remote' : ($project->location ?? 'On-site') }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ $project->proposals_count ?? 0 }} proposals
                        </span>
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Project Description</h2>
                    <div class="prose prose-indigo max-w-none text-gray-600">
                        {!! nl2br(e($project->description)) !!}
                    </div>
                </div>

                <!-- Requirements -->
                @if($project->requirements)
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Requirements</h2>
                        <div class="prose prose-indigo max-w-none text-gray-600">
                            {!! nl2br(e($project->requirements)) !!}
                        </div>
                    </div>
                @endif

                <!-- Deliverables -->
                @if($project->deliverables)
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Expected Deliverables</h2>
                        <div class="prose prose-indigo max-w-none text-gray-600">
                            {!! nl2br(e($project->deliverables)) !!}
                        </div>
                    </div>
                @endif

                <!-- Skills Required -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Skills Required</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($project->skills_required ?? [] as $skill)
                            <span class="px-3 py-1.5 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-full">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Submit Proposal Section -->
                @auth
                    @if(auth()->id() !== $project->employer_id)
                        <div class="bg-white rounded-xl shadow-md p-6" id="submit-proposal">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Submit a Proposal</h2>
                            
                            @if($hasSubmitted ?? false)
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                    <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="font-semibold text-green-800">Proposal Submitted!</h3>
                                    <p class="text-green-600 text-sm mt-1">You've already submitted a proposal for this project.</p>
                                    <a href="{{ route('marketplace.freelancer.proposals') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm mt-2 inline-block">
                                        View My Proposals →
                                    </a>
                                </div>
                            @else
                                <form action="{{ route('marketplace.freelancer.submit-proposal', $project) }}" method="POST" class="space-y-6">
                                    @csrf
                                    
                                    <!-- Cover Letter -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Letter</label>
                                        <textarea name="cover_letter" rows="6" required
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                  placeholder="Introduce yourself and explain why you're the best fit for this project...">{{ old('cover_letter') }}</textarea>
                                        @error('cover_letter')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Bid Amount -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Your Bid (₹)
                                            </label>
                                            <input type="number" name="bid_amount" value="{{ old('bid_amount') }}" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                   placeholder="{{ $project->project_type == 'hourly' ? 'Hourly rate' : 'Total project cost' }}">
                                            @error('bid_amount')
                                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Estimated Duration (days)
                                            </label>
                                            <input type="number" name="estimated_days" value="{{ old('estimated_days') }}" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                   placeholder="Number of days">
                                            @error('estimated_days')
                                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- AI Assistance -->
                                    <div class="bg-indigo-50 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                                <span class="text-indigo-700 font-medium">AI Proposal Helper</span>
                                            </div>
                                            <button type="button" onclick="generateAIProposal()" 
                                                    class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                                                Generate Suggestion
                                            </button>
                                        </div>
                                        <p class="text-indigo-600 text-sm mt-2">Get AI-powered suggestions based on your profile and project requirements.</p>
                                    </div>

                                    <button type="submit" 
                                            class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                        Submit Proposal
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="bg-white rounded-xl shadow-md p-6 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <h3 class="font-semibold text-gray-900 mb-2">Sign in to Submit a Proposal</h3>
                        <p class="text-gray-500 mb-4">Create an account or sign in to apply for this project.</p>
                        <div class="flex gap-4 justify-center">
                            <a href="{{ route('login') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                Sign In
                            </a>
                            <a href="{{ route('register') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Register
                            </a>
                        </div>
                    </div>
                @endauth
            </div>

            <!-- Sidebar -->
            <div class="lg:w-1/3">
                <!-- Budget & Details -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Project Details</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-3 border-b border-gray-100">
                            <span class="text-gray-600">Budget</span>
                            <span class="font-bold text-green-600 text-lg">
                                @if($project->project_type == 'hourly')
                                    ₹{{ number_format($project->hourly_rate_min) }} - ₹{{ number_format($project->hourly_rate_max) }}/hr
                                @else
                                    ₹{{ number_format($project->budget_min) }} - ₹{{ number_format($project->budget_max) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-gray-100">
                            <span class="text-gray-600">Experience Level</span>
                            <span class="font-medium text-gray-900">{{ ucfirst($project->experience_level ?? 'Any') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-gray-100">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-medium text-gray-900">{{ $project->estimated_duration_days ?? 'Flexible' }} {{ $project->duration_type ?? 'days' }}</span>
                        </div>
                        @if($project->deadline)
                            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                <span class="text-gray-600">Deadline</span>
                                <span class="font-medium text-gray-900">{{ $project->deadline->format('M d, Y') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-3">
                            <span class="text-gray-600">Proposals</span>
                            <span class="font-medium text-gray-900">{{ $project->proposals_count ?? 0 }}</span>
                        </div>
                    </div>

                    @auth
                        @if(auth()->id() !== $project->employer_id && !($hasSubmitted ?? false))
                            <a href="#submit-proposal" 
                               class="mt-6 block w-full px-6 py-3 bg-indigo-600 text-white font-semibold text-center rounded-lg hover:bg-indigo-700 transition">
                                Submit a Proposal
                            </a>
                        @endif
                    @endauth
                </div>

                <!-- Client Info -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">About the Client</h3>
                    
                    <div class="flex items-center mb-4">
                        <img src="{{ $project->employer->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($project->employer->name ?? 'E') }}" 
                             alt="{{ $project->employer->name ?? 'Client' }}"
                             class="w-14 h-14 rounded-full mr-4 object-cover">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $project->employer->name ?? 'Client' }}</h4>
                            @if($project->company)
                                <p class="text-gray-500 text-sm">{{ $project->company->name }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Member since {{ $project->employer->created_at?->format('M Y') ?? 'Recently' }}
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $clientStats['total_projects'] ?? 0 }} projects posted
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $clientStats['completed_contracts'] ?? 0 }} hires
                        </div>
                    </div>
                </div>

                <!-- Similar Projects -->
                @if(isset($similarProjects) && $similarProjects->isNotEmpty())
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Similar Projects</h3>
                        
                        <div class="space-y-4">
                            @foreach($similarProjects as $similar)
                                <a href="{{ route('marketplace.project.show', $similar) }}" 
                                   class="block p-3 rounded-lg hover:bg-gray-50 transition">
                                    <h4 class="font-medium text-gray-900 mb-1 line-clamp-1">{{ $similar->title }}</h4>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">{{ $similar->category }}</span>
                                        <span class="text-green-600 font-medium">₹{{ number_format($similar->budget_min) }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generateAIProposal() {
    // AI proposal generation would be implemented here
    alert('AI Proposal generation coming soon! This feature uses your profile and project requirements to suggest a compelling cover letter.');
}
</script>
@endpush
@endsection
