@extends('layouts.app')

@section('title', 'Post New Job')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Post a New Job</h1>
            <p class="text-gray-600">Fill in the details to create your job posting</p>
        </div>

        <form action="{{ route('employer.jobs.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Basic Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('title') border-red-500 @enderror" placeholder="e.g. Senior Full Stack Developer">
                        @error('title')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                            <input type="text" name="location" value="{{ old('location') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('location') border-red-500 @enderror" placeholder="e.g. Bangalore, India">
                            @error('location')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Type *</label>
                            <select name="job_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('job_type') border-red-500 @enderror">
                                <option value="">Select Type</option>
                                <option value="full-time" {{ old('job_type') === 'full-time' ? 'selected' : '' }}>Full-time</option>
                                <option value="part-time" {{ old('job_type') === 'part-time' ? 'selected' : '' }}>Part-time</option>
                                <option value="contract" {{ old('job_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                                <option value="internship" {{ old('job_type') === 'internship' ? 'selected' : '' }}>Internship</option>
                                <option value="remote" {{ old('job_type') === 'remote' ? 'selected' : '' }}>Remote</option>
                            </select>
                            @error('job_type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level *</label>
                            <select name="experience_level" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('experience_level') border-red-500 @enderror">
                                <option value="">Select Level</option>
                                <option value="entry" {{ old('experience_level') === 'entry' ? 'selected' : '' }}>Entry Level</option>
                                <option value="mid" {{ old('experience_level') === 'mid' ? 'selected' : '' }}>Mid Level</option>
                                <option value="senior" {{ old('experience_level') === 'senior' ? 'selected' : '' }}>Senior Level</option>
                                <option value="lead" {{ old('experience_level') === 'lead' ? 'selected' : '' }}>Lead/Principal</option>
                            </select>
                            @error('experience_level')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expires On *</label>
                            <input type="date" name="expires_at" value="{{ old('expires_at', now()->addDays(30)->format('Y-m-d')) }}" required min="{{ now()->addDay()->format('Y-m-d') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('expires_at') border-red-500 @enderror">
                            @error('expires_at')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary Range -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Salary Range (Optional)</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Salary (₹/year)</label>
                        <input type="number" name="salary_min" value="{{ old('salary_min') }}" min="0" step="100000" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('salary_min') border-red-500 @enderror" placeholder="e.g. 500000">
                        @error('salary_min')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Salary (₹/year)</label>
                        <input type="number" name="salary_max" value="{{ old('salary_max') }}" min="0" step="100000" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('salary_max') border-red-500 @enderror" placeholder="e.g. 1200000">
                        @error('salary_max')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Job Description -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Job Description *</h2>
                
                <textarea name="description" rows="8" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('description') border-red-500 @enderror" placeholder="Describe the role, what the candidate will be doing, and what makes this opportunity exciting...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Responsibilities -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Responsibilities (Optional)</h2>
                
                <textarea name="responsibilities" rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('responsibilities') border-red-500 @enderror" placeholder="List the key responsibilities...">{{ old('responsibilities') }}</textarea>
                @error('responsibilities')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Qualifications -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Qualifications (Optional)</h2>
                
                <textarea name="qualifications" rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent @error('qualifications') border-red-500 @enderror" placeholder="List the required qualifications and preferred experience...">{{ old('qualifications') }}</textarea>
                @error('qualifications')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Required Skills -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Required Skills (Optional)</h2>
                <p class="text-sm text-gray-600 mb-4">Enter skills and press Enter or comma to add</p>
                
                <div id="skills-container" class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-3 border border-gray-300 rounded-lg">
                    <!-- Skills will be added here -->
                </div>
                <input type="text" id="skill-input" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Type a skill and press Enter">
            </div>

            <!-- Status -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Publication Status *</h2>
                
                <div class="space-y-3">
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-pink-500 transition-colors">
                        <input type="radio" name="status" value="published" {{ old('status', 'draft') === 'published' ? 'checked' : '' }} class="mr-3">
                        <div>
                            <p class="font-semibold text-gray-900">Publish Immediately</p>
                            <p class="text-sm text-gray-600">Job will be visible to candidates right away</p>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-pink-500 transition-colors">
                        <input type="radio" name="status" value="draft" {{ old('status', 'draft') === 'draft' ? 'checked' : '' }} class="mr-3">
                        <div>
                            <p class="font-semibold text-gray-900">Save as Draft</p>
                            <p class="text-sm text-gray-600">Review and publish later</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all">
                    Create Job Posting
                </button>
                <a href="{{ route('employer.jobs.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Skills input handler
const skillsContainer = document.getElementById('skills-container');
const skillInput = document.getElementById('skill-input');
const skills = [];

skillInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addSkill(this.value.trim());
        this.value = '';
    }
});

function addSkill(skill) {
    if (skill && !skills.includes(skill)) {
        skills.push(skill);
        
        const skillTag = document.createElement('span');
        skillTag.className = 'px-3 py-1 bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700 rounded-full text-sm font-medium flex items-center gap-2';
        skillTag.innerHTML = `
            ${skill}
            <button type="button" onclick="removeSkill('${skill}')" class="hover:text-red-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        `;
        
        skillsContainer.appendChild(skillTag);
        
        // Create hidden input
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'required_skills[]';
        hiddenInput.value = skill;
        hiddenInput.id = `skill-${skill}`;
        skillsContainer.appendChild(hiddenInput);
    }
}

function removeSkill(skill) {
    const index = skills.indexOf(skill);
    if (index > -1) {
        skills.splice(index, 1);
    }
    
    document.getElementById(`skill-${skill}`).remove();
    event.target.closest('span').remove();
}
</script>
@endsection
