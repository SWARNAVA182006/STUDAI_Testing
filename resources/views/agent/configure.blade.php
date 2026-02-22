@extends('layouts.app')

@section('title', 'Configure Autonomous Agent')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-pink-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('agent.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Configure Your Agent</h1>
            </div>
            <p class="text-gray-600">Set up your AI-powered job application assistant</p>
        </div>

        <form action="{{ route('agent.configure.store') }}" method="POST" class="space-y-8">
            @csrf

            {{-- Job Search Criteria --}}
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-accent-blue rounded-lg flex items-center justify-center">
                        <i data-lucide="search" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Job Search Criteria</h2>
                        <p class="text-sm text-gray-600">Define what jobs you're looking for</p>
                    </div>
                </div>

                <div class="space-y-6">
                    {{-- Keywords --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">
                            Job Titles / Keywords <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="job_search_criteria[keywords]" 
                               id="keywords-input"
                               value="{{ old('job_search_criteria.keywords', isset($config) ? implode(', ', $config->job_search_criteria['keywords'] ?? []) : '') }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="e.g., Software Engineer, Full Stack Developer, Backend Engineer"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Separate multiple keywords with commas</p>
                        @error('job_search_criteria.keywords')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Locations --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Locations</label>
                        <input type="text" 
                               name="job_search_criteria[locations]" 
                               value="{{ old('job_search_criteria.locations', isset($config) ? implode(', ', $config->job_search_criteria['locations'] ?? []) : '') }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="e.g., San Francisco, Remote, New York">
                        <p class="text-xs text-gray-500 mt-1">Leave empty for all locations</p>
                    </div>

                    {{-- Job Types --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Job Types</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @php
                                $jobTypes = ['full_time', 'part_time', 'contract', 'internship'];
                                $selectedTypes = old('job_search_criteria.job_types', $config->job_search_criteria['job_types'] ?? []);
                            @endphp
                            @foreach($jobTypes as $type)
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($type, $selectedTypes) ? 'border-primary bg-blue-50' : 'border-gray-300' }}">
                                    <input type="checkbox" 
                                           name="job_search_criteria[job_types][]" 
                                           value="{{ $type }}"
                                           {{ in_array($type, $selectedTypes) ? 'checked' : '' }}
                                           class="rounded text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Experience Levels --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Experience Levels</label>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            @php
                                $experienceLevels = ['entry', 'junior', 'mid', 'senior', 'lead'];
                                $selectedLevels = old('job_search_criteria.experience_levels', $config->job_search_criteria['experience_levels'] ?? []);
                            @endphp
                            @foreach($experienceLevels as $level)
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($level, $selectedLevels) ? 'border-primary bg-blue-50' : 'border-gray-300' }}">
                                    <input type="checkbox" 
                                           name="job_search_criteria[experience_levels][]" 
                                           value="{{ $level }}"
                                           {{ in_array($level, $selectedLevels) ? 'checked' : '' }}
                                           class="rounded text-primary focus:ring-primary">
                                    <span class="text-sm font-medium text-gray-900">{{ ucfirst($level) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Salary Range --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Minimum Salary (₹/year)</label>
                            <input type="number" 
                                   name="job_search_criteria[min_salary]" 
                                   value="{{ old('job_search_criteria.min_salary', $config->job_search_criteria['min_salary'] ?? '') }}"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 500000"
                                   min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Maximum Salary (₹/year)</label>
                            <input type="number" 
                                   name="job_search_criteria[max_salary]" 
                                   value="{{ old('job_search_criteria.max_salary', $config->job_search_criteria['max_salary'] ?? '') }}"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="e.g., 1500000"
                                   min="0">
                        </div>
                    </div>

                    {{-- Remote Preference --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Remote Work Preference</label>
                        <select name="job_search_criteria[remote_preference]" 
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                            @php
                                $remotePreference = old('job_search_criteria.remote_preference', $config->job_search_criteria['remote_preference'] ?? 'no_preference');
                            @endphp
                            <option value="required" {{ $remotePreference === 'required' ? 'selected' : '' }}>Remote Only</option>
                            <option value="preferred" {{ $remotePreference === 'preferred' ? 'selected' : '' }}>Remote Preferred</option>
                            <option value="no_preference" {{ $remotePreference === 'no_preference' ? 'selected' : '' }}>No Preference</option>
                            <option value="on_site_only" {{ $remotePreference === 'on_site_only' ? 'selected' : '' }}>On-site Only</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Agent Preferences --}}
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-secondary-color to-green-600 rounded-lg flex items-center justify-center">
                        <i data-lucide="sliders" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Agent Preferences</h2>
                        <p class="text-sm text-gray-600">Control how your agent operates</p>
                    </div>
                </div>

                <div class="space-y-6">
                    {{-- Match Threshold --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">
                            Minimum Match Score ({{ $matchThreshold ?? 70 }}%)
                        </label>
                        <input type="range" 
                               name="preferences[match_threshold]" 
                               id="match-threshold"
                               value="{{ old('preferences.match_threshold', $config->preferences['match_threshold'] ?? 70) }}"
                               min="50" 
                               max="95" 
                               step="5"
                               class="w-full"
                               oninput="document.querySelector('label[for=match-threshold] span').textContent = this.value + '%'">
                        <p class="text-xs text-gray-500 mt-1">Only apply to jobs with match score above this threshold</p>
                    </div>

                    {{-- Daily Application Limit --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Daily Application Limit</label>
                        <input type="number" 
                               name="daily_application_limit" 
                               value="{{ old('daily_application_limit', $config->daily_application_limit ?? 10) }}"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                               min="1" 
                               max="50"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Maximum applications per day (1-50)</p>
                    </div>

                    {{-- Toggles --}}
                    <div class="space-y-4">
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <p class="font-semibold text-gray-900">Apply to External Jobs</p>
                                <p class="text-sm text-gray-600">Submit applications to jobs on external platforms (LinkedIn, etc.)</p>
                            </div>
                            <input type="checkbox" 
                                   name="preferences[apply_to_external_jobs]" 
                                   value="1"
                                   {{ old('preferences.apply_to_external_jobs', $config->preferences['apply_to_external_jobs'] ?? true) ? 'checked' : '' }}
                                   class="toggle-checkbox">
                        </label>

                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <p class="font-semibold text-gray-900">Auto-Customize Resume</p>
                                <p class="text-sm text-gray-600">Automatically customize resume for each job using AI</p>
                            </div>
                            <input type="checkbox" 
                                   name="preferences[auto_customize_resume]" 
                                   value="1"
                                   {{ old('preferences.auto_customize_resume', $config->preferences['auto_customize_resume'] ?? true) ? 'checked' : '' }}
                                   class="toggle-checkbox">
                        </label>

                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <p class="font-semibold text-gray-900">Generate Cover Letter</p>
                                <p class="text-sm text-gray-600">Automatically generate tailored cover letters</p>
                            </div>
                            <input type="checkbox" 
                                   name="preferences[generate_cover_letter]" 
                                   value="1"
                                   {{ old('preferences.generate_cover_letter', $config->preferences['generate_cover_letter'] ?? true) ? 'checked' : '' }}
                                   class="toggle-checkbox">
                        </label>

                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <p class="font-semibold text-gray-900">Require Approval</p>
                                <p class="text-sm text-gray-600">Review and approve applications before submission</p>
                            </div>
                            <input type="checkbox" 
                                   name="require_approval" 
                                   value="1"
                                   {{ old('require_approval', $config->require_approval ?? false) ? 'checked' : '' }}
                                   class="toggle-checkbox">
                        </label>

                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                            <div>
                                <p class="font-semibold text-gray-900">Auto Follow-Up</p>
                                <p class="text-sm text-gray-600">Automatically follow up on applications after N days</p>
                            </div>
                            <input type="checkbox" 
                                   name="auto_follow_up" 
                                   value="1"
                                   {{ old('auto_follow_up', $config->auto_follow_up ?? false) ? 'checked' : '' }}
                                   class="toggle-checkbox"
                                   onchange="document.getElementById('follow-up-days').disabled = !this.checked">
                        </label>

                        <div id="follow-up-days-container" class="ml-8">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Follow-up After (days)</label>
                            <input type="number" 
                                   id="follow-up-days"
                                   name="follow_up_days" 
                                   value="{{ old('follow_up_days', $config->follow_up_days ?? 7) }}"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent"
                                   min="1" 
                                   max="30"
                                   {{ old('auto_follow_up', $config->auto_follow_up ?? false) ? '' : 'disabled' }}>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Hours --}}
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-accent-yellow to-orange-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Active Hours</h2>
                        <p class="text-sm text-gray-600">When should the agent run?</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Start Hour (24h format)</label>
                            <select name="active_hours[start]" 
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ $i }}" {{ old('active_hours.start', $config->active_hours['start'] ?? 9) == $i ? 'selected' : '' }}>
                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">End Hour (24h format)</label>
                            <select name="active_hours[end]" 
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent">
                                @for($i = 0; $i < 24; $i++)
                                    <option value="{{ $i }}" {{ old('active_hours.end', $config->active_hours['end'] ?? 18) == $i ? 'selected' : '' }}>
                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Active Days</label>
                        <div class="grid grid-cols-7 gap-2">
                            @php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                $selectedDays = old('active_hours.days', $config->active_hours['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
                            @endphp
                            @foreach($days as $day)
                                <label class="flex flex-col items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($day, $selectedDays) ? 'border-primary bg-blue-50' : 'border-gray-300' }}">
                                    <input type="checkbox" 
                                           name="active_hours[days][]" 
                                           value="{{ $day }}"
                                           {{ in_array($day, $selectedDays) ? 'checked' : '' }}
                                           class="mb-1 rounded text-primary focus:ring-primary">
                                    <span class="text-xs font-medium text-gray-900">{{ substr($day, 0, 3) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Learning & Notifications --}}
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="brain" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Learning & Notifications</h2>
                        <p class="text-sm text-gray-600">AI optimization and updates</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                        <div>
                            <p class="font-semibold text-gray-900">Enable Learning</p>
                            <p class="text-sm text-gray-600">Let AI learn from outcomes and optimize strategy</p>
                        </div>
                        <input type="checkbox" 
                               name="enable_learning" 
                               value="1"
                               {{ old('enable_learning', $config->enable_learning ?? true) ? 'checked' : '' }}
                               class="toggle-checkbox">
                    </label>

                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-lg cursor-pointer">
                        <div>
                            <p class="font-semibold text-gray-900">Daily Email Digest</p>
                            <p class="text-sm text-gray-600">Receive daily summary of agent activity</p>
                        </div>
                        <input type="checkbox" 
                               name="send_digest" 
                               value="1"
                               {{ old('send_digest', $config->send_digest ?? true) ? 'checked' : '' }}
                               class="toggle-checkbox">
                    </label>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('agent.dashboard') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-primary to-primary-light text-white font-semibold rounded-lg hover:shadow-xl transition-all">
                    Save Configuration
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    // Update match threshold display
    const matchThreshold = document.getElementById('match-threshold');
    if (matchThreshold) {
        matchThreshold.addEventListener('input', function() {
            this.previousElementSibling.textContent = 'Minimum Match Score (' + this.value + '%)';
        });
    }

    // Convert comma-separated keywords to array
    document.querySelector('form').addEventListener('submit', function(e) {
        const keywordsInput = document.getElementById('keywords-input');
        if (keywordsInput && keywordsInput.value) {
            // Create hidden inputs for each keyword
            const keywords = keywordsInput.value.split(',').map(k => k.trim()).filter(k => k);
            keywords.forEach((keyword, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `job_search_criteria[keywords][${index}]`;
                input.value = keyword;
                this.appendChild(input);
            });
            // Disable original input to prevent double submission
            keywordsInput.disabled = true;
        }

        // Convert locations
        const locationsInput = document.querySelector('input[name="job_search_criteria[locations]"]');
        if (locationsInput && locationsInput.value) {
            const locations = locationsInput.value.split(',').map(l => l.trim()).filter(l => l);
            locations.forEach((location, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `job_search_criteria[locations][${index}]`;
                input.value = location;
                this.appendChild(input);
            });
            locationsInput.disabled = true;
        }
    });
</script>
@endpush

@push('styles')
<style>
    .toggle-checkbox {
        @apply relative w-12 h-6 appearance-none bg-gray-300 rounded-full cursor-pointer transition-colors;
    }
    .toggle-checkbox:checked {
        @apply bg-primary;
    }
    .toggle-checkbox::before {
        content: '';
        @apply absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform;
    }
    .toggle-checkbox:checked::before {
        @apply translate-x-6;
    }
</style>
@endpush
@endsection
