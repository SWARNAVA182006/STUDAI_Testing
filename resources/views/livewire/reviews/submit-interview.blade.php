<div class="max-w-3xl mx-auto">
    @if ($submitted)
        {{-- Success State --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Experience Shared!</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Thank you for sharing your interview experience. It will help others prepare better.
            </p>
            <a href="{{ route('companies.interviews', $company) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition">
                View Interview Experiences
            </a>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                <h1 class="text-xl font-bold">Share Interview Experience at {{ $company->name }}</h1>
                <p class="text-purple-100 text-sm mt-1">Help others prepare for their interviews</p>
            </div>

            {{-- Progress --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    @foreach (['Basic Info', 'Experience', 'Details'] as $index => $label)
                        @php $stepNum = $index + 1; @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm
                                {{ $currentStep === $stepNum ? 'bg-purple-600 text-white' : ($stepNum < $currentStep ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                                @if ($stepNum < $currentStep) ✓ @else {{ $stepNum }} @endif
                            </div>
                            <span class="hidden sm:block text-sm font-medium {{ $currentStep === $stepNum ? 'text-purple-600' : 'text-gray-500' }}">{{ $label }}</span>
                        </div>
                        @if ($index < 2)
                            <div class="flex-1 h-0.5 mx-2 {{ $stepNum < $currentStep ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Form --}}
            <div class="p-6">
                @if ($currentStep === 1)
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Position Applied For *</label>
                            <input type="text" wire:model="jobTitle" placeholder="e.g., Senior Software Engineer" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            @error('jobTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                                <input type="text" wire:model="department" placeholder="e.g., Engineering" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location</label>
                                <input type="text" wire:model="location" placeholder="e.g., New York, NY" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">How did you apply? *</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (self::APPLICATION_SOURCES as $value => $label)
                                    <button type="button" wire:click="$set('applicationSource', '{{ $value }}')" class="px-4 py-3 rounded-xl border-2 text-sm font-medium transition text-left
                                        {{ $applicationSource === $value ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 text-purple-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Interview Date</label>
                            <input type="month" wire:model="interviewDate" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                @endif

                @if ($currentStep === 2)
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Difficulty Level *</label>
                            <div class="flex items-center gap-2">
                                @foreach (self::DIFFICULTY_LABELS as $value => $label)
                                    <button type="button" wire:click="$set('difficultyRating', {{ $value }})" class="flex-1 px-4 py-3 rounded-xl border-2 text-center text-sm font-medium transition
                                        {{ $difficultyRating === $value ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 text-purple-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Overall Experience *</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button type="button" wire:click="$set('experienceRating', 'positive')" class="px-6 py-4 rounded-xl border-2 text-center transition
                                    {{ $experienceRating === 'positive' ? 'border-green-500 bg-green-50 dark:bg-green-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                                    <div class="text-3xl mb-1">😊</div>
                                    <div class="text-sm font-medium {{ $experienceRating === 'positive' ? 'text-green-700' : 'text-gray-700 dark:text-gray-300' }}">Positive</div>
                                </button>
                                <button type="button" wire:click="$set('experienceRating', 'neutral')" class="px-6 py-4 rounded-xl border-2 text-center transition
                                    {{ $experienceRating === 'neutral' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                                    <div class="text-3xl mb-1">😐</div>
                                    <div class="text-sm font-medium {{ $experienceRating === 'neutral' ? 'text-yellow-700' : 'text-gray-700 dark:text-gray-300' }}">Neutral</div>
                                </button>
                                <button type="button" wire:click="$set('experienceRating', 'negative')" class="px-6 py-4 rounded-xl border-2 text-center transition
                                    {{ $experienceRating === 'negative' ? 'border-red-500 bg-red-50 dark:bg-red-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                                    <div class="text-3xl mb-1">😞</div>
                                    <div class="text-sm font-medium {{ $experienceRating === 'negative' ? 'text-red-700' : 'text-gray-700 dark:text-gray-300' }}">Negative</div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Did you get an offer?</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button type="button" wire:click="$set('gotOffer', true)" class="px-4 py-3 rounded-xl border-2 text-center transition
                                    {{ $gotOffer === true ? 'border-green-500 bg-green-50 dark:bg-green-900/30 text-green-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                    Yes, got offer
                                </button>
                                <button type="button" wire:click="$set('gotOffer', false)" class="px-4 py-3 rounded-xl border-2 text-center transition
                                    {{ $gotOffer === false ? 'border-red-500 bg-red-50 dark:bg-red-900/30 text-red-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                    No offer
                                </button>
                                <button type="button" wire:click="$set('gotOffer', null)" class="px-4 py-3 rounded-xl border-2 text-center transition
                                    {{ $gotOffer === null ? 'border-gray-400 bg-gray-50 dark:bg-gray-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                    Still waiting
                                </button>
                            </div>
                        </div>

                        @if ($gotOffer === true)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Did you accept?</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" wire:click="$set('acceptedOffer', true)" class="px-4 py-3 rounded-xl border-2 text-center transition
                                        {{ $acceptedOffer === true ? 'border-green-500 bg-green-50 dark:bg-green-900/30 text-green-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        ✓ Accepted
                                    </button>
                                    <button type="button" wire:click="$set('acceptedOffer', false)" class="px-4 py-3 rounded-xl border-2 text-center transition
                                        {{ $acceptedOffer === false ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        ✗ Declined
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Interview Stages (select all that apply)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (self::INTERVIEW_STAGES as $value => $label)
                                    <button type="button" wire:click="toggleStage('{{ $value }}')" class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition text-left
                                        {{ in_array($value, $interviewStages) ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30 text-purple-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ in_array($value, $interviewStages) ? '✓ ' : '' }}{{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if ($currentStep === 3)
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Describe the Interview Process *
                                <span class="text-gray-400 font-normal">(min 100 characters)</span>
                            </label>
                            <textarea wire:model="interviewProcess" rows="5" placeholder="Walk us through the interview process from start to finish..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"></textarea>
                            <div class="flex justify-between mt-1">
                                @error('interviewProcess') <p class="text-sm text-red-600">{{ $message }}</p> @else <span></span> @enderror
                                <span class="text-xs text-gray-400">{{ strlen($interviewProcess) }}/5000</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Interview Questions Asked</label>
                            <div class="space-y-2">
                                @foreach ($interviewQuestions as $index => $question)
                                    <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $question }}</span>
                                        <button type="button" wire:click="removeQuestion({{ $index }})" class="text-red-500 hover:text-red-700 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex gap-2 mt-2">
                                <input type="text" wire:model="newQuestion" wire:keydown.enter.prevent="addQuestion" placeholder="Add an interview question..." class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <button type="button" wire:click="addQuestion" class="px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition">
                                    Add
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tips for Other Candidates</label>
                            <textarea wire:model="tipsForCandidates" rows="3" placeholder="What advice would you give to someone interviewing here?" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"></textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model="isAnonymous" id="anonymous" class="w-5 h-5 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <label for="anonymous" class="text-sm text-gray-700 dark:text-gray-300">Post anonymously</label>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                @if ($currentStep > 1)
                    <button wire:click="previousStep" class="inline-flex items-center gap-2 px-6 py-3 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition">
                        ← Back
                    </button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < $totalSteps)
                    <button wire:click="nextStep" class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl shadow-sm transition">
                        Next →
                    </button>
                @else
                    <button wire:click="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-sm transition">
                        <span wire:loading.remove wire:target="submit">Submit Experience</span>
                        <span wire:loading wire:target="submit">Submitting...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
