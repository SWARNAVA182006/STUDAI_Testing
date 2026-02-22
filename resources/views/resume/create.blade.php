@extends('layouts.app')

@section('title', 'Create Resume')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="resumeBuilder()">
    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center" :class="step >= 1 ? 'text-purple-600' : 'text-gray-400'">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" 
                     :class="step >= 1 ? 'bg-purple-600 text-white' : 'bg-gray-300'">
                    1
                </div>
                <span class="ml-3 font-medium">Choose Template</span>
            </div>
            <div class="flex-1 h-1 mx-4" :class="step >= 2 ? 'bg-purple-600' : 'bg-gray-300'"></div>
            
            <div class="flex items-center" :class="step >= 2 ? 'text-purple-600' : 'text-gray-400'">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" 
                     :class="step >= 2 ? 'bg-purple-600 text-white' : 'bg-gray-300'">
                    2
                </div>
                <span class="ml-3 font-medium">Basic Info</span>
            </div>
            <div class="flex-1 h-1 mx-4" :class="step >= 3 ? 'bg-purple-600' : 'bg-gray-300'"></div>
            
            <div class="flex items-center" :class="step >= 3 ? 'text-purple-600' : 'text-gray-400'">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" 
                     :class="step >= 3 ? 'bg-purple-600 text-white' : 'bg-gray-300'">
                    3
                </div>
                <span class="ml-3 font-medium">AI Enhancement</span>
            </div>
        </div>
    </div>

    <form action="{{ route('resume.store') }}" method="POST" @submit="handleSubmit">
        @csrf

        <!-- Step 1: Template Selection -->
        <div x-show="step === 1" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Choose Your Template</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($templates as $template)
                    <label class="cursor-pointer">
                        <input type="radio" name="template_id" value="{{ $template->id }}" 
                               x-model="formData.template_id" class="sr-only">
                        <div class="border-2 rounded-lg p-4 transition-all"
                             :class="formData.template_id == {{ $template->id }} ? 'border-purple-600 bg-purple-50' : 'border-gray-200 hover:border-purple-300'">
                            <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 rounded-md mb-3 flex items-center justify-center">
                                <i data-lucide="file-text" class="w-16 h-16 text-gray-400"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">{{ $template->name }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $template->description }}</p>
                            
                            <div class="flex gap-2 mt-3">
                                @if($template->is_ats_friendly)
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">ATS Friendly</span>
                                @endif
                                @if($template->is_premium)
                                    <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">Premium</span>
                                @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="nextStep" class="btn btn-primary">
                    Continue <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Basic Information -->
        <div x-show="step === 2" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Resume Title *</label>
                    <input type="text" name="title" x-model="formData.title" 
                           class="form-input w-full" placeholder="e.g., Software Engineer Resume" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="full_name" x-model="formData.full_name" 
                           class="form-input w-full" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" x-model="formData.email" 
                           class="form-input w-full" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="tel" name="phone" x-model="formData.phone" 
                           class="form-input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                    <input type="text" name="location" x-model="formData.location" 
                           class="form-input w-full" placeholder="City, Country">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">LinkedIn URL</label>
                    <input type="url" name="linkedin_url" x-model="formData.linkedin_url" 
                           class="form-input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">GitHub URL</label>
                    <input type="url" name="github_url" x-model="formData.github_url" 
                           class="form-input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Portfolio URL</label>
                    <input type="url" name="portfolio_url" x-model="formData.portfolio_url" 
                           class="form-input w-full">
                </div>
            </div>

            @if($targetJob)
                <input type="hidden" name="target_job_id" value="{{ $targetJob->id }}">
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                        This resume will be optimized for: <strong>{{ $targetJob->title }}</strong> at {{ $targetJob->company->name }}
                    </p>
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <button type="button" @click="prevStep" class="btn btn-outline">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back
                </button>
                <button type="button" @click="nextStep" class="btn btn-primary">
                    Continue <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: AI Enhancement -->
        <div x-show="step === 3" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">AI Enhancement</h2>
            
            <div class="space-y-6">
                <!-- Option 1: Generate from Profile -->
                <div class="border-2 border-purple-200 rounded-lg p-6 bg-gradient-to-br from-purple-50 to-pink-50">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center">
                                <i data-lucide="sparkles" class="w-6 h-6 text-white"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">Generate AI Professional Summary</h3>
                            <p class="text-gray-600 mt-1">Let AI create a compelling professional summary based on your profile</p>
                            <label class="flex items-center mt-3">
                                <input type="checkbox" x-model="generateAISummary" class="form-checkbox text-purple-600">
                                <span class="ml-2 text-sm text-gray-700">Generate professional summary with AI</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Manual Summary (Alternative) -->
                <div x-show="!generateAISummary" class="border-2 border-gray-200 rounded-lg p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Professional Summary</label>
                    <textarea name="professional_summary" x-model="formData.professional_summary" 
                              rows="4" class="form-input w-full" 
                              placeholder="Write a brief summary of your professional background..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Or leave blank to generate with AI later</p>
                </div>

                <!-- AI Features Included -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-3">Included AI Features:</h4>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li class="flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-green-600"></i>
                            AI-powered professional summary generation
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-green-600"></i>
                            Automatic skills extraction from experience
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-green-600"></i>
                            Achievement quantification suggestions
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-green-600"></i>
                            ATS optimization and keyword matching
                        </li>
                        <li class="flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-green-600"></i>
                            Job-specific customization (when targeting a role)
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" @click="prevStep" class="btn btn-outline">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back
                </button>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                    <span x-show="!loading">Create Resume</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function resumeBuilder() {
    return {
        step: 1,
        loading: false,
        generateAISummary: true,
        formData: {
            template_id: {{ $templates->first()->id ?? 'null' }},
            title: '',
            full_name: '{{ auth()->user()->name }}',
            email: '{{ auth()->user()->email }}',
            phone: '{{ auth()->user()->profile->phone ?? "" }}',
            location: '{{ auth()->user()->profile->location ?? "" }}',
            linkedin_url: '',
            github_url: '',
            portfolio_url: '',
            professional_summary: '',
        },
        
        nextStep() {
            if (this.validateStep()) {
                this.step++;
            }
        },
        
        prevStep() {
            this.step--;
        },
        
        validateStep() {
            if (this.step === 1 && !this.formData.template_id) {
                alert('Please select a template');
                return false;
            }
            if (this.step === 2) {
                if (!this.formData.title || !this.formData.full_name || !this.formData.email) {
                    alert('Please fill in all required fields');
                    return false;
                }
            }
            return true;
        },
        
        handleSubmit(e) {
            this.loading = true;
            // Form will submit normally
        }
    }
}

lucide.createIcons();
</script>
@endpush
@endsection
