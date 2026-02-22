@if($isOpen && $job)
<div class="fixed inset-0 z-50 overflow-hidden" 
     x-data="{ show: @entangle('isOpen') }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>
    
    <!-- Modal Panel -->
    <div class="absolute inset-x-0 bottom-0 max-h-[90vh] bg-white dark:bg-gray-900 rounded-t-3xl shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         style="padding-bottom: calc(var(--sab, 0) + 1rem);">
        
        <!-- Handle -->
        <div class="flex justify-center py-3">
            <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>
        
        <!-- Header -->
        <div class="px-6 pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Quick Apply</h2>
                <button wire:click="close" class="p-2 -mr-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Job Info -->
            <div class="flex items-center gap-3">
                @if($job->company?->logo)
                    <img src="{{ $job->company->logo }}" alt="{{ $job->company->name }}" class="w-10 h-10 rounded-lg object-cover">
                @else
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-pink-400 to-purple-600 flex items-center justify-center text-white font-bold">
                        {{ substr($job->company?->name ?? 'J', 0, 1) }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $job->title }}</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $job->company?->name }}</p>
                </div>
            </div>
            
            <!-- Progress Steps -->
            <div class="flex items-center gap-2 mt-4">
                @for($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex-1 h-1 rounded-full {{ $i <= $step ? 'bg-pink-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endfor
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Step {{ $step }} of {{ $totalSteps }}: {{ $currentStepName }}
            </p>
        </div>
        
        <!-- Content -->
        <div class="px-6 py-4 overflow-y-auto" style="max-height: 50vh;">
            <!-- Error Message -->
            @if($error)
                <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                </div>
            @endif
            
            <!-- Success Message -->
            @if($success)
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Application Submitted!</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">You'll be notified when the employer responds.</p>
                    <button wire:click="close" class="mt-6 px-6 py-2 bg-pink-500 text-white rounded-lg font-medium">
                        Done
                    </button>
                </div>
            @else
                <!-- Step: Resume Selection -->
                @if($currentStepName === 'Resume')
                    <div class="space-y-3">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Select Your Resume</h3>
                        
                        @forelse($resumes as $resume)
                            <button wire:click="selectResume({{ $resume->id }})"
                                    class="w-full p-4 rounded-xl border-2 text-left transition
                                           {{ $selectedResumeId === $resume->id 
                                              ? 'border-pink-500 bg-pink-50 dark:bg-pink-900/20' 
                                              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ $resume->name ?? 'Resume' }}
                                            @if($resume->is_primary)
                                                <span class="ml-2 text-xs px-2 py-0.5 bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 rounded-full">Primary</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Updated {{ $resume->updated_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    @if($selectedResumeId === $resume->id)
                                        <svg class="w-5 h-5 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <div class="text-center py-8">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">No resumes found</p>
                                <a href="{{ route('resume.create') }}" class="text-pink-500 font-medium">
                                    Upload a Resume →
                                </a>
                            </div>
                        @endforelse
                    </div>
                @endif
                
                <!-- Step: Cover Letter -->
                @if($currentStepName === 'Cover Letter')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Cover Letter</h3>
                            <button wire:click="generateAiCoverLetter" 
                                    wire:loading.attr="disabled"
                                    wire:target="generateAiCoverLetter"
                                    class="text-sm text-pink-500 font-medium flex items-center gap-1 disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span wire:loading.remove wire:target="generateAiCoverLetter">Generate with AI</span>
                                <span wire:loading wire:target="generateAiCoverLetter">Generating...</span>
                            </button>
                        </div>
                        
                        <textarea wire:model="coverLetter"
                                  rows="8"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                  placeholder="Write your cover letter here..."></textarea>
                        
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ strlen($coverLetter) }} characters
                        </p>
                    </div>
                @endif
                
                <!-- Step: Screening Questions -->
                @if($currentStepName === 'Questions')
                    <div class="space-y-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Screening Questions</h3>
                        
                        @foreach($job->screeningQuestions as $question)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $question->question }}
                                    @if($question->is_required)
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                
                                @if($question->type === 'text')
                                    <input type="text" 
                                           wire:model="answers.{{ $question->id }}"
                                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                @elseif($question->type === 'textarea')
                                    <textarea wire:model="answers.{{ $question->id }}"
                                              rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                                @elseif($question->type === 'select')
                                    <select wire:model="answers.{{ $question->id }}"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                        <option value="">Select an option</option>
                                        @foreach($question->options ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif($question->type === 'boolean')
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" wire:model="answers.{{ $question->id }}" value="yes" class="text-pink-500 focus:ring-pink-500">
                                            <span class="text-gray-700 dark:text-gray-300">Yes</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" wire:model="answers.{{ $question->id }}" value="no" class="text-pink-500 focus:ring-pink-500">
                                            <span class="text-gray-700 dark:text-gray-300">No</span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Step: Review -->
                @if($currentStepName === 'Review')
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Review Your Application</h3>
                        
                        <!-- Resume -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $resumes->firstWhere('id', $selectedResumeId)?->name ?? 'Resume' }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Cover Letter Preview -->
                        @if($coverLetter)
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Cover Letter</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ $coverLetter }}</p>
                            </div>
                        @endif
                        
                        <!-- Additional Info -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Salary Expectation (Optional)
                                </label>
                                <input type="text" 
                                       wire:model="salaryExpectation"
                                       placeholder="e.g., $80,000 - $100,000"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Availability
                                </label>
                                <select wire:model="availability"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                    <option value="immediately">Immediately</option>
                                    <option value="2_weeks">2 Weeks Notice</option>
                                    <option value="1_month">1 Month Notice</option>
                                    <option value="flexible">Flexible</option>
                                </select>
                            </div>
                            
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" 
                                       wire:model="willingToRelocate"
                                       class="w-5 h-5 rounded border-gray-300 text-pink-500 focus:ring-pink-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Willing to relocate</span>
                            </label>
                        </div>
                    </div>
                @endif
            @endif
        </div>
        
        <!-- Footer Actions -->
        @if(!$success)
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                @if($step > 1)
                    <button wire:click="previousStep"
                            class="flex-1 py-3 px-4 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Back
                    </button>
                @endif
                
                @if($step < $totalSteps)
                    <button wire:click="nextStep"
                            class="flex-1 py-3 px-4 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-medium rounded-xl hover:opacity-90 transition">
                        Continue
                    </button>
                @else
                    <button wire:click="submit"
                            wire:loading.attr="disabled"
                            wire:target="submit"
                            class="flex-1 py-3 px-4 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-medium rounded-xl hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="submit">Submit Application</span>
                        <span wire:loading wire:target="submit" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
@endif
