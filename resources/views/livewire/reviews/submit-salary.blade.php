<div class="max-w-3xl mx-auto">
    @if ($submitted)
        {{-- Success State --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Salary Shared!</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Thank you for contributing. Your salary data helps others negotiate better offers.
            </p>
            <a href="{{ route('companies.salaries', $company) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition">
                View Salary Data
            </a>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white">
                <h1 class="text-xl font-bold">Share Your Salary at {{ $company->name }}</h1>
                <p class="text-emerald-100 text-sm mt-1">Help others by sharing compensation data anonymously</p>
            </div>

            {{-- Progress --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    @foreach (['Job Info', 'Compensation', 'Experience'] as $index => $label)
                        @php $stepNum = $index + 1; @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm
                                {{ $currentStep === $stepNum ? 'bg-emerald-600 text-white' : ($stepNum < $currentStep ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                                @if ($stepNum < $currentStep) ✓ @else {{ $stepNum }} @endif
                            </div>
                            <span class="hidden sm:block text-sm font-medium {{ $currentStep === $stepNum ? 'text-emerald-600' : 'text-gray-500' }}">{{ $label }}</span>
                        </div>
                        @if ($index < 2)
                            <div class="flex-1 h-0.5 mx-2 {{ $stepNum < $currentStep ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Form --}}
            <div class="p-6">
                @if ($currentStep === 1)
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Job Title *</label>
                            <input type="text" wire:model="jobTitle" placeholder="e.g., Senior Software Engineer" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            @error('jobTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Job Level *</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (\App\Models\SalaryReport::JOB_LEVELS as $value => $label)
                                    <button type="button" wire:click="$set('jobLevel', '{{ $value }}')" class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition
                                        {{ $jobLevel === $value ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Employment Type *</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship'] as $value => $label)
                                    <button type="button" wire:click="$set('employmentType', '{{ $value }}')" class="px-4 py-3 rounded-xl border-2 text-sm font-medium transition
                                        {{ $employmentType === $value ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location *</label>
                                <input type="text" wire:model="location" placeholder="e.g., New York, NY" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-3 px-4 py-3 w-full border border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <input type="checkbox" wire:model="isRemote" class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Remote Position</span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($currentStep === 2)
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                                <select wire:model="currency" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                    <option value="INR">INR (₹)</option>
                                    <option value="CAD">CAD (C$)</option>
                                    <option value="AUD">AUD (A$)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pay Frequency</label>
                                <select wire:model="payFrequency" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="yearly">Per Year</option>
                                    <option value="monthly">Per Month</option>
                                    <option value="hourly">Per Hour</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Base Salary *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">{{ match($currency) { 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', default => $currency } }}</span>
                                <input type="number" wire:model.live="baseSalary" placeholder="0" class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-xl font-semibold">
                            </div>
                            @error('baseSalary') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Annual Bonus</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ match($currency) { 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', default => $currency } }}</span>
                                    <input type="number" wire:model.live="bonus" placeholder="0" class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock/Equity (Annual)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">{{ match($currency) { 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', default => $currency } }}</span>
                                    <input type="number" wire:model.live="stockValue" placeholder="0" class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>

                        {{-- Total Compensation Preview --}}
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-emerald-800 dark:text-emerald-300">Total Compensation</span>
                                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ match($currency) { 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', default => $currency } }}{{ number_format($this->getTotalCompensation()) }}
                                    <span class="text-sm font-normal text-emerald-700 dark:text-emerald-300">/{{ $payFrequency === 'yearly' ? 'yr' : ($payFrequency === 'monthly' ? 'mo' : 'hr') }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($currentStep === 3)
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Years of Experience *</label>
                                <input type="number" wire:model="yearsExperience" min="0" max="50" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Years at This Company</label>
                                <input type="number" wire:model="yearsAtCompany" min="0" max="50" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Education Level</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['high_school' => 'High School', 'associate' => 'Associate', 'bachelors' => "Bachelor's", 'masters' => "Master's", 'phd' => 'PhD', 'other' => 'Other'] as $value => $label)
                                    <button type="button" wire:click="$set('educationLevel', '{{ $value }}')" class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition
                                        {{ $educationLevel === $value ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Benefits Included</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (self::BENEFIT_OPTIONS as $value => $label)
                                    <button type="button" wire:click="toggleBenefit('{{ $value }}')" class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition text-left
                                        {{ in_array($value, $benefits) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                        {{ in_array($value, $benefits) ? '✓ ' : '' }}{{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Compensation Satisfaction</label>
                            <div class="flex items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('satisfactionRating', {{ $i }})" class="p-1 transition">
                                        <svg class="w-8 h-8 {{ $i <= ($satisfactionRating ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                @endfor
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                            <p class="text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                Your salary data is completely anonymous. We never share personally identifiable information.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                @if ($currentStep > 1)
                    <button wire:click="previousStep" class="inline-flex items-center gap-2 px-6 py-3 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition">
                        ← Back
                    </button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < $totalSteps)
                    <button wire:click="nextStep" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-sm transition">
                        Next →
                    </button>
                @else
                    <button wire:click="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-sm transition">
                        <span wire:loading.remove wire:target="submit">Submit Salary</span>
                        <span wire:loading wire:target="submit">Submitting...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
