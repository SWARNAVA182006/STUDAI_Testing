@extends('layouts.app')

@section('title', $job->title . ' - ' . $job->company_name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('jobs.search') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Search
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Job Header Card -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-20 w-20 bg-gradient-to-br from-pink-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl mr-6">
                            {{ substr($job->company_name, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $job->title }}</h1>
                            <p class="text-xl text-gray-700 font-medium mb-4">{{ $job->company_name }}</p>
                            
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ $job->location }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ ucwords(str_replace('-', ' ', $job->job_type)) }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    {{ ucfirst($job->experience_level) }} Level
                                </div>
                                @if($job->salary_min && $job->salary_max)
                                    <div class="flex items-center font-semibold text-green-600">
                                        <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        ₹{{ number_format($job->salary_min / 100000, 1) }}L - ₹{{ number_format($job->salary_max / 100000, 1) }}L per year
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 mt-6">
                                @auth
                                    @if($hasApplied)
                                        <button disabled class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-600 font-semibold rounded-lg cursor-not-allowed">
                                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Already Applied
                                        </button>
                                    @else
                                        <button onclick="openApplicationModal()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Apply Now
                                        </button>
                                    @endif
                                    <button onclick="toggleSave({{ $job->id }})" id="save-btn" class="inline-flex items-center px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:border-pink-500 hover:text-pink-600 transition-colors">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                        </svg>
                                        Save Job
                                    </button>
                                @else
                                    <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105">
                                        Login to Apply
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Description -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Job Description</h2>
                    <div class="prose max-w-none text-gray-700">
                        {!! nl2br(e($job->description)) !!}
                    </div>
                </div>

                <!-- Required Skills -->
                @if($job->required_skills)
                <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Required Skills</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach(json_decode($job->required_skills, true) ?? [] as $skill)
                            <span class="px-4 py-2 bg-gradient-to-r from-pink-100 to-purple-100 text-purple-700 font-medium rounded-lg">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Responsibilities -->
                @if($job->responsibilities)
                <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Responsibilities</h2>
                    <div class="prose max-w-none text-gray-700">
                        {!! nl2br(e($job->responsibilities)) !!}
                    </div>
                </div>
                @endif

                <!-- Qualifications -->
                @if($job->qualifications)
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Qualifications</h2>
                    <div class="prose max-w-none text-gray-700">
                        {!! nl2br(e($job->qualifications)) !!}
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Company Info -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">About Company</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Company Name</p>
                            <p class="font-semibold text-gray-900">{{ $job->company_name }}</p>
                        </div>
                        @if($job->company)
                            @if($job->company->website)
                            <div>
                                <p class="text-sm text-gray-600">Website</p>
                                <a href="{{ $job->company->website }}" target="_blank" class="text-pink-600 hover:text-pink-800 font-medium">
                                    Visit Website →
                                </a>
                            </div>
                            @endif
                        @endif
                        <div>
                            <p class="text-sm text-gray-600">Posted</p>
                            <p class="font-semibold text-gray-900">{{ $job->created_at->diffForHumans() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Expires</p>
                            <p class="font-semibold text-gray-900">{{ $job->expires_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Similar Jobs -->
                @if($similarJobs->isNotEmpty())
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Similar Jobs</h3>
                    <div class="space-y-4">
                        @foreach($similarJobs as $similarJob)
                            <a href="{{ route('jobs.show', $similarJob->id) }}" class="block p-4 border border-gray-200 rounded-lg hover:border-pink-500 hover:shadow-md transition-all">
                                <h4 class="font-semibold text-gray-900 mb-1">{{ $similarJob->title }}</h4>
                                <p class="text-sm text-gray-600 mb-2">{{ $similarJob->company_name }}</p>
                                <div class="flex items-center text-xs text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    {{ $similarJob->location }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Application Modal -->
@auth
<div id="applicationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-pink-500 to-purple-600">
            <h3 class="text-xl font-bold text-white">Apply for {{ $job->title }}</h3>
            <button onclick="closeApplicationModal()" class="text-white/80 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="applicationForm" class="px-6 py-6 overflow-y-auto max-h-[calc(90vh-140px)]">
            <!-- Application Summary -->
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Application Summary</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Applicant Name</p>
                        <p class="font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="font-semibold text-gray-900">{{ auth()->user()->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Applying For</p>
                        <p class="font-semibold text-gray-900">{{ $job->title }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Company</p>
                        <p class="font-semibold text-gray-900">{{ $job->company_name }}</p>
                    </div>
                    @if(auth()->user()->profile?->phone)
                    <div>
                        <p class="text-xs text-gray-500">Phone</p>
                        <p class="font-semibold text-gray-900">{{ auth()->user()->profile->phone }}</p>
                    </div>
                    @endif
                    @if(auth()->user()->profile?->location)
                    <div>
                        <p class="text-xs text-gray-500">Location</p>
                        <p class="font-semibold text-gray-900">{{ auth()->user()->profile->location }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Cover Letter Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Cover Letter</label>
                    <button type="button" 
                            onclick="generateCoverLetter()" 
                            id="aiWriterBtn"
                            class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-sm font-medium rounded-lg hover:from-purple-600 hover:to-pink-600 transition-all shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span id="aiWriterText">AI Writer</span>
                    </button>
                </div>
                <textarea name="cover_letter" 
                          id="coverLetterInput"
                          rows="8" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent resize-none" 
                          placeholder="Tell the employer why you're a great fit for this role..."></textarea>
                <div class="flex items-center justify-between mt-1.5">
                    <p class="text-sm text-gray-500">A personalized cover letter increases your chances of getting noticed</p>
                    <span id="charCount" class="text-sm text-gray-400">0/2000</span>
                </div>
            </div>

            <!-- Resume Section -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Resume</label>
                @if(auth()->user()->profile?->resume_path)
                <div class="flex items-center p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">{{ auth()->user()->name }}_Resume.pdf</p>
                        <p class="text-sm text-green-600">✓ Resume attached from your profile</p>
                    </div>
                    <a href="{{ Storage::url(auth()->user()->profile->resume_path) }}" target="_blank" class="text-sm text-pink-600 hover:text-pink-800 font-medium">
                        View
                    </a>
                </div>
                @else
                <div class="flex items-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">No resume uploaded</p>
                        <p class="text-sm text-yellow-700">Upload a resume to your profile for better application success</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="text-sm text-pink-600 hover:text-pink-800 font-medium">
                        Upload Now
                    </a>
                </div>
                @endif
            </div>

            <!-- Skills Match (if available) -->
            @if($job->required_skills && auth()->user()->profile?->skills)
            @php
                $jobSkills = is_array($job->required_skills) ? $job->required_skills : (json_decode($job->required_skills, true) ?? []);
                $userSkills = is_array(auth()->user()->profile->skills) ? auth()->user()->profile->skills : (json_decode(auth()->user()->profile->skills, true) ?? []);
                $matchedSkills = array_intersect(array_map('strtolower', $jobSkills), array_map('strtolower', $userSkills));
                $matchPercentage = count($jobSkills) > 0 ? round((count($matchedSkills) / count($jobSkills)) * 100) : 0;
            @endphp
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-blue-900">Skills Match</h4>
                    <span class="text-lg font-bold text-blue-600">{{ $matchPercentage }}%</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-2 mb-3">
                    <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $matchPercentage }}%"></div>
                </div>
                @if(count($matchedSkills) > 0)
                <div class="flex flex-wrap gap-1.5">
                    @foreach($matchedSkills as $skill)
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">✓ {{ ucfirst($skill) }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            <!-- Info Box -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-purple-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm text-purple-800 font-medium">What happens next?</p>
                        <ul class="text-sm text-purple-700 mt-1 space-y-1">
                            <li>• Your profile and resume will be shared with {{ $job->company_name }}</li>
                            <li>• You'll receive email updates on your application status</li>
                            <li>• The employer may contact you for next steps</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-sm text-gray-500">
                <svg class="w-4 h-4 inline-block mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                Your information is secure
            </p>
            <div class="flex gap-3">
                <button onclick="closeApplicationModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button onclick="submitApplication()" id="submitBtn" class="px-6 py-2.5 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Submit Application
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const coverLetterInput = document.getElementById('coverLetterInput');
const charCount = document.getElementById('charCount');

// Character count
coverLetterInput.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = `${count}/2000`;
    if (count > 2000) {
        charCount.classList.add('text-red-500');
    } else {
        charCount.classList.remove('text-red-500');
    }
});

function openApplicationModal() {
    document.getElementById('applicationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeApplicationModal() {
    document.getElementById('applicationModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeApplicationModal();
    }
});

// Close modal on backdrop click
document.getElementById('applicationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeApplicationModal();
    }
});

async function generateCoverLetter() {
    const aiWriterBtn = document.getElementById('aiWriterBtn');
    const aiWriterText = document.getElementById('aiWriterText');
    
    aiWriterBtn.disabled = true;
    aiWriterText.textContent = 'Generating...';
    
    try {
        const response = await fetch('/api/ai/generate-cover-letter', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                job_id: {{ $job->id }},
                job_title: '{{ addslashes($job->title) }}',
                company_name: '{{ addslashes($job->company_name) }}',
                job_description: `{{ addslashes(Str::limit($job->description, 500)) }}`
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.cover_letter) {
            coverLetterInput.value = data.cover_letter;
            // Trigger input event to update character count
            coverLetterInput.dispatchEvent(new Event('input'));
        } else {
            // Fallback template if AI fails
            const fallbackLetter = `Dear Hiring Manager,

I am writing to express my strong interest in the {{ $job->title }} position at {{ $job->company_name }}. With my background and skills, I am confident that I would be a valuable addition to your team.

I am particularly drawn to this opportunity because of {{ $job->company_name }}'s reputation and the exciting nature of this role. I believe my experience aligns well with your requirements and I am eager to contribute to your team's success.

I would welcome the opportunity to discuss how my skills and experience can benefit {{ $job->company_name }}. Thank you for considering my application.

Best regards,
{{ auth()->user()->name }}`;
            coverLetterInput.value = fallbackLetter;
            coverLetterInput.dispatchEvent(new Event('input'));
        }
    } catch (error) {
        console.error('Error generating cover letter:', error);
        // Use fallback template
        const fallbackLetter = `Dear Hiring Manager,

I am excited to apply for the {{ $job->title }} position at {{ $job->company_name }}. I believe my skills and experience make me a strong candidate for this role.

I am particularly interested in this opportunity because it aligns with my career goals and I am impressed by {{ $job->company_name }}'s work in the industry.

I would love the opportunity to discuss how I can contribute to your team's success.

Best regards,
{{ auth()->user()->name }}`;
        coverLetterInput.value = fallbackLetter;
        coverLetterInput.dispatchEvent(new Event('input'));
    } finally {
        aiWriterBtn.disabled = false;
        aiWriterText.textContent = 'AI Writer';
    }
}

function submitApplication() {
    const form = document.getElementById('applicationForm');
    const submitBtn = document.getElementById('submitBtn');
    const coverLetter = coverLetterInput.value;

    if (coverLetter.length > 2000) {
        alert('Cover letter exceeds 2000 characters. Please shorten it.');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = `<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg> Submitting...`;

    fetch(`/api/jobs/{{ $job->id }}/apply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            cover_letter: coverLetter
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const modal = document.getElementById('applicationModal');
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-8 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Application Submitted!</h3>
                    <p class="text-gray-600 mb-6">Your application for {{ $job->title }} at {{ $job->company_name }} has been submitted successfully.</p>
                    <button onclick="window.location.reload()" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-lg">
                        Done
                    </button>
                </div>
            `;
        } else {
            alert(data.error || 'Failed to submit application. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg> Submit Application`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg> Submit Application`;
    });
}

function toggleSave(jobId) {
    fetch(`/api/jobs/${jobId}/toggle-save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        const btn = document.getElementById('save-btn');
        if (data.saved) {
            btn.classList.add('border-pink-500', 'text-pink-600');
            btn.classList.remove('border-gray-300', 'text-gray-700');
            btn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/></svg>Saved`;
        } else {
            btn.classList.remove('border-pink-500', 'text-pink-600');
            btn.classList.add('border-gray-300', 'text-gray-700');
            btn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>Save Job`;
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endauth

@endsection
