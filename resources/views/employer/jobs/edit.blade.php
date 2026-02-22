<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Job Posting') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('employer.jobs.update', $job->id) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="title" class="block text-sm font-medium text-gray-700">Job Title *</label>
                                    <input type="text" name="title" id="title" value="{{ old('title', $job->title) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('title')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700">Location *</label>
                                    <input type="text" name="location" id="location" value="{{ old('location', $job->location) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('location')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="job_type" class="block text-sm font-medium text-gray-700">Job Type *</label>
                                    <select name="job_type" id="job_type" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select Type</option>
                                        <option value="full_time" {{ old('job_type', $job->job_type) === 'full_time' ? 'selected' : '' }}>Full Time</option>
                                        <option value="part_time" {{ old('job_type', $job->job_type) === 'part_time' ? 'selected' : '' }}>Part Time</option>
                                        <option value="contract" {{ old('job_type', $job->job_type) === 'contract' ? 'selected' : '' }}>Contract</option>
                                        <option value="internship" {{ old('job_type', $job->job_type) === 'internship' ? 'selected' : '' }}>Internship</option>
                                        <option value="remote" {{ old('job_type', $job->job_type) === 'remote' ? 'selected' : '' }}>Remote</option>
                                    </select>
                                    @error('job_type')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="experience_level" class="block text-sm font-medium text-gray-700">Experience Level *</label>
                                    <select name="experience_level" id="experience_level" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select Level</option>
                                        <option value="entry_level" {{ old('experience_level', $job->experience_level) === 'entry_level' ? 'selected' : '' }}>Entry Level</option>
                                        <option value="mid_level" {{ old('experience_level', $job->experience_level) === 'mid_level' ? 'selected' : '' }}>Mid Level</option>
                                        <option value="senior_level" {{ old('experience_level', $job->experience_level) === 'senior_level' ? 'selected' : '' }}>Senior Level</option>
                                        <option value="executive" {{ old('experience_level', $job->experience_level) === 'executive' ? 'selected' : '' }}>Executive</option>
                                    </select>
                                    @error('experience_level')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="expires_at" class="block text-sm font-medium text-gray-700">Expires On *</label>
                                    <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at', $job->expires_at?->format('Y-m-d')) }}" required
                                        min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('expires_at')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Salary Range -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Salary Range (Optional)</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="salary_min" class="block text-sm font-medium text-gray-700">Minimum Salary (₹/year)</label>
                                    <input type="number" name="salary_min" id="salary_min" value="{{ old('salary_min', $job->salary_min) }}"
                                        step="100000" placeholder="e.g., 500000"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('salary_min')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="salary_max" class="block text-sm font-medium text-gray-700">Maximum Salary (₹/year)</label>
                                    <input type="number" name="salary_max" id="salary_max" value="{{ old('salary_max', $job->salary_max) }}"
                                        step="100000" placeholder="e.g., 1000000"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('salary_max')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Job Details</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Job Description *</label>
                                    <textarea name="description" id="description" rows="8" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $job->description) }}</textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="responsibilities" class="block text-sm font-medium text-gray-700">Responsibilities</label>
                                    <textarea name="responsibilities" id="responsibilities" rows="6"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('responsibilities', $job->responsibilities) }}</textarea>
                                    @error('responsibilities')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="qualifications" class="block text-sm font-medium text-gray-700">Qualifications</label>
                                    <textarea name="qualifications" id="qualifications" rows="6"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('qualifications', $job->qualifications) }}</textarea>
                                    @error('qualifications')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Required Skills -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Required Skills</h3>
                            
                            <div>
                                <label for="skills-input" class="block text-sm font-medium text-gray-700">
                                    Add skills (press Enter or comma to add)
                                </label>
                                <input type="text" id="skills-input"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="e.g., JavaScript, React, Node.js">
                                
                                <div id="skills-container" class="mt-2 flex flex-wrap gap-2">
                                    <!-- Existing skills will be loaded here -->
                                </div>
                                
                                <div id="skills-hidden-inputs">
                                    <!-- Hidden inputs for existing skills -->
                                    @if(old('required_skills'))
                                        @foreach(old('required_skills') as $skill)
                                            <input type="hidden" name="required_skills[]" value="{{ $skill }}">
                                        @endforeach
                                    @elseif($job->required_skills)
                                        @foreach($job->required_skills as $skill)
                                            <input type="hidden" name="required_skills[]" value="{{ $skill }}">
                                        @endforeach
                                    @endif
                                </div>
                                
                                @error('required_skills')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Benefits -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Benefits</h3>
                            
                            <div>
                                <label for="benefits-input" class="block text-sm font-medium text-gray-700">
                                    Add benefits (press Enter or comma to add)
                                </label>
                                <input type="text" id="benefits-input"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="e.g., Health Insurance, Work from Home">
                                
                                <div id="benefits-container" class="mt-2 flex flex-wrap gap-2">
                                    <!-- Existing benefits will be loaded here -->
                                </div>
                                
                                <div id="benefits-hidden-inputs">
                                    <!-- Hidden inputs for existing benefits -->
                                    @if(old('benefits'))
                                        @foreach(old('benefits') as $benefit)
                                            <input type="hidden" name="benefits[]" value="{{ $benefit }}">
                                        @endforeach
                                    @elseif($job->benefits)
                                        @foreach($job->benefits as $benefit)
                                            <input type="hidden" name="benefits[]" value="{{ $benefit }}">
                                        @endforeach
                                    @endif
                                </div>
                                
                                @error('benefits')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Publication Status</h3>
                            
                            <div class="space-y-3">
                                <label class="flex items-start">
                                    <input type="radio" name="status" value="published" {{ old('status', $job->status) === 'published' ? 'checked' : '' }}
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700">Published</span>
                                        <span class="block text-sm text-gray-500">Job will be visible to candidates immediately</span>
                                    </span>
                                </label>

                                <label class="flex items-start">
                                    <input type="radio" name="status" value="draft" {{ old('status', $job->status) === 'draft' ? 'checked' : '' }}
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700">Save as draft</span>
                                        <span class="block text-sm text-gray-500">You can publish this job later</span>
                                    </span>
                                </label>

                                <label class="flex items-start">
                                    <input type="radio" name="status" value="closed" {{ old('status', $job->status) === 'closed' ? 'checked' : '' }}
                                        class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700">Closed</span>
                                        <span class="block text-sm text-gray-500">Job will not accept new applications</span>
                                    </span>
                                </label>
                            </div>
                            
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <div>
                                @if($job->applications_count === 0)
                                    <form action="{{ route('employer.jobs.destroy', $job->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this job posting? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete Job
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="flex gap-3">
                                <a href="{{ route('employer.jobs.show', $job->id) }}"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                    Cancel
                                </a>

                                <button type="submit"
                                    class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-blue-700 hover:to-purple-700">
                                    Update Job Posting
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Skills Management
        const existingSkills = @json(old('required_skills', $job->required_skills ?? []));
        const skillsInput = document.getElementById('skills-input');
        const skillsContainer = document.getElementById('skills-container');
        const skillsHiddenInputs = document.getElementById('skills-hidden-inputs');

        // Load existing skills
        existingSkills.forEach(skill => {
            addSkillTag(skill);
        });

        skillsInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const skill = this.value.trim().replace(',', '');
                if (skill) {
                    addSkillTag(skill);
                    this.value = '';
                }
            }
        });

        function addSkillTag(skill) {
            const tag = document.createElement('span');
            tag.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800';
            tag.innerHTML = `
                ${skill}
                <button type="button" onclick="removeSkillTag(this)" class="ml-2 text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            skillsContainer.appendChild(tag);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'required_skills[]';
            hiddenInput.value = skill;
            skillsHiddenInputs.appendChild(hiddenInput);
        }

        function removeSkillTag(button) {
            const tag = button.parentElement;
            const skill = tag.textContent.trim();
            const hiddenInputs = skillsHiddenInputs.querySelectorAll('input[name="required_skills[]"]');
            hiddenInputs.forEach(input => {
                if (input.value === skill) {
                    input.remove();
                }
            });
            tag.remove();
        }

        // Benefits Management
        const existingBenefits = @json(old('benefits', $job->benefits ?? []));
        const benefitsInput = document.getElementById('benefits-input');
        const benefitsContainer = document.getElementById('benefits-container');
        const benefitsHiddenInputs = document.getElementById('benefits-hidden-inputs');

        // Load existing benefits
        existingBenefits.forEach(benefit => {
            addBenefitTag(benefit);
        });

        benefitsInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const benefit = this.value.trim().replace(',', '');
                if (benefit) {
                    addBenefitTag(benefit);
                    this.value = '';
                }
            }
        });

        function addBenefitTag(benefit) {
            const tag = document.createElement('span');
            tag.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800';
            tag.innerHTML = `
                ${benefit}
                <button type="button" onclick="removeBenefitTag(this)" class="ml-2 text-green-600 hover:text-green-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            benefitsContainer.appendChild(tag);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'benefits[]';
            hiddenInput.value = benefit;
            benefitsHiddenInputs.appendChild(hiddenInput);
        }

        function removeBenefitTag(button) {
            const tag = button.parentElement;
            const benefit = tag.textContent.trim();
            const hiddenInputs = benefitsHiddenInputs.querySelectorAll('input[name="benefits[]"]');
            hiddenInputs.forEach(input => {
                if (input.value === benefit) {
                    input.remove();
                }
            });
            tag.remove();
        }
    </script>
    @endpush
</x-app-layout>
