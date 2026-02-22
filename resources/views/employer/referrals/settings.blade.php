@extends('layouts.employer')

@section('title', 'Referral Program Settings')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1">
                    <li><a href="{{ route('employer.referrals.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Referrals</a></li>
                    <li><span class="mx-2 text-gray-400">/</span></li>
                    <li class="text-gray-900 dark:text-white font-medium">Settings</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Referral Program Settings</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Configure your employee referral program parameters</p>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        <form action="{{ route('employer.referrals.update-settings') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Program Status -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Program Status</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Referral Program</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Allow employees to submit referrals</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" {{ $settings->enabled ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto-Approve Referrals</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Automatically create applications for referred candidates</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_approve" value="1" {{ $settings->auto_approve ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>

            <!-- Bonus Structure -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Bonus Structure</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Configure referral bonuses based on position level</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Bonus Amount</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₹</span>
                            </div>
                            <input type="number" name="default_bonus_amount" value="{{ $settings->default_bonus_amount }}" min="0" step="100" required
                                   class="pl-8 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Bonus by Position Level</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @php $levels = $settings->bonus_by_level ?? []; @endphp
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Entry Level</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₹</span>
                                </div>
                                <input type="number" name="bonus_entry" value="{{ $levels['entry'] ?? 15000 }}" min="0" step="100" required
                                       class="pl-8 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Mid Level</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₹</span>
                                </div>
                                <input type="number" name="bonus_mid" value="{{ $levels['mid'] ?? 25000 }}" min="0" step="100" required
                                       class="pl-8 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Senior Level</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₹</span>
                                </div>
                                <input type="number" name="bonus_senior" value="{{ $levels['senior'] ?? 50000 }}" min="0" step="100" required
                                       class="pl-8 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Lead/Manager</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₹</span>
                                </div>
                                <input type="number" name="bonus_lead" value="{{ $levels['lead'] ?? 75000 }}" min="0" step="100" required
                                       class="pl-8 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Executive</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₹</span>
                                </div>
                                <input type="number" name="bonus_executive" value="{{ $levels['executive'] ?? 100000 }}" min="0" step="100" required
                                       class="pl-8 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rules & Limits -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Rules & Limits</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Probation Period (days)</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Number of days before bonus is paid after hire</p>
                        <input type="number" name="probation_days" value="{{ $settings->probation_days }}" min="0" max="365" required
                               class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Referrals per Employee (monthly)</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Leave empty for unlimited</p>
                        <input type="number" name="max_referrals_per_employee" value="{{ $settings->max_referrals_per_employee }}" min="1"
                               class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Unlimited">
                    </div>
                </div>
            </div>

            <!-- Terms & Conditions -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Terms & Conditions</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Program terms that employees must agree to when making referrals</p>
                <textarea name="terms_and_conditions" rows="6"
                          class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="Enter terms and conditions...">{{ $settings->terms_and_conditions }}</textarea>
            </div>

            <!-- Share Link -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Share Your Referral Program</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Share this link with employees so they can submit referrals</p>
                
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ route('employer.referrals.index') }}" id="share-link"
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm bg-gray-50 dark:bg-gray-900">
                    <button type="button" onclick="copyShareLink()" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Copy Link
                    </button>
                </div>
                
                <div class="mt-4 flex gap-3">
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('employer.referrals.index')) }}" target="_blank" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        Share on LinkedIn
                    </a>
                    <a href="mailto:?subject=Referral%20Opportunity&body=Check%20out%20our%20referral%20program:%20{{ urlencode(route('employer.referrals.index')) }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Share via Email
                    </a>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('employer.referrals.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function copyShareLink() {
    const input = document.getElementById('share-link');
    input.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}
</script>
@endpush
@endsection
