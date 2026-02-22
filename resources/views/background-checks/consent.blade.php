@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full">
        <!-- Company Logo/Header -->
        <div class="text-center mb-8">
            <div class="mx-auto h-16 w-16 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                <svg class="h-8 w-8 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">Background Check Authorization</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ $backgroundCheck->company->name ?? 'Employer' }} has requested a background check as part of your application process.
            </p>
        </div>

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
            <!-- Check Details -->
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Background Check Details</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Provider</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->provider_name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Requested By</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->company->name ?? 'Employer' }}</span>
                    </div>
                    @if($backgroundCheck->package)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Package</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->package->name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Consent Expires</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->consent_expires_at->format('M d, Y') }}</span>
                    </div>
                </div>

                <!-- Checks Included -->
                <div class="mt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Checks Included:</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($backgroundCheck->checks_requested ?? [] as $check)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            {{ ucfirst(str_replace('_', ' ', $check)) }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Rights Notice -->
            <div class="px-6 py-5 bg-blue-50 dark:bg-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">Your Rights Under the Fair Credit Reporting Act (FCRA)</h4>
                <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                    <li>• You have the right to receive a copy of the background check report</li>
                    <li>• You have the right to dispute any inaccurate information</li>
                    <li>• If adverse action is taken, you will be notified and given an opportunity to respond</li>
                    <li>• You can request additional information about the nature and scope of the investigation</li>
                </ul>
            </div>

            <!-- Consent Form -->
            <form action="{{ route('background-checks.consent.submit', $backgroundCheck->consent_token) }}" method="POST" class="px-6 py-5">
                @csrf

                @if($errors->any())
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
                    <ul class="text-sm text-red-600 dark:text-red-400">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="space-y-4">
                    <!-- Authorization Checkbox -->
                    <label class="flex items-start">
                        <input type="checkbox" name="agree_to_check" value="1" required
                               class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            I authorize {{ $backgroundCheck->company->name ?? 'the employer' }} and {{ $backgroundCheck->provider_name }} to conduct a background check on me. I understand this may include verification of my employment history, education, criminal records, and other relevant information.
                        </span>
                    </label>

                    <!-- Terms Checkbox -->
                    <label class="flex items-start">
                        <input type="checkbox" name="agree_to_terms" value="1" required
                               class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                            I have read and understand my rights under the Fair Credit Reporting Act. I acknowledge that I may request a copy of the report and dispute any inaccuracies.
                        </span>
                    </label>

                    <!-- Electronic Signature -->
                    <div>
                        <label for="signature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Electronic Signature (Type your full name)
                        </label>
                        <input type="text" name="signature" id="signature" required
                               placeholder="Type your full legal name"
                               class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            By typing your name above, you are providing your electronic signature agreeing to this authorization.
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        I Authorize This Background Check
                    </button>
                </div>
            </form>

            <!-- Footer Note -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50">
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                    If you have questions about this background check, please contact {{ $backgroundCheck->company->name ?? 'the employer' }} directly.
                    Your consent is valid for 7 days from the date of this request.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
