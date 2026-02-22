<x-layouts.dashboard :title="'Dashboard'">
    {{-- Welcome Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-ink-primary">
            Welcome back, {{ explode(' ', $user->name)[0] }}
        </h1>
        <p class="mt-1 text-sm text-ink-secondary">Here's what's happening with your job search</p>
    </div>

    {{-- Stats Overview Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Applications Remaining --}}
        <div class="bg-white rounded-xl border border-surface-200 p-5 hover:shadow-elevation-1 transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-ink-tertiary uppercase tracking-wider">Applications Left</p>
                    <p class="mt-2 text-3xl font-semibold text-ink-primary">{{ $subscriptionStats['applications_remaining'] }}</p>
                    <p class="mt-1 text-xs text-ink-tertiary">This month</p>
                </div>
                <div class="p-3 bg-google-blue-50 rounded-xl">
                    <svg class="w-6 h-6 text-google-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- AI Credits --}}
        <div class="bg-white rounded-xl border border-surface-200 p-5 hover:shadow-elevation-1 transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-ink-tertiary uppercase tracking-wider">AI Credits</p>
                    <p class="mt-2 text-3xl font-semibold text-ink-primary">{{ $subscriptionStats['ai_credits_remaining'] }}</p>
                    <p class="mt-1 text-xs text-ink-tertiary">Remaining</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-xl">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Applications --}}
        <div class="bg-white rounded-xl border border-surface-200 p-5 hover:shadow-elevation-1 transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-ink-tertiary uppercase tracking-wider">Applications Sent</p>
                    <p class="mt-2 text-3xl font-semibold text-ink-primary">{{ $applicationStats['total'] }}</p>
                    <p class="mt-1 text-xs text-ink-tertiary">All time</p>
                </div>
                <div class="p-3 bg-google-green-50 rounded-xl">
                    <svg class="w-6 h-6 text-google-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Saved Jobs --}}
        <div class="bg-white rounded-xl border border-surface-200 p-5 hover:shadow-elevation-1 transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-ink-tertiary uppercase tracking-wider">Saved Jobs</p>
                    <p class="mt-2 text-3xl font-semibold text-ink-primary">{{ $savedJobsCount }}</p>
                    <p class="mt-1 text-xs text-ink-tertiary">Bookmarked</p>
                </div>
                <div class="p-3 bg-google-yellow-50 rounded-xl">
                    <svg class="w-6 h-6 text-google-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content Area (Left 2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Profile Completion Card --}}
            @if($profileCompletion < 100)
            <div class="bg-gradient-to-r from-google-blue-600 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Complete Your Profile</h3>
                        <p class="text-blue-100 text-sm mt-1">{{ $profileCompletion }}% complete</p>
                    </div>
                    <div class="text-3xl font-bold opacity-90">{{ $profileCompletion }}%</div>
                </div>
                <div class="w-full bg-white/20 rounded-full h-2 mb-4">
                    <div class="bg-white rounded-full h-2 transition-all duration-500" style="width: {{ $profileCompletion }}%"></div>
                </div>
                <p class="text-blue-100 mb-4 text-sm">A complete profile gets 3x more visibility to employers.</p>
                <a href="{{ route('profile.career.builder') }}" class="inline-flex items-center px-5 py-2.5 bg-white text-google-blue-600 font-medium text-sm rounded-lg hover:bg-blue-50 transition-colors">
                    Complete Profile
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
            @endif

            {{-- Recent Applications --}}
            <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-ink-primary">Recent Applications</h3>
                    <a href="{{ route('dashboard.applications') }}" class="text-google-blue-600 hover:text-google-blue-700 text-sm font-medium">
                        View All
                    </a>
                </div>

                @if($recentApplications->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-surface-50 rounded-xl mb-4">
                            <svg class="w-7 h-7 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-ink-primary mb-1">No applications yet</h4>
                        <p class="text-sm text-ink-tertiary mb-5">Start applying to jobs that match your skills</p>
                        <a href="{{ route('jobs.search') }}" class="inline-flex items-center px-5 py-2.5 bg-google-blue-600 text-white font-medium text-sm rounded-lg hover:bg-google-blue-700 transition-colors">
                            Browse Jobs
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-surface-100">
                        @foreach($recentApplications as $application)
                            <div class="px-6 py-4 hover:bg-surface-50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-ink-primary">
                                            {{ $application->job->title }}
                                        </h4>
                                        <p class="text-sm text-ink-secondary mt-0.5">
                                            {{ $application->job->company_name }}
                                        </p>
                                        <div class="flex items-center mt-1.5 text-xs text-ink-tertiary">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Applied {{ $application->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
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
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recommended Jobs --}}
            <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-surface-100">
                    <h3 class="text-base font-semibold text-ink-primary">Recommended For You</h3>
                    <p class="text-xs text-ink-tertiary mt-0.5">Jobs matching your profile and preferences</p>
                </div>

                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($recommendedJobs as $job)
                        <div class="border border-surface-200 rounded-xl p-4 hover:border-google-blue-200 hover:shadow-elevation-1 transition-all">
                            <h4 class="font-medium text-sm text-ink-primary mb-1">{{ $job->title }}</h4>
                            <p class="text-xs text-ink-secondary mb-2">{{ $job->company_name }}</p>
                            <div class="flex items-center text-xs text-ink-tertiary mb-3">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $job->location }}
                                @if($job->salary_min && $job->salary_max)
                                    <span class="mx-1.5 text-surface-300">|</span>
                                    <span class="text-google-green-700 font-medium">{{ number_format($job->salary_min / 100000, 1) }}L - {{ number_format($job->salary_max / 100000, 1) }}L</span>
                                @endif
                            </div>
                            <a href="{{ route('jobs.show', $job->id) }}" class="inline-flex items-center text-xs font-medium text-google-blue-600 hover:text-google-blue-700">
                                View Details
                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-8 text-sm text-ink-tertiary">
                            No recommendations available yet. Complete your profile to get personalized job matches.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar (Right 1/3) --}}
        <div class="space-y-6">
            {{-- Subscription Status Card --}}
            <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
                <div class="bg-gradient-to-r from-google-blue-600 to-purple-600 px-5 py-4 text-white">
                    <h3 class="text-sm font-semibold">Your Plan</h3>
                </div>
                <div class="px-5 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xl font-semibold text-ink-primary">{{ $subscriptionStats['plan_name'] }}</span>
                        @if($subscription->subscriptionPlan->price > 0)
                            <span class="px-2 py-0.5 bg-google-green-50 text-google-green-700 text-xs font-medium rounded-full">
                                Active
                            </span>
                        @endif
                    </div>

                    @if($subscription->subscriptionPlan->price > 0)
                        <p class="text-xs text-ink-tertiary mb-4">
                            Next billing: {{ $subscriptionStats['next_billing_date'] ? $subscriptionStats['next_billing_date']->format('M d, Y') : 'N/A' }}
                        </p>
                    @endif

                    <div class="space-y-3 mb-5">
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-secondary">Applications</span>
                            <span class="font-medium text-ink-primary">{{ $subscriptionStats['applications_remaining'] }} left</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-ink-secondary">AI Credits</span>
                            <span class="font-medium text-ink-primary">{{ $subscriptionStats['ai_credits_remaining'] }} left</span>
                        </div>
                    </div>

                    @if($subscription->subscriptionPlan->price == 0)
                        <a href="{{ route('pricing') }}" class="block w-full text-center px-4 py-2.5 bg-google-blue-600 text-white font-medium text-sm rounded-lg hover:bg-google-blue-700 transition-colors">
                            Upgrade Plan
                        </a>
                    @else
                        <a href="{{ route('payments.index') }}" class="block w-full text-center px-4 py-2.5 bg-surface-50 text-ink-secondary font-medium text-sm rounded-lg hover:bg-surface-100 transition-colors">
                            View Billing
                        </a>
                    @endif
                </div>
            </div>

            {{-- Application Status Breakdown --}}
            <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-surface-100">
                    <h3 class="text-sm font-semibold text-ink-primary">Application Status</h3>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-2.5 h-2.5 bg-google-yellow-500 rounded-full mr-2.5"></div>
                            <span class="text-sm text-ink-secondary">Pending</span>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">{{ $applicationStats['pending'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-2.5 h-2.5 bg-google-blue-500 rounded-full mr-2.5"></div>
                            <span class="text-sm text-ink-secondary">Under Review</span>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">{{ $applicationStats['reviewing'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-2.5 h-2.5 bg-google-green-500 rounded-full mr-2.5"></div>
                            <span class="text-sm text-ink-secondary">Shortlisted</span>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">{{ $applicationStats['shortlisted'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-2.5 h-2.5 bg-google-red-500 rounded-full mr-2.5"></div>
                            <span class="text-sm text-ink-secondary">Rejected</span>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">{{ $applicationStats['rejected'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-surface-100">
                    <h3 class="text-sm font-semibold text-ink-primary">Quick Actions</h3>
                </div>
                <div class="p-3 space-y-1">
                    <a href="{{ route('jobs.search') }}" class="flex items-center p-2.5 rounded-lg hover:bg-surface-50 transition-colors">
                        <div class="p-2 bg-google-blue-50 rounded-lg mr-3">
                            <svg class="w-4 h-4 text-google-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">Search Jobs</span>
                    </a>
                    <a href="{{ route('profile.career.builder') }}" class="flex items-center p-2.5 rounded-lg hover:bg-surface-50 transition-colors">
                        <div class="p-2 bg-purple-50 rounded-lg mr-3">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">Edit Profile</span>
                    </a>
                    <a href="{{ route('jobs.saved') }}" class="flex items-center p-2.5 rounded-lg hover:bg-surface-50 transition-colors">
                        <div class="p-2 bg-google-yellow-50 rounded-lg mr-3">
                            <svg class="w-4 h-4 text-google-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">Saved Jobs</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center p-2.5 rounded-lg hover:bg-surface-50 transition-colors">
                        <div class="p-2 bg-surface-50 rounded-lg mr-3">
                            <svg class="w-4 h-4 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-ink-primary">Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.dashboard>
