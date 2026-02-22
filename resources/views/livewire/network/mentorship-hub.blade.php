<div>
    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <x-heroicon-o-academic-cap class="h-6 w-6 text-indigo-600" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['as_mentee']['active'] }}</p>
                    <p class="text-sm text-gray-500">Active Mentors</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <x-heroicon-o-users class="h-6 w-6 text-green-600" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['as_mentor']['active'] }}</p>
                    <p class="text-sm text-gray-500">Mentees</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <x-heroicon-o-clock class="h-6 w-6 text-yellow-600" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['as_mentor']['pending'] }}</p>
                    <p class="text-sm text-gray-500">Pending Requests</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <x-heroicon-o-check-badge class="h-6 w-6 text-purple-600" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['as_mentor']['completed'] + $this->stats['as_mentee']['completed'] }}</p>
                    <p class="text-sm text-gray-500">Completed</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button wire:click="setTab('find')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'find' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                Find a Mentor
            </button>
            <button wire:click="setTab('matches')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'matches' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                My Mentorships
            </button>
            <button wire:click="setTab('requests')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'requests' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                Mentorship Requests
                @if($this->pendingRequests->count() > 0)
                    <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $this->pendingRequests->count() }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    {{-- Find a Mentor Tab --}}
    @if($tab === 'find')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-8">
                    <x-heroicon-o-academic-cap class="h-16 w-16 text-indigo-600 mx-auto mb-4" />
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Find Your Perfect Mentor</h2>
                    <p class="text-gray-500">Tell us about your goals and we'll match you with experienced professionals.</p>
                </div>

                {{-- Goals Selection --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        What are your mentorship goals? <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($this->getAvailableGoals() as $key => $label)
                            <label class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-indigo-500 transition {{ in_array($key, $selectedGoals) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                <input type="checkbox"
                                       wire:model="selectedGoals"
                                       value="{{ $key }}"
                                       class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedGoals')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Skills to Learn --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        What skills do you want to develop? <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-2 mb-3">
                        <input type="text"
                               wire:model="newSkill"
                               wire:keydown.enter.prevent="addSkill"
                               class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="e.g., Leadership, Python, Public Speaking">
                        <button wire:click="addSkill"
                                type="button"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                            Add
                        </button>
                    </div>
                    @if(!empty($desiredSkills))
                        <div class="flex flex-wrap gap-2">
                            @foreach($desiredSkills as $index => $skill)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                    {{ $skill }}
                                    <button wire:click="removeSkill({{ $index }})" class="ml-2">
                                        <x-heroicon-s-x-mark class="h-4 w-4" />
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    @endif
                    @error('desiredSkills')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Meeting Frequency --}}
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        How often would you like to meet?
                    </label>
                    <select wire:model="meetingFrequency"
                            class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="weekly">Weekly</option>
                        <option value="biweekly">Bi-weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <button wire:click="searchMentors"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition flex items-center justify-center space-x-2"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="searchMentors">
                        <x-heroicon-o-sparkles class="h-5 w-5" />
                    </span>
                    <span wire:loading wire:target="searchMentors">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="searchMentors">Find Mentors with AI</span>
                    <span wire:loading wire:target="searchMentors">Analyzing matches...</span>
                </button>

                {{-- Potential Matches --}}
                @if(!empty($potentialMatches))
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Recommended Mentors
                        </h3>
                        <div class="space-y-4">
                            @foreach($potentialMatches as $match)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-4 {{ $selectedMentorId === $match['mentor']->id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                    <div class="flex items-start space-x-4">
                                        <img src="{{ $match['mentor']->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($match['mentor']->name) }}"
                                             alt="{{ $match['mentor']->name }}"
                                             class="w-16 h-16 rounded-full">
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $match['mentor']->name }}</h4>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $match['mentor']->candidateProfile?->current_title ?? 'Professional' }}
                                                        @if($match['mentor']->candidateProfile?->years_of_experience)
                                                            • {{ $match['mentor']->candidateProfile->years_of_experience }}+ years experience
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-2xl font-bold text-indigo-600">{{ round($match['score'] * 100) }}%</div>
                                                    <p class="text-xs text-gray-500">Match Score</p>
                                                </div>
                                            </div>

                                            {{-- AI Reasoning --}}
                                            @if(isset($match['reasoning']['summary']))
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $match['reasoning']['summary'] }}
                                                </p>
                                            @endif

                                            {{-- Matched Skills --}}
                                            @if(!empty($match['matched_skills']))
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    @foreach($match['matched_skills'] as $skill)
                                                        <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                                            {{ $skill }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <button wire:click="selectMentor({{ $match['mentor']->id }})"
                                                    class="mt-4 px-4 py-2 {{ $selectedMentorId === $match['mentor']->id ? 'bg-indigo-600 text-white' : 'border border-indigo-600 text-indigo-600 hover:bg-indigo-50' }} rounded-lg transition text-sm font-medium">
                                                {{ $selectedMentorId === $match['mentor']->id ? 'Selected' : 'Select as Mentor' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($selectedMentorId)
                            <button wire:click="requestMentorship"
                                    class="mt-6 w-full py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                                Request Mentorship
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- My Mentorships Tab --}}
    @if($tab === 'matches')
        <div class="mb-4 flex items-center space-x-2">
            <button wire:click="setRoleFilter('all')"
                    class="px-3 py-1.5 text-sm font-medium rounded-full transition {{ $roleFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                All
            </button>
            <button wire:click="setRoleFilter('mentee')"
                    class="px-3 py-1.5 text-sm font-medium rounded-full transition {{ $roleFilter === 'mentee' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                As Mentee
            </button>
            <button wire:click="setRoleFilter('mentor')"
                    class="px-3 py-1.5 text-sm font-medium rounded-full transition {{ $roleFilter === 'mentor' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                As Mentor
            </button>
        </div>

        <div class="space-y-4">
            @forelse($this->mentorshipMatches as $match)
                @php
                    $isMentor = $match->mentor_id === auth()->id();
                    $otherUser = $isMentor ? $match->mentee : $match->mentor;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4">
                            <img src="{{ $otherUser->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($otherUser->name) }}"
                                 alt="{{ $otherUser->name }}"
                                 class="w-14 h-14 rounded-full">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $otherUser->name }}</h3>
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $isMentor ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ $isMentor ? 'Your Mentee' : 'Your Mentor' }}
                                    </span>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $match->status_badge_color }}-100 text-{{ $match->status_badge_color }}-700">
                                        {{ ucfirst($match->status) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $otherUser->candidateProfile?->current_title ?? 'Professional' }}
                                </p>

                                {{-- Goals --}}
                                @if($match->mentee_goals)
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500 mb-1">Goals:</p>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($match->mentee_goals as $goal)
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                    {{ $this->getAvailableGoals()[$goal] ?? $goal }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Next Meeting --}}
                                @if($match->next_meeting_at && $match->isActive())
                                    <p class="mt-2 text-sm text-indigo-600">
                                        <x-heroicon-o-calendar class="h-4 w-4 inline mr-1" />
                                        Next meeting: {{ $match->next_meeting_at->format('M d, Y') }}
                                    </p>
                                @endif

                                {{-- Milestones --}}
                                @if($match->milestones && count($match->milestones) > 0)
                                    <div class="mt-3">
                                        <p class="text-xs text-gray-500 mb-1">Recent Milestones:</p>
                                        @foreach(array_slice($match->milestones, -2) as $milestone)
                                            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                                <x-heroicon-s-check-circle class="h-4 w-4 text-green-500" />
                                                <span>{{ $milestone['title'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col items-end space-y-2">
                            <div class="text-right">
                                <div class="text-xl font-bold text-indigo-600">{{ $match->match_percentage }}%</div>
                                <p class="text-xs text-gray-500">Match Score</p>
                            </div>

                            @if($match->isActive())
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('candidate.network.messages') }}"
                                       class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition">
                                        Message
                                    </a>
                                    <button wire:click="recordMeeting({{ $match->id }})"
                                            class="px-3 py-1.5 text-sm font-medium text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition">
                                        Log Meeting
                                    </button>
                                    <button wire:click="completeMentorship({{ $match->id }})"
                                            wire:confirm="Mark this mentorship as complete?"
                                            class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                        Complete
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-academic-cap class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No active mentorships</h3>
                    <p class="text-gray-500 mb-4">Find a mentor to accelerate your career growth.</p>
                    <button wire:click="setTab('find')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        Find a Mentor
                    </button>
                </div>
            @endforelse

            {{ $this->mentorshipMatches->links() }}
        </div>
    @endif

    {{-- Mentorship Requests Tab --}}
    @if($tab === 'requests')
        <div class="space-y-4">
            @forelse($this->pendingRequests as $request)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4">
                            <img src="{{ $request->mentee->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($request->mentee->name) }}"
                                 alt="{{ $request->mentee->name }}"
                                 class="w-14 h-14 rounded-full">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $request->mentee->name }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $request->mentee->candidateProfile?->current_title ?? 'Professional' }}
                                    wants you to be their mentor
                                </p>

                                {{-- Goals --}}
                                @if($request->mentee_goals)
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500 mb-1">Their goals:</p>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($request->mentee_goals as $goal)
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                                                    {{ $this->getAvailableGoals()[$goal] ?? $goal }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- AI Reasoning --}}
                                @if($request->ai_reasoning && isset($request->ai_reasoning['summary']))
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 italic">
                                        "{{ $request->ai_reasoning['summary'] }}"
                                    </p>
                                @endif

                                <p class="mt-2 text-xs text-gray-400">
                                    Requested {{ $request->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col items-end space-y-2">
                            <div class="text-right mb-2">
                                <div class="text-xl font-bold text-indigo-600">{{ $request->match_percentage }}%</div>
                                <p class="text-xs text-gray-500">Match Score</p>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button wire:click="acceptRequest({{ $request->id }})"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                                    Accept
                                </button>
                                <button wire:click="rejectRequest({{ $request->id }})"
                                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium rounded-lg transition">
                                    Decline
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-inbox class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No pending requests</h3>
                    <p class="text-gray-500">When someone requests your mentorship, it will appear here.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>
