<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Interview Practice Session') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-6">Configure Your Practice Session</h3>

                    @if($job)
                        <div class="mb-6 p-4 border border-indigo-100 bg-indigo-50 rounded-lg">
                            <div class="flex items-start">
                                <i class="fas fa-briefcase text-indigo-600 mt-1 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-indigo-900">Interviewing for: {{ $job->title }}</p>
                                    @if($company)
                                        <p class="text-sm text-indigo-800">{{ $company->name }} @if($company->industry) &middot; {{ $company->industry }} @endif</p>
                                    @endif
                                    <p class="text-xs text-indigo-700 mt-2">We'll tailor your mock interview using this role data.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('interview.start') }}" class="space-y-6">
                        @csrf

                        @if($job)
                            <input type="hidden" name="company_id" value="{{ $company?->id }}">
                        @endif

                        <!-- Job Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Target Job Title <span class="text-red-500">*</span></label>
                            <input type="text" name="job_title" value="{{ old('job_title', $job->title ?? '') }}"
                                   placeholder="e.g., Software Engineer, Marketing Manager"
                                   required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-sm text-gray-500">We'll tailor questions to this role. Update it if you want to practice for a different title.</p>
                        </div>

                        <!-- Experience Level -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level <span class="text-red-500">*</span></label>
                            <select name="experience_level" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="entry" {{ old('experience_level') === 'entry' ? 'selected' : '' }}>Entry Level (0-2 years)</option>
                                <option value="mid" {{ old('experience_level', 'mid') === 'mid' ? 'selected' : '' }}>Mid Level (2-5 years)</option>
                                <option value="senior" {{ old('experience_level') === 'senior' ? 'selected' : '' }}>Senior Level (5-10 years)</option>
                                <option value="executive" {{ old('experience_level') === 'executive' ? 'selected' : '' }}>Executive/Leadership (10+ years)</option>
                            </select>
                        </div>

                        <!-- Number of Questions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Questions</label>
                            <select name="question_count" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="5" {{ old('question_count') == 5 ? 'selected' : '' }}>5 Questions (~15 minutes)</option>
                                <option value="10" {{ old('question_count', 10) == 10 ? 'selected' : '' }}>10 Questions (~30 minutes)</option>
                                <option value="15" {{ old('question_count') == 15 ? 'selected' : '' }}>15 Questions (~45 minutes)</option>
                                <option value="20" {{ old('question_count') == 20 ? 'selected' : '' }}>20 Questions (~60 minutes)</option>
                            </select>
                        </div>

                        <!-- Practice Focus -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Practice Focus</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @php
                                    $focusOptions = [
                                        'balanced' => ['label' => 'Balanced Mix', 'description' => 'Behavioral + technical + situational'],
                                        'behavioral' => ['label' => 'Behavioral Heavy', 'description' => 'Leadership, teamwork, culture fit'],
                                        'technical' => ['label' => 'Technical Heavy', 'description' => 'Role-specific technical depth'],
                                    ];
                                    $selectedFocus = old('focus_area', 'balanced');
                                @endphp
                                @foreach($focusOptions as $value => $option)
                                    <label class="flex">
                                        <input type="radio" name="focus_area" value="{{ $value }}" class="sr-only peer" {{ $selectedFocus === $value ? 'checked' : '' }}>
                                        <div class="flex-1 p-4 border-2 rounded-lg cursor-pointer transition text-left border-gray-200 hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50">
                                            <p class="font-medium text-gray-900">{{ $option['label'] }}</p>
                                            <p class="text-xs text-gray-600 mt-1">{{ $option['description'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-gray-500">We'll still cover all question types, but emphasize the focus you choose.</p>
                        </div>

                        <!-- Info Box -->
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                                <div class="text-sm text-blue-800 space-y-1">
                                    <p class="font-medium">What to expect:</p>
                                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                                        <li>AI-generated questions tailored to your role and level</li>
                                        <li>Type or record answers, then get instant feedback</li>
                                        <li>Receive follow-up questions to deepen your practice</li>
                                        <li>Download a detailed performance report at the end</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col-reverse md:flex-row gap-4">
                            <a href="{{ route('interview.index') }}" 
                               class="flex-1 py-3 px-6 border border-gray-300 text-center rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="flex-1 py-3 px-6 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                                <i class="fas fa-play mr-2"></i> Start Practice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
