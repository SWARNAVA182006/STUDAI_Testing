<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
    <x-ui.responsive-container size="lg" padding="none">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Build Your Career Profile</h1>
            <p class="text-lg text-gray-600">Complete your profile to unlock AI-powered job matching</p>
        </div>

        {{-- Progress Bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Profile Completion</span>
                <span class="text-sm font-bold text-primary-600">{{ $this->completionPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-primary-500 to-secondary-500 h-3 rounded-full transition-all duration-500" 
                     style="width: {{ $this->completionPercentage }}%"></div>
            </div>
        </div>

        {{-- Step Wizard Component --}}
        <x-ui.step-wizard 
            :steps="[
                ['title' => 'Resume', 'description' => 'Upload your resume'],
                ['title' => 'Basics', 'description' => 'Professional info'],
                ['title' => 'Experience', 'description' => 'Work history'],
                ['title' => 'Education', 'description' => 'Academic background'],
                ['title' => 'Skills', 'description' => 'Your expertise'],
                ['title' => 'Finish', 'description' => 'Links & preferences'],
            ]"
            :current-step="$currentStep"
            :completed-steps="range(1, $currentStep - 1)"
            :allow-jump-ahead="true"
            size="md"
            class="mb-8"
        />

        {{-- Flash Messages --}}
        @if (session()->has('message'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Main Content Card --}}
        <div class="bg-white rounded-xl shadow-xl p-8">
            
            {{-- Step 1: Resume Upload --}}
            @if ($currentStep === 1)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Upload Your Resume</h2>
                    <p class="text-gray-600 mb-6">Upload your resume and let our AI extract your professional information automatically.</p>

                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-primary-500 transition-colors">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        
                        <div wire:loading.remove wire:target="resumeFile">
                            <input type="file" wire:model="resumeFile" accept=".pdf,.doc,.docx,.txt" 
                                   class="hidden" id="resumeUpload">
                            <label for="resumeUpload" class="cursor-pointer">
                                <span class="text-primary-600 font-semibold hover:text-primary-700">Click to upload</span>
                                <span class="text-gray-600"> or drag and drop</span>
                            </label>
                            <p class="text-sm text-gray-500 mt-2">PDF, DOC, DOCX, or TXT (Max 5MB)</p>
                        </div>

                        <div wire:loading wire:target="resumeFile" class="text-primary-600">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p>Uploading...</p>
                        </div>
                    </div>

                    @error('resumeFile') 
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p> 
                    @enderror

                    @if ($resumeFile && !$analyzing && !$analysisComplete)
                        <div class="mt-6 flex justify-center">
                            <button wire:click="uploadResume" 
                                    class="px-8 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-lg hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg">
                                Analyze Resume with AI
                            </button>
                        </div>
                    @endif

                    @if ($analyzing)
                        <div class="mt-6 text-center">
                            <div class="inline-flex items-center gap-3 bg-blue-50 px-6 py-4 rounded-lg">
                                <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-blue-900 font-medium">Analyzing your resume with AI... {{ $uploadProgress }}%</span>
                            </div>
                        </div>
                    @endif

                    <div class="mt-8 text-center">
                        <button wire:click="nextStep" 
                                class="text-gray-600 hover:text-gray-900 font-medium">
                            Skip and fill manually →
                        </button>
                    </div>
                </div>
            @endif

            {{-- Step 2: Basic Info --}}
            @if ($currentStep === 2)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Basic Information</h2>
                    <p class="text-gray-600 mb-6">Tell us about your professional background</p>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Professional Headline *</label>
                            <x-ui.ai-textarea 
                                wire:model="headline"
                                field="headline"
                                placeholder="e.g., Senior Software Engineer with 8+ years in cloud architecture"
                                :max-length="255"
                                :rows="2"
                                :show-ai-button="true"
                            />
                            @error('headline') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Professional Summary *</label>
                            <x-ui.ai-textarea 
                                wire:model="summary"
                                field="summary"
                                placeholder="Write a compelling summary of your professional experience, skills, and career goals..."
                                :max-length="1000"
                                :rows="6"
                                :show-ai-button="true"
                                :show-enhance-button="true"
                                :show-tone-selector="true"
                            />
                            @error('summary') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Location</label>
                            <x-ui.smart-select
                                wire:model="current_location"
                                placeholder="Start typing your city..."
                                :allow-create="true"
                                :options="[
                                    ['value' => 'Remote', 'label' => 'Remote'],
                                    ['value' => 'New York, NY', 'label' => 'New York, NY'],
                                    ['value' => 'San Francisco, CA', 'label' => 'San Francisco, CA'],
                                    ['value' => 'Los Angeles, CA', 'label' => 'Los Angeles, CA'],
                                    ['value' => 'Chicago, IL', 'label' => 'Chicago, IL'],
                                    ['value' => 'Austin, TX', 'label' => 'Austin, TX'],
                                    ['value' => 'Seattle, WA', 'label' => 'Seattle, WA'],
                                    ['value' => 'Boston, MA', 'label' => 'Boston, MA'],
                                    ['value' => 'Denver, CO', 'label' => 'Denver, CO'],
                                    ['value' => 'Atlanta, GA', 'label' => 'Atlanta, GA'],
                                    ['value' => 'London, UK', 'label' => 'London, UK'],
                                    ['value' => 'Mumbai, India', 'label' => 'Mumbai, India'],
                                    ['value' => 'Bangalore, India', 'label' => 'Bangalore, India'],
                                    ['value' => 'Toronto, Canada', 'label' => 'Toronto, Canada'],
                                    ['value' => 'Sydney, Australia', 'label' => 'Sydney, Australia'],
                                ]"
                            />
                            @error('current_location') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- Step 3: Experience (truncated for file length - similar to above patterns) --}}
            @if ($currentStep === 3)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Work Experience</h2>
                    <p class="text-gray-600 mb-6">Add your relevant work experience. Use AI to help write descriptions and achievements!</p>
                    
                    <x-ui.experience-builder 
                        wire:model="experience"
                        :max-entries="10"
                    />
                </div>
            @endif

            {{-- Step 4: Education --}}
            @if ($currentStep === 4)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Education</h2>
                    <p class="text-gray-600 mb-6">Add your educational background</p>
                    
                    <x-ui.education-builder 
                        wire:model="education"
                        :max-entries="5"
                    />
                </div>
            @endif

            {{-- Step 5: Skills --}}
            @if ($currentStep === 5)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Skills & Expertise</h2>
                    <p class="text-gray-600 mb-6">Add your professional skills. Let AI suggest relevant skills based on your experience!</p>
                    
                    <x-ui.skill-selector 
                        wire:model="skills"
                        :max-skills="20"
                        :show-proficiency="true"
                        :show-years="true"
                        :context="$headline"
                    />
                </div>
            @endif

            {{-- Step 6: Links & Preferences --}}
            @if ($currentStep === 6)
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Almost Done!</h2>
                    <p class="text-gray-600 mb-6">Add your professional links and preferences</p>
                    
                    <div class="space-y-6">
                        {{-- Social Links Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                    LinkedIn
                                </label>
                                <input type="url" wire:model="linkedin_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="https://linkedin.com/in/...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                    GitHub
                                </label>
                                <input type="url" wire:model="github_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="https://github.com/...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-1 19.231V12H9v-1.969h2V8.188c0-2.013 1.214-3.106 3.06-3.106.859 0 1.758.154 1.758.154v1.969h-.991c-.98 0-1.287.605-1.287 1.227v1.537h2.218l-.354 1.969h-1.864v7.231h3.46C18.521 17.625 20 14.987 20 12c0-4.418-3.582-8-8-8s-8 3.582-8 8c0 3.987 2.921 7.279 6.731 7.872v-5.641z"/></svg>
                                    Portfolio
                                </label>
                                <input type="url" wire:model="portfolio_url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="https://yoursite.com">
                            </div>
                        </div>
                        
                        {{-- Salary Expectations --}}
                        <div>
                            <h3 class="text-lg font-semibold mb-4">💰 Salary Expectations</h3>
                            <x-ui.range-slider
                                wire:model="expected_salary_range"
                                :min="0"
                                :max="500000"
                                :step="5000"
                                :default-min="$expected_salary_min ?? 50000"
                                :default-max="$expected_salary_max ?? 150000"
                                :format-currency="true"
                                currency="$"
                            />
                            <p class="text-sm text-gray-500 mt-2">Drag the handles to set your expected salary range</p>
                        </div>
                        
                        {{-- Career Goals --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">🎯 Career Goals</label>
                            <x-ui.ai-textarea 
                                wire:model="career_goals"
                                field="career_goals"
                                placeholder="What are your career aspirations? Where do you see yourself in 5 years?"
                                :max-length="1000"
                                :rows="4"
                                :show-ai-button="true"
                                :show-enhance-button="true"
                            />
                        </div>
                        
                        {{-- Completion Card --}}
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center">
                                    <span class="text-2xl">🎉</span>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-green-900">Your Profile is {{ $this->completionPercentage }}% Complete!</h3>
                                    <p class="text-green-700">Click "Complete Profile" to start receiving AI-powered job matches and career recommendations.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Navigation --}}
            <div class="mt-8 flex justify-between">
                @if ($currentStep > 1)
                    <button wire:click="previousStep" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-all">
                        ← Previous
                    </button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < $totalSteps)
                    <button wire:click="nextStep" class="px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-lg hover:from-primary-700 hover:to-primary-800 font-medium shadow-lg transition-all hover:scale-105">
                        Next Step →
                    </button>
                @else
                    <button wire:click="saveProfile" class="px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 font-semibold shadow-lg text-lg transition-all hover:scale-105">
                        ✓ Complete Profile
                    </button>
                @endif
            </div>
        </div>
    </x-ui.responsive-container>
</div>
