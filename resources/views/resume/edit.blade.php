<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Resume') }}: {{ $resume->title }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('resume.preview', $resume) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                   target="_blank">
                    <i class="fas fa-eye mr-2"></i> Preview
                </a>
                <a href="{{ route('resume.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Back to Resumes
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Editor Section -->
                <div class="lg:col-span-2 space-y-6">
                    <form id="resume-form" method="POST" action="{{ route('resume.update', $resume) }}">
                        @csrf
                        @method('PUT')

                        <!-- Basic Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Resume Title</label>
                                        <input type="text" name="title" value="{{ old('title', $resume->title) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                        <input type="text" name="full_name" value="{{ old('full_name', $resume->full_name) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" name="email" value="{{ old('email', $resume->email) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                        <input type="tel" name="phone" value="{{ old('phone', $resume->phone) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                        <input type="text" name="location" value="{{ old('location', $resume->location) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">LinkedIn URL</label>
                                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $resume->linkedin_url) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">GitHub URL</label>
                                        <input type="url" name="github_url" value="{{ old('github_url', $resume->github_url) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Portfolio URL</label>
                                        <input type="url" name="portfolio_url" value="{{ old('portfolio_url', $resume->portfolio_url) }}" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Professional Summary -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">Professional Summary</h3>
                                    <button type="button" onclick="generateSummary()" 
                                            class="text-sm text-indigo-600 hover:text-indigo-800">
                                        <i class="fas fa-magic mr-1"></i> AI Generate
                                    </button>
                                </div>
                                
                                <textarea name="professional_summary" rows="4" 
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                          placeholder="A brief summary of your professional background and goals...">{{ old('professional_summary', $resume->professional_summary) }}</textarea>
                            </div>
                        </div>

                        <!-- Experience Section -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">Work Experience</h3>
                                    <button type="button" onclick="addExperience()" 
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i> Add Experience
                                    </button>
                                </div>
                                
                                <div id="experience-list" class="space-y-4">
                                    @foreach($resume->experiences ?? [] as $index => $exp)
                                    <div class="experience-item p-4 border rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <input type="text" name="experiences[{{ $index }}][company]" value="{{ $exp['company'] }}" 
                                                   placeholder="Company Name" class="rounded-md border-gray-300">
                                            <input type="text" name="experiences[{{ $index }}][position]" value="{{ $exp['position'] }}" 
                                                   placeholder="Position" class="rounded-md border-gray-300">
                                            <input type="text" name="experiences[{{ $index }}][location]" value="{{ $exp['location'] ?? '' }}" 
                                                   placeholder="Location" class="rounded-md border-gray-300">
                                            <div class="flex gap-2">
                                                <input type="text" name="experiences[{{ $index }}][start_date]" value="{{ $exp['start_date'] }}" 
                                                       placeholder="Start (MM/YYYY)" class="flex-1 rounded-md border-gray-300">
                                                <input type="text" name="experiences[{{ $index }}][end_date]" value="{{ $exp['end_date'] ?? 'Present' }}" 
                                                       placeholder="End (MM/YYYY)" class="flex-1 rounded-md border-gray-300">
                                            </div>
                                            <div class="md:col-span-2">
                                                <textarea name="experiences[{{ $index }}][description]" rows="3" 
                                                          placeholder="Describe your responsibilities and achievements..." 
                                                          class="w-full rounded-md border-gray-300">{{ $exp['description'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                        <button type="button" onclick="removeItem(this)" 
                                                class="mt-2 text-sm text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash mr-1"></i> Remove
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Education Section -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">Education</h3>
                                    <button type="button" onclick="addEducation()" 
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                        <i class="fas fa-plus mr-2"></i> Add Education
                                    </button>
                                </div>
                                
                                <div id="education-list" class="space-y-4">
                                    @foreach($resume->education ?? [] as $index => $edu)
                                    <div class="education-item p-4 border rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <input type="text" name="education[{{ $index }}][institution]" value="{{ $edu['institution'] }}" 
                                                   placeholder="Institution Name" class="rounded-md border-gray-300">
                                            <input type="text" name="education[{{ $index }}][degree]" value="{{ $edu['degree'] }}" 
                                                   placeholder="Degree/Certification" class="rounded-md border-gray-300">
                                            <input type="text" name="education[{{ $index }}][field]" value="{{ $edu['field'] ?? '' }}" 
                                                   placeholder="Field of Study" class="rounded-md border-gray-300">
                                            <div class="flex gap-2">
                                                <input type="text" name="education[{{ $index }}][start_year]" value="{{ $edu['start_year'] ?? '' }}" 
                                                       placeholder="Start Year" class="flex-1 rounded-md border-gray-300">
                                                <input type="text" name="education[{{ $index }}][end_year]" value="{{ $edu['end_year'] ?? '' }}" 
                                                       placeholder="End Year" class="flex-1 rounded-md border-gray-300">
                                            </div>
                                        </div>
                                        <button type="button" onclick="removeItem(this)" 
                                                class="mt-2 text-sm text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash mr-1"></i> Remove
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Skills Section -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">Skills</h3>
                                    <button type="button" onclick="extractSkills()" 
                                            class="text-sm text-indigo-600 hover:text-indigo-800">
                                        <i class="fas fa-magic mr-1"></i> AI Extract
                                    </button>
                                </div>
                                
                                <input type="text" name="skills" value="{{ old('skills', is_array($resume->skills) ? implode(', ', $resume->skills) : $resume->skills) }}" 
                                       placeholder="e.g., JavaScript, React, Node.js, AWS (comma-separated)" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Template Selection -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-4">Template</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach($templates as $template)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="template_id" value="{{ $template->id }}" 
                                               {{ $resume->template_id == $template->id ? 'checked' : '' }} class="sr-only peer">
                                        <div class="border-2 rounded-lg p-4 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 hover:border-gray-400 transition">
                                            <div class="aspect-[8.5/11] bg-gray-100 rounded mb-2">
                                                @if($template->thumbnail_url)
                                                <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}" class="w-full h-full object-cover rounded">
                                                @endif
                                            </div>
                                            <p class="text-sm font-medium text-center">{{ $template->name }}</p>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 mt-6">
                            <button type="submit" 
                                    class="flex-1 py-3 px-6 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                            <a href="{{ route('resume.preview', $resume) }}" target="_blank"
                               class="flex-1 py-3 px-6 bg-gray-600 text-white text-center rounded-lg font-semibold hover:bg-gray-700 transition">
                                <i class="fas fa-eye mr-2"></i> Preview
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- AI Tools -->
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="fas fa-robot mr-2 text-indigo-600"></i> AI Tools
                            </h3>
                            <div class="space-y-3">
                                <button type="button" onclick="optimizeForJob()" 
                                        class="w-full py-2 px-4 bg-white border border-indigo-200 rounded-lg text-sm font-medium text-indigo-700 hover:bg-indigo-50 transition">
                                    <i class="fas fa-bullseye mr-2"></i> Optimize for Job
                                </button>
                                <button type="button" onclick="analyzeATS()" 
                                        class="w-full py-2 px-4 bg-white border border-indigo-200 rounded-lg text-sm font-medium text-indigo-700 hover:bg-indigo-50 transition">
                                    <i class="fas fa-chart-line mr-2"></i> ATS Score Check
                                </button>
                                <button type="button" onclick="suggestImprovements()" 
                                        class="w-full py-2 px-4 bg-white border border-indigo-200 rounded-lg text-sm font-medium text-indigo-700 hover:bg-indigo-50 transition">
                                    <i class="fas fa-lightbulb mr-2"></i> Get Suggestions
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Export</h3>
                            <div class="space-y-2">
                                <a href="{{ route('resume.export.pdf', $resume) }}" 
                                   class="block w-full py-2 px-4 bg-red-600 text-white text-center rounded-lg font-medium hover:bg-red-700 transition">
                                    <i class="fas fa-file-pdf mr-2"></i> Download PDF
                                </a>
                                <a href="{{ route('resume.export.docx', $resume) }}" 
                                   class="block w-full py-2 px-4 bg-blue-600 text-white text-center rounded-lg font-medium hover:bg-blue-700 transition">
                                    <i class="fas fa-file-word mr-2"></i> Download DOCX
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Analytics</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Views</span>
                                    <span class="font-semibold">{{ $resume->views_count ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Downloads</span>
                                    <span class="font-semibold">{{ $resume->downloads_count ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Updated</span>
                                    <span class="font-semibold">{{ $resume->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let experienceCount = {{ count($resume->experiences ?? []) }};
        let educationCount = {{ count($resume->education ?? []) }};

        function addExperience() {
            const template = `
                <div class="experience-item p-4 border rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="experiences[${experienceCount}][company]" placeholder="Company Name" class="rounded-md border-gray-300">
                        <input type="text" name="experiences[${experienceCount}][position]" placeholder="Position" class="rounded-md border-gray-300">
                        <input type="text" name="experiences[${experienceCount}][location]" placeholder="Location" class="rounded-md border-gray-300">
                        <div class="flex gap-2">
                            <input type="text" name="experiences[${experienceCount}][start_date]" placeholder="Start (MM/YYYY)" class="flex-1 rounded-md border-gray-300">
                            <input type="text" name="experiences[${experienceCount}][end_date]" placeholder="End (MM/YYYY)" class="flex-1 rounded-md border-gray-300">
                        </div>
                        <div class="md:col-span-2">
                            <textarea name="experiences[${experienceCount}][description]" rows="3" placeholder="Describe your responsibilities and achievements..." class="w-full rounded-md border-gray-300"></textarea>
                        </div>
                    </div>
                    <button type="button" onclick="removeItem(this)" class="mt-2 text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
            `;
            document.getElementById('experience-list').insertAdjacentHTML('beforeend', template);
            experienceCount++;
        }

        function addEducation() {
            const template = `
                <div class="education-item p-4 border rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="education[${educationCount}][institution]" placeholder="Institution Name" class="rounded-md border-gray-300">
                        <input type="text" name="education[${educationCount}][degree]" placeholder="Degree/Certification" class="rounded-md border-gray-300">
                        <input type="text" name="education[${educationCount}][field]" placeholder="Field of Study" class="rounded-md border-gray-300">
                        <div class="flex gap-2">
                            <input type="text" name="education[${educationCount}][start_year]" placeholder="Start Year" class="flex-1 rounded-md border-gray-300">
                            <input type="text" name="education[${educationCount}][end_year]" placeholder="End Year" class="flex-1 rounded-md border-gray-300">
                        </div>
                    </div>
                    <button type="button" onclick="removeItem(this)" class="mt-2 text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
            `;
            document.getElementById('education-list').insertAdjacentHTML('beforeend', template);
            educationCount++;
        }

        function removeItem(button) {
            button.closest('.experience-item, .education-item').remove();
        }

        async function generateSummary() {
            // Call AI endpoint to generate professional summary
            alert('AI Summary generation would call /api/resume/generate-summary');
        }

        async function extractSkills() {
            // Call AI endpoint to extract skills from experience
            alert('AI Skills extraction would call /api/resume/extract-skills');
        }

        async function optimizeForJob() {
            // Call AI endpoint to optimize resume for a specific job
            alert('AI Optimization would call /api/resume/optimize-for-job');
        }

        async function analyzeATS() {
            // Call AI endpoint to analyze ATS compatibility
            alert('ATS Analysis would call /api/resume/analyze-ats');
        }

        async function suggestImprovements() {
            // Call AI endpoint for improvement suggestions
            alert('AI Suggestions would call /api/resume/suggest-improvements');
        }
    </script>
    @endpush
</x-app-layout>
