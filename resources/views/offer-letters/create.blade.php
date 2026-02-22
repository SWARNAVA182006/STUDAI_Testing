@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('offer-letters.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Offers
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Offer Letter</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Create a new offer letter for a candidate
            </p>
        </div>

        <form id="offer-form" class="space-y-6">
            @csrf

            <!-- Candidate & Position -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Position Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="candidate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Candidate *</label>
                        <select name="candidate_id" id="candidate_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a candidate</option>
                            <!-- Populate via JavaScript or from controller -->
                        </select>
                    </div>

                    <div>
                        <label for="job_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Posting</label>
                        <select name="job_id" id="job_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a job (optional)</option>
                        </select>
                    </div>

                    <div>
                        <label for="job_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Title *</label>
                        <input type="text" name="job_title" id="job_title" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                        <input type="text" name="department" id="department" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="employment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employment Type *</label>
                        <select name="employment_type" id="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>

                    <div>
                        <label for="work_arrangement" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Arrangement *</label>
                        <select name="work_arrangement" id="work_arrangement" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="on-site">On-Site</option>
                            <option value="remote">Remote</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    <div>
                        <label for="work_location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Work Location</label>
                        <input type="text" name="work_location" id="work_location" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="reporting_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reports To</label>
                        <input type="text" name="reporting_to" id="reporting_to" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Compensation -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Compensation</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="base_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Base Salary *</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="base_salary" id="base_salary" required min="0" step="0.01" class="pl-7 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label for="salary_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pay Period *</label>
                        <select name="salary_period" id="salary_period" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="annually" selected>Annually</option>
                            <option value="monthly">Monthly</option>
                            <option value="bi-weekly">Bi-Weekly</option>
                            <option value="weekly">Weekly</option>
                            <option value="hourly">Hourly</option>
                        </select>
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currency *</label>
                        <select name="currency" id="currency" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="USD" selected>USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="CAD">CAD ($)</option>
                            <option value="AUD">AUD ($)</option>
                        </select>
                    </div>

                    <div>
                        <label for="signing_bonus" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Signing Bonus</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="signing_bonus" id="signing_bonus" min="0" step="0.01" class="pl-7 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label for="annual_bonus_target" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Annual Bonus Target (%)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" name="annual_bonus_target" id="annual_bonus_target" min="0" max="100" step="0.1" class="pr-7 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label for="bonus_structure" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bonus Structure Details</label>
                        <textarea name="bonus_structure" id="bonus_structure" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe bonus criteria, payment schedule, etc."></textarea>
                    </div>
                </div>
            </div>

            <!-- Equity -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Equity (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="equity_shares" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Number of Shares</label>
                        <input type="number" name="equity_shares" id="equity_shares" min="0" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="equity_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Equity Type</label>
                        <input type="text" name="equity_type" id="equity_type" placeholder="e.g., Stock Options, RSUs" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-3">
                        <label for="vesting_schedule" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vesting Schedule</label>
                        <input type="text" name="vesting_schedule" id="vesting_schedule" placeholder="e.g., 4-year vesting with 1-year cliff" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Dates -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Important Dates</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date *</label>
                        <input type="date" name="start_date" id="start_date" required min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="offer_expiry_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Offer Expiry Date *</label>
                        <input type="date" name="offer_expiry_date" id="offer_expiry_date" required min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="response_deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Response Deadline</label>
                        <input type="date" name="response_deadline" id="response_deadline" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Template & Benefits -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Template & Benefits</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Letter Template</label>
                        <select name="template_id" id="template_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a template (optional)</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="benefits_package_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Benefits Package</label>
                        <select name="benefits_package_id" id="benefits_package_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a package (optional)</option>
                            @foreach($benefitsPackages as $package)
                                <option value="{{ $package->id }}">{{ $package->name }} (${{ number_format($package->total_value, 0) }}/year)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="special_conditions" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Special Conditions</label>
                        <textarea name="special_conditions" id="special_conditions" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Any special terms, conditions, or contingencies..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('offer-letters.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Create Offer Letter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('offer-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('{{ route('offer-letters.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            alert(result.message || 'Failed to create offer letter');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});
</script>
@endsection
