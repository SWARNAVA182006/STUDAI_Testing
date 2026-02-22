<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Company Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('employer.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <!-- Company Basics -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Basics</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-gray-700">Company Name *</label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="industry" class="block text-sm font-medium text-gray-700">Industry</label>
                                    <input type="text" name="industry" id="industry" value="{{ old('industry', $company->industry) }}"
                                        placeholder="e.g., Technology, Healthcare, Finance"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('industry')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="company_size" class="block text-sm font-medium text-gray-700">Company Size</label>
                                    <select name="company_size" id="company_size"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select Size</option>
                                        <option value="1-10" {{ old('company_size', $company->company_size) === '1-10' ? 'selected' : '' }}>1-10 employees</option>
                                        <option value="11-50" {{ old('company_size', $company->company_size) === '11-50' ? 'selected' : '' }}>11-50 employees</option>
                                        <option value="51-200" {{ old('company_size', $company->company_size) === '51-200' ? 'selected' : '' }}>51-200 employees</option>
                                        <option value="201-500" {{ old('company_size', $company->company_size) === '201-500' ? 'selected' : '' }}>201-500 employees</option>
                                        <option value="501-1000" {{ old('company_size', $company->company_size) === '501-1000' ? 'selected' : '' }}>501-1000 employees</option>
                                        <option value="1000+" {{ old('company_size', $company->company_size) === '1000+' ? 'selected' : '' }}>1000+ employees</option>
                                    </select>
                                    @error('company_size')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="headquarters" class="block text-sm font-medium text-gray-700">Headquarters</label>
                                    <input type="text" name="headquarters" id="headquarters" value="{{ old('headquarters', $company->headquarters) }}"
                                        placeholder="e.g., Mumbai, India"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('headquarters')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="founded_year" class="block text-sm font-medium text-gray-700">Founded Year</label>
                                    <input type="number" name="founded_year" id="founded_year" value="{{ old('founded_year', $company->founded_year) }}"
                                        min="1800" max="{{ date('Y') }}" placeholder="e.g., 2020"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('founded_year')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Online Presence -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Online Presence</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700">Company Website</label>
                                    <input type="url" name="website" id="website" value="{{ old('website', $company->website) }}"
                                        placeholder="https://example.com"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('website')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn Profile</label>
                                    <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $company->linkedin_url) }}"
                                        placeholder="https://linkedin.com/company/yourcompany"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('linkedin_url')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Company Logo -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Logo</h3>
                            
                            <div class="flex items-start gap-6">
                                <div id="logo-preview" class="flex-shrink-0">
                                    @if($company->logo)
                                        <img src="{{ Storage::url($company->logo) }}" alt="Company logo" 
                                            class="w-32 h-32 rounded-lg object-cover border-2 border-gray-200">
                                    @else
                                        <div class="w-32 h-32 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                            <span class="text-white text-4xl font-bold">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Upload Logo</label>
                                    <input type="file" name="logo" id="logo" accept="image/jpeg,image/jpg,image/png"
                                        class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100">
                                    <p class="mt-1 text-sm text-gray-500">JPG, JPEG or PNG. Max size 2MB.</p>
                                    @error('logo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    @if($company->logo)
                                        <button type="button" id="remove-logo-btn"
                                            class="mt-3 inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Remove Current Logo
                                        </button>
                                        <input type="hidden" name="remove_logo" id="remove-logo-input" value="0">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- About the Company -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">About the Company</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Company Description</label>
                                    <textarea name="description" id="description" rows="6"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Tell candidates about your company, mission, and what makes it a great place to work...">{{ old('description', $company->description) }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">Max 2000 characters</p>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="culture" class="block text-sm font-medium text-gray-700">Company Culture</label>
                                    <textarea name="culture" id="culture" rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Describe your company culture, values, and work environment...">{{ old('culture', $company->culture) }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">Max 1000 characters</p>
                                    @error('culture')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Employee Benefits -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Benefits</h3>
                            
                            <div>
                                <label for="benefits-input" class="block text-sm font-medium text-gray-700">
                                    Add benefits (press Enter or comma to add)
                                </label>
                                <input type="text" id="benefits-input"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="e.g., Health Insurance, Flexible Hours, Remote Work">
                                
                                <div id="benefits-container" class="mt-2 flex flex-wrap gap-2">
                                    <!-- Existing benefits will be loaded here -->
                                </div>
                                
                                <div id="benefits-hidden-inputs">
                                    <!-- Hidden inputs for existing benefits -->
                                    @if(old('benefits'))
                                        @foreach(old('benefits') as $benefit)
                                            <input type="hidden" name="benefits[]" value="{{ $benefit }}">
                                        @endforeach
                                    @elseif($company->benefits)
                                        @foreach($company->benefits as $benefit)
                                            <input type="hidden" name="benefits[]" value="{{ $benefit }}">
                                        @endforeach
                                    @endif
                                </div>
                                
                                @error('benefits')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('employer.profile.show') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                Cancel
                            </a>

                            <button type="submit"
                                class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:from-blue-700 hover:to-purple-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Update Company Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Logo Preview
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logo-preview');

        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.innerHTML = `<img src="${e.target.result}" alt="Logo preview" class="w-32 h-32 rounded-lg object-cover border-2 border-gray-200">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Remove Logo
        const removeLogo Btn = document.getElementById('remove-logo-btn');
        const removeLogoInput = document.getElementById('remove-logo-input');
        
        if (removeLogoBtn) {
            removeLogoBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove your company logo?')) {
                    removeLogoInput.value = '1';
                    logoPreview.innerHTML = `
                        <div class="w-32 h-32 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <span class="text-white text-4xl font-bold">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                        </div>
                    `;
                    this.remove();
                }
            });
        }

        // Benefits Management
        const existingBenefits = @json(old('benefits', $company->benefits ?? []));
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
