@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('background-checks.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Background Checks
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Request Background Check</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Initiate a new background verification for a candidate
            </p>
        </div>

        <!-- Form -->
        <form id="background-check-form" class="space-y-6">
            @csrf
            
            <!-- Candidate Selection -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Candidate Information</h3>
                
                @if($candidate)
                    <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                    <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                <span class="text-lg font-medium text-indigo-600 dark:text-indigo-300">
                                    {{ strtoupper(substr($candidate->name, 0, 2)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $candidate->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $candidate->email }}</div>
                        </div>
                    </div>
                @else
                    <div>
                        <label for="candidate_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Search Candidate
                        </label>
                        <input type="text" id="candidate_search" 
                               placeholder="Type candidate name or email..."
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="hidden" name="candidate_id" id="candidate_id">
                        <div id="candidate_results" class="mt-2 hidden"></div>
                    </div>
                @endif

                @if($applicationId)
                    <input type="hidden" name="application_id" value="{{ $applicationId }}">
                @endif
            </div>

            <!-- Provider Selection -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Background Check Provider</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative flex flex-col bg-gray-50 dark:bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 border-2 border-transparent has-[:checked]:border-indigo-500">
                        <input type="radio" name="provider" value="checkr" class="sr-only" checked>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">Checkr</span>
                            <svg class="w-5 h-5 text-indigo-600 hidden provider-check" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Fast, modern background checks with excellent API</p>
                    </label>

                    <label class="relative flex flex-col bg-gray-50 dark:bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 border-2 border-transparent has-[:checked]:border-indigo-500">
                        <input type="radio" name="provider" value="sterling" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">Sterling</span>
                            <svg class="w-5 h-5 text-indigo-600 hidden provider-check" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Enterprise-grade screening with global coverage</p>
                    </label>

                    <label class="relative flex flex-col bg-gray-50 dark:bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 border-2 border-transparent has-[:checked]:border-indigo-500">
                        <input type="radio" name="provider" value="goodhire" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">GoodHire</span>
                            <svg class="w-5 h-5 text-indigo-600 hidden provider-check" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Affordable screening for small to mid-size teams</p>
                    </label>
                </div>
            </div>

            <!-- Package Selection (if available) -->
            @if($packages->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Select Package (Optional)</h3>
                <div class="space-y-3">
                    <label class="flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 border-2 border-transparent has-[:checked]:border-indigo-500">
                        <input type="radio" name="package_id" value="" class="mt-1" checked>
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Custom Selection</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Choose individual checks below</p>
                        </div>
                    </label>
                    @foreach($packages as $package)
                    <label class="flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 border-2 border-transparent has-[:checked]:border-indigo-500" data-provider="{{ $package->provider }}">
                        <input type="radio" name="package_id" value="{{ $package->id }}" class="mt-1" data-checks='@json($package->checks_included)'>
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $package->name }}</span>
                                <span class="text-sm font-semibold text-indigo-600">{{ $package->formatted_price }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $package->description }}</p>
                            <p class="text-xs text-gray-400 mt-1">Includes: {{ $package->checks_list }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Check Types -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Background Check Types</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Select the verifications to include in this background check.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="criminal" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Criminal Records</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">County, state, and federal criminal records</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="employment" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Employment Verification</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Verify past employment history</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="education" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Education Verification</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Verify degrees and certifications</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="ssn_trace" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">SSN Trace</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Social Security Number verification</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="credit" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Credit Check</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Credit history and financial responsibility</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="mvr" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Motor Vehicle Records</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Driving history and license verification</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="drug" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Drug Screening</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pre-employment drug test coordination</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="sex_offender" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Sex Offender Registry</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">National sex offender database check</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="global_watchlist" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Global Watchlist</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">OFAC and international sanctions lists</p>
                        </div>
                    </label>

                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        <input type="checkbox" name="checks[]" value="identity" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Identity Verification</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Confirm candidate identity</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Consent Options -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Consent Request</h3>
                
                <label class="flex items-start">
                    <input type="checkbox" name="send_consent_now" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" checked>
                    <div class="ml-3">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Send consent request immediately</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            The candidate will receive an email to review and consent to the background check.
                            You can also send this later from the background check details page.
                        </p>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('background-checks.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition" id="submit-btn">
                    <svg class="w-4 h-4 mr-2 hidden animate-spin" id="loading-spinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Request Background Check
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('background-check-form');
    const submitBtn = document.getElementById('submit-btn');
    const spinner = document.getElementById('loading-spinner');

    // Provider selection visual feedback
    document.querySelectorAll('input[name="provider"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.provider-check').forEach(check => check.classList.add('hidden'));
            this.closest('label').querySelector('.provider-check').classList.remove('hidden');
        });
        // Initialize
        if (radio.checked) {
            radio.closest('label').querySelector('.provider-check').classList.remove('hidden');
        }
    });

    // Package selection updates checks
    document.querySelectorAll('input[name="package_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value && this.dataset.checks) {
                const checks = JSON.parse(this.dataset.checks);
                document.querySelectorAll('input[name="checks[]"]').forEach(cb => {
                    cb.checked = checks.includes(cb.value);
                    cb.disabled = true;
                });
            } else {
                document.querySelectorAll('input[name="checks[]"]').forEach(cb => {
                    cb.disabled = false;
                });
            }
        });
    });

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const checks = [];
        formData.getAll('checks[]').forEach(c => checks.push(c));

        if (checks.length === 0) {
            alert('Please select at least one background check type');
            return;
        }

        submitBtn.disabled = true;
        spinner.classList.remove('hidden');

        try {
            const response = await fetch('{{ route("background-checks.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    candidate_id: formData.get('candidate_id'),
                    application_id: formData.get('application_id'),
                    package_id: formData.get('package_id') || null,
                    provider: formData.get('provider'),
                    checks: checks,
                    send_consent_now: formData.has('send_consent_now'),
                }),
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '{{ route("background-checks.index") }}';
            } else {
                alert(result.message || 'Failed to create background check');
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            spinner.classList.add('hidden');
        }
    });
});
</script>
@endpush
@endsection
