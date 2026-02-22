<div class="max-w-3xl mx-auto">
    @if ($submitted)
        {{-- Success State --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Thank You!</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Your review has been submitted and is pending moderation. It will be visible once approved.
            </p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('companies.show', $company) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition">
                    Back to {{ $company->name }}
                </a>
            </div>
        </div>
    @else
        {{-- Step Wizard --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
                <h1 class="text-xl font-bold">Review {{ $company->name }}</h1>
                <p class="text-primary-100 text-sm mt-1">Share your experience to help others</p>
            </div>

            {{-- Progress Steps --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    @foreach (['Employment', 'Ratings', 'Review', 'Submit'] as $index => $label)
                        @php $stepNum = $index + 1; @endphp
                        <button wire:click="goToStep({{ $stepNum }})" class="flex items-center gap-2 {{ $stepNum < $currentStep ? 'cursor-pointer' : 'cursor-default' }}">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm transition
                                {{ $currentStep === $stepNum ? 'bg-primary-600 text-white' : ($stepNum < $currentStep ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                                @if ($stepNum < $currentStep)
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    {{ $stepNum }}
                                @endif
                            </div>
                            <span class="hidden sm:block text-sm font-medium {{ $currentStep === $stepNum ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $label }}
                            </span>
                        </button>
                        @if ($index < 3)
                            <div class="flex-1 h-0.5 mx-2 {{ $stepNum < $currentStep ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Form Content --}}
            <div class="p-6">
                {{-- Step 1: Employment Info --}}
                @if ($currentStep === 1)
                    <div class="space-y-6" x-data>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Job Title *</label>
                            <input type="text" wire:model="jobTitle" placeholder="e.g., Software Engineer" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            @error('jobTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                            <input type="text" wire:model="department" placeholder="e.g., Engineering, Marketing" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employment Type *</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship', 'freelance' => 'Freelance'] as $value => $label)
                                    <button type="button" wire:click="$set('employmentStatus', '{{ $value }}')" class="px-4 py-3 rounded-xl border-2 text-sm font-medium transition
                                        {{ $employmentStatus === $value ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-500' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="isCurrentEmployee" class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">I currently work here</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                                <input type="month" wire:model="startDate" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            </div>
                            @if (!$isCurrentEmployee)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                                    <input type="month" wire:model="endDate" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Step 2: Ratings --}}
                @if ($currentStep === 2)
                    <div class="space-y-8">
                        {{-- Overall Rating --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Overall Rating *</label>
                            <div class="flex items-center gap-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('overallRating', {{ $i }})" class="p-2 transition transform hover:scale-110">
                                        <svg class="w-10 h-10 {{ $i <= $overallRating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }} transition" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                @endfor
                                <span class="ml-4 text-lg font-semibold text-gray-700 dark:text-gray-300">
                                    {{ ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][$overallRating] }}
                                </span>
                            </div>
                        </div>

                        {{-- Category Ratings --}}
                        <div class="grid sm:grid-cols-2 gap-6">
                            @foreach (['cultureRating' => 'Culture & Values', 'compensationRating' => 'Compensation & Benefits', 'worklifeRating' => 'Work-Life Balance', 'growthRating' => 'Career Growth', 'managementRating' => 'Senior Management'] as $field => $label)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $label }}</label>
                                    <div class="flex items-center gap-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <button type="button" wire:click="$set('{{ $field }}', {{ $i }})" class="p-1 transition">
                                                <svg class="w-7 h-7 {{ $i <= ($this->$field ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Quick Questions --}}
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recommend to a friend?</p>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="$set('recommendToFriend', true)" class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition {{ $recommendToFriend === true ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-300' }}">
                                        👍 Yes
                                    </button>
                                    <button type="button" wire:click="$set('recommendToFriend', false)" class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition {{ $recommendToFriend === false ? 'bg-red-500 text-white' : 'bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-300' }}">
                                        👎 No
                                    </button>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Approve of CEO?</p>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="$set('ceoApproval', true)" class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition {{ $ceoApproval === true ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-300' }}">
                                        👍 Yes
                                    </button>
                                    <button type="button" wire:click="$set('ceoApproval', false)" class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition {{ $ceoApproval === false ? 'bg-red-500 text-white' : 'bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 text-gray-700 dark:text-gray-300' }}">
                                        👎 No
                                    </button>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Business outlook?</p>
                                <div class="flex gap-2">
                                    @foreach (['positive' => '📈', 'neutral' => '➡️', 'negative' => '📉'] as $value => $emoji)
                                        <button type="button" wire:click="$set('businessOutlook', '{{ $value }}')" class="flex-1 px-3 py-2 rounded-lg text-xl transition {{ $businessOutlook === $value ? 'bg-primary-500 ring-2 ring-primary-300' : 'bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500' }}">
                                            {{ $emoji }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Review Content --}}
                @if ($currentStep === 3)
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review Title</label>
                            <input type="text" wire:model="reviewTitle" placeholder='e.g., "Great place to grow your career"' class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Pros * <span class="text-gray-400 font-normal">(What you liked)</span>
                            </label>
                            <textarea wire:model="pros" rows="4" placeholder="Share the positive aspects of working here..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition resize-none"></textarea>
                            <div class="flex justify-between mt-1">
                                @error('pros') <p class="text-sm text-red-600">{{ $message }}</p> @else <span></span> @enderror
                                <span class="text-xs text-gray-400">{{ strlen($pros) }}/5000 (min 50)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Cons * <span class="text-gray-400 font-normal">(What could be improved)</span>
                            </label>
                            <textarea wire:model="cons" rows="4" placeholder="Share areas that could be improved..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition resize-none"></textarea>
                            <div class="flex justify-between mt-1">
                                @error('cons') <p class="text-sm text-red-600">{{ $message }}</p> @else <span></span> @enderror
                                <span class="text-xs text-gray-400">{{ strlen($cons) }}/5000 (min 50)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Advice to Management <span class="text-gray-400 font-normal">(Optional)</span>
                            </label>
                            <textarea wire:model="adviceToManagement" rows="3" placeholder="Any suggestions for leadership..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition resize-none"></textarea>
                        </div>
                    </div>
                @endif

                {{-- Step 4: Privacy & Submit --}}
                @if ($currentStep === 4)
                    <div class="space-y-6">
                        {{-- Summary Preview --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Review Summary</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Position:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ $jobTitle }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Rating:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ $overallRating }}/5 stars</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Employment:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ $isCurrentEmployee ? 'Current' : 'Former' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Recommends:</span>
                                    <span class="ml-2 text-gray-900 dark:text-white">{{ $recommendToFriend ? 'Yes' : 'No' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Privacy Settings --}}
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                            <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Privacy Settings
                            </h3>

                            <label class="flex items-start gap-3 cursor-pointer mb-4">
                                <input type="checkbox" wire:model.live="isAnonymous" class="mt-1 w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Post anonymously</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Your name won't be shown publicly</p>
                                </div>
                            </label>

                            @if (!$isAnonymous)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Display Name</label>
                                    <input type="text" wire:model="displayName" placeholder="How you want your name to appear" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                                </div>
                            @endif
                        </div>

                        {{-- Terms Agreement --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="agreeToTerms" class="mt-1 w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">I agree to the Community Guidelines *</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        I confirm this review is based on my genuine experience and complies with the
                                        <a href="#" class="text-primary-600 hover:underline">Terms of Service</a>.
                                    </p>
                                </div>
                            </label>
                            @error('agreeToTerms') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @error('submit') 
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-800">
                                <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                            </div>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- Footer Actions --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                @if ($currentStep > 1)
                    <button wire:click="previousStep" class="inline-flex items-center gap-2 px-6 py-3 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back
                    </button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < $totalSteps)
                    <button wire:click="nextStep" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl shadow-sm transition">
                        Next
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                @else
                    <button wire:click="submit" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="inline-flex items-center gap-2 px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-sm transition">
                        <span wire:loading.remove wire:target="submit">Submit Review</span>
                        <span wire:loading wire:target="submit" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
