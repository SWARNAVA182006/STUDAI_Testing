@extends('layouts.dashboard')

@section('title', 'Post New Project - Talent Marketplace')

@section('page-title', 'Post New Project')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Back Link -->
    <div class="mb-6">
        <a href="{{ route('marketplace.employer.dashboard') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-8">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Create a New Project</h1>
            <p class="mt-2 text-gray-500">Fill in the details below to post your project and start receiving proposals from talented freelancers.</p>
        </div>

        <form id="create-project-form" class="space-y-8">
            @csrf
            
            <!-- Basic Information -->
            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-100 pb-3">Basic Information</h2>
                
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Project Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                        placeholder="e.g., Build a Modern E-commerce Website">
                    <p class="mt-1.5 text-sm text-gray-500">A clear, descriptive title helps attract the right freelancers</p>
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
                    <select name="category" id="category" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors">
                        <option value="">Select a category</option>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="6" required minlength="50"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors resize-none"
                        placeholder="Describe your project in detail. Include goals, scope, and any specific requirements..."></textarea>
                    <div class="mt-1.5 flex items-center justify-between">
                        <p class="text-sm text-gray-500">Minimum 50 characters</p>
                        <button type="button" id="enhance-btn" class="text-sm text-studai-blue-600 hover:text-studai-blue-700 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Enhance with AI
                        </button>
                    </div>
                </div>

                <!-- Requirements -->
                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700 mb-2">Requirements</label>
                    <textarea name="requirements" id="requirements" rows="4"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors resize-none"
                        placeholder="List any specific requirements, technical specifications, or qualifications needed..."></textarea>
                </div>

                <!-- Deliverables -->
                <div>
                    <label for="deliverables" class="block text-sm font-medium text-gray-700 mb-2">Deliverables</label>
                    <textarea name="deliverables" id="deliverables" rows="4"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors resize-none"
                        placeholder="What will the freelancer deliver upon completion?"></textarea>
                </div>
            </div>

            <!-- Skills Required -->
            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-100 pb-3">Skills Required</h2>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Required Skills <span class="text-red-500">*</span></label>
                    <div id="skills-container" class="flex flex-wrap gap-2 mb-3">
                        <!-- Skills will be added here -->
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="skill-input"
                            class="flex-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                            placeholder="Type a skill and press Enter">
                        <button type="button" id="add-skill-btn" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                            Add
                        </button>
                    </div>
                    <input type="hidden" name="skills_required" id="skills-hidden">
                    <p class="mt-1.5 text-sm text-gray-500">Add 1-10 skills that are required for this project</p>
                </div>

                <!-- Experience Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="experience_level" value="entry" class="peer sr-only" required>
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-studai-blue-500 peer-checked:bg-studai-blue-50 transition-colors">
                                <div class="font-medium text-gray-900">Entry Level</div>
                                <div class="text-sm text-gray-500">New to the field</div>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="experience_level" value="intermediate" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-studai-blue-500 peer-checked:bg-studai-blue-50 transition-colors">
                                <div class="font-medium text-gray-900">Intermediate</div>
                                <div class="text-sm text-gray-500">2-5 years experience</div>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="experience_level" value="expert" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-studai-blue-500 peer-checked:bg-studai-blue-50 transition-colors">
                                <div class="font-medium text-gray-900">Expert</div>
                                <div class="text-sm text-gray-500">5+ years experience</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Budget & Timeline -->
            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-100 pb-3">Budget & Timeline</h2>
                
                <!-- Project Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Project Type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="project_type" value="fixed_price" class="peer sr-only" required checked>
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-studai-blue-500 peer-checked:bg-studai-blue-50 transition-colors">
                                <div class="font-medium text-gray-900">Fixed Price</div>
                                <div class="text-sm text-gray-500">Set budget for the project</div>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="project_type" value="hourly" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-studai-blue-500 peer-checked:bg-studai-blue-50 transition-colors">
                                <div class="font-medium text-gray-900">Hourly</div>
                                <div class="text-sm text-gray-500">Pay per hour worked</div>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="project_type" value="milestone" class="peer sr-only">
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-studai-blue-500 peer-checked:bg-studai-blue-50 transition-colors">
                                <div class="font-medium text-gray-900">Milestone</div>
                                <div class="text-sm text-gray-500">Pay per milestone</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Budget Range (Fixed Price) -->
                <div id="budget-fixed" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency <span class="text-red-500">*</span></label>
                        <select name="currency" id="currency" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors">
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="INR">INR (₹)</option>
                        </select>
                    </div>
                    <div>
                        <label for="budget_min" class="block text-sm font-medium text-gray-700 mb-2">Minimum Budget</label>
                        <input type="number" name="budget_min" id="budget_min" min="0" step="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                            placeholder="0">
                    </div>
                    <div>
                        <label for="budget_max" class="block text-sm font-medium text-gray-700 mb-2">Maximum Budget</label>
                        <input type="number" name="budget_max" id="budget_max" min="0" step="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                            placeholder="0">
                    </div>
                </div>

                <!-- Hourly Rate (Hourly) -->
                <div id="budget-hourly" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="currency-hourly" class="block text-sm font-medium text-gray-700 mb-2">Currency <span class="text-red-500">*</span></label>
                        <select name="currency_hourly" id="currency-hourly"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors">
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="INR">INR (₹)</option>
                        </select>
                    </div>
                    <div>
                        <label for="hourly_rate_min" class="block text-sm font-medium text-gray-700 mb-2">Min Hourly Rate</label>
                        <input type="number" name="hourly_rate_min" id="hourly_rate_min" min="0" step="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                            placeholder="0">
                    </div>
                    <div>
                        <label for="hourly_rate_max" class="block text-sm font-medium text-gray-700 mb-2">Max Hourly Rate</label>
                        <input type="number" name="hourly_rate_max" id="hourly_rate_max" min="0" step="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                            placeholder="0">
                    </div>
                </div>

                <!-- Duration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="estimated_duration_days" class="block text-sm font-medium text-gray-700 mb-2">Estimated Duration</label>
                        <input type="number" name="estimated_duration_days" id="estimated_duration_days" min="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                            placeholder="e.g., 14">
                    </div>
                    <div>
                        <label for="duration_type" class="block text-sm font-medium text-gray-700 mb-2">Duration Type <span class="text-red-500">*</span></label>
                        <select name="duration_type" id="duration_type" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors">
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                        </select>
                    </div>
                </div>

                <!-- Deadline -->
                <div>
                    <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">Project Deadline</label>
                    <input type="date" name="deadline" id="deadline"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                        min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    <p class="mt-1.5 text-sm text-gray-500">Optional: Set a specific deadline for project completion</p>
                </div>
            </div>

            <!-- Additional Options -->
            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 border-b border-gray-100 pb-3">Additional Options</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Remote Work -->
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="allows_remote" value="1" checked
                                class="w-5 h-5 text-studai-blue-600 border-gray-300 rounded focus:ring-studai-blue-500">
                            <span class="text-gray-700">Allow remote work</span>
                        </label>
                    </div>

                    <!-- Urgent -->
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_urgent" value="1"
                                class="w-5 h-5 text-studai-blue-600 border-gray-300 rounded focus:ring-studai-blue-500">
                            <span class="text-gray-700">Mark as urgent</span>
                        </label>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location Preference</label>
                    <input type="text" name="location" id="location"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-studai-blue-500 focus:border-studai-blue-500 transition-colors"
                        placeholder="e.g., United States, Remote, or Any">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-100">
                <button type="submit" name="publish" value="1"
                    class="flex-1 px-6 py-3 bg-studai-blue-600 text-white font-medium rounded-xl hover:bg-studai-blue-700 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Publish Project
                </button>
                <button type="submit" name="publish" value="0"
                    class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Save as Draft
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-project-form');
    const skillsContainer = document.getElementById('skills-container');
    const skillInput = document.getElementById('skill-input');
    const addSkillBtn = document.getElementById('add-skill-btn');
    const skillsHidden = document.getElementById('skills-hidden');
    const enhanceBtn = document.getElementById('enhance-btn');
    
    let skills = [];

    // Skills management
    function updateSkillsHidden() {
        skillsHidden.value = JSON.stringify(skills);
    }

    function addSkill(skill) {
        skill = skill.trim();
        if (skill && !skills.includes(skill) && skills.length < 10) {
            skills.push(skill);
            renderSkills();
            updateSkillsHidden();
        }
        skillInput.value = '';
    }

    function removeSkill(skill) {
        skills = skills.filter(s => s !== skill);
        renderSkills();
        updateSkillsHidden();
    }

    function renderSkills() {
        skillsContainer.innerHTML = skills.map(skill => `
            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-studai-blue-100 text-studai-blue-700 rounded-full text-sm font-medium">
                ${skill}
                <button type="button" onclick="window.removeSkill('${skill}')" class="ml-1 hover:text-studai-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </span>
        `).join('');
    }

    window.removeSkill = removeSkill;

    addSkillBtn.addEventListener('click', () => addSkill(skillInput.value));
    skillInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill(skillInput.value);
        }
    });

    // Project type toggle
    const projectTypeRadios = document.querySelectorAll('input[name="project_type"]');
    const budgetFixed = document.getElementById('budget-fixed');
    const budgetHourly = document.getElementById('budget-hourly');

    projectTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'hourly') {
                budgetFixed.classList.add('hidden');
                budgetHourly.classList.remove('hidden');
            } else {
                budgetFixed.classList.remove('hidden');
                budgetHourly.classList.add('hidden');
            }
        });
    });

    // AI Enhancement
    enhanceBtn.addEventListener('click', async function() {
        const description = document.getElementById('description');
        const category = document.getElementById('category');
        
        if (!description.value || description.value.length < 20) {
            alert('Please write at least 20 characters in the description first.');
            return;
        }
        if (!category.value) {
            alert('Please select a category first.');
            return;
        }

        enhanceBtn.disabled = true;
        enhanceBtn.textContent = 'Enhancing...';

        try {
            const response = await fetch('{{ route("marketplace.employer.enhance-project") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    brief: description.value,
                    category: category.value
                })
            });

            const data = await response.json();
            
            if (data.success && data.enhanced) {
                description.value = data.enhanced;
            } else {
                alert('Could not enhance description. Please try again.');
            }
        } catch (error) {
            console.error('Enhancement error:', error);
            alert('An error occurred. Please try again.');
        } finally {
            enhanceBtn.disabled = false;
            enhanceBtn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Enhance with AI
            `;
        }
    });

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (skills.length === 0) {
            alert('Please add at least one skill.');
            return;
        }

        const formData = new FormData(form);
        const publishBtn = e.submitter;
        const isPublish = publishBtn.value === '1';
        
        formData.set('publish', isPublish ? '1' : '0');
        formData.set('skills_required', JSON.stringify(skills));

        // Convert FormData to JSON
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'skills_required') {
                data[key] = skills;
            } else if (key === 'allows_remote' || key === 'is_urgent' || key === 'publish') {
                data[key] = value === '1';
            } else {
                data[key] = value;
            }
        });

        publishBtn.disabled = true;
        publishBtn.textContent = isPublish ? 'Publishing...' : 'Saving...';

        try {
            const response = await fetch('{{ route("marketplace.employer.store-project") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = result.redirect || '{{ route("marketplace.employer.dashboard") }}';
            } else {
                alert(result.message || 'An error occurred. Please try again.');
            }
        } catch (error) {
            console.error('Submit error:', error);
            alert('An error occurred. Please try again.');
        } finally {
            publishBtn.disabled = false;
            publishBtn.textContent = isPublish ? 'Publish Project' : 'Save as Draft';
        }
    });
});
</script>
@endpush
@endsection
