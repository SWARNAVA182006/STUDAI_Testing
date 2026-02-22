<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm Plan Selection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Plan Details -->
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h3>
                        <p class="mt-2 text-gray-600">{{ $plan->description }}</p>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-4">Select Billing Cycle</h4>
                        
                        <form id="billing-form" method="POST" action="{{ route('subscriptions.subscribe') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input type="radio" name="billing_cycle" value="monthly" class="h-4 w-4 text-indigo-600" checked>
                                    <div class="ml-3 flex-1">
                                        <div class="flex justify-between items-baseline">
                                            <span class="font-semibold text-gray-900">Monthly</span>
                                            <span class="text-2xl font-bold text-gray-900">₹{{ number_format($plan->price_monthly) }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Billed monthly, cancel anytime</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition relative">
                                    <input type="radio" name="billing_cycle" value="yearly" class="h-4 w-4 text-indigo-600">
                                    <div class="ml-3 flex-1">
                                        <div class="flex justify-between items-baseline">
                                            <div>
                                                <span class="font-semibold text-gray-900">Yearly</span>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Save 20%
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-gray-900">₹{{ number_format($plan->price_yearly / 12) }}</span>
                                                <span class="text-gray-500">/month</span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-500">₹{{ number_format($plan->price_yearly) }} billed annually</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Payment Gateway Selection -->
                            <div class="mt-6">
                                <h4 class="font-semibold text-gray-900 mb-4">Select Payment Method</h4>
                                <div class="space-y-3">
                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input type="radio" name="gateway" value="razorpay" class="h-4 w-4 text-indigo-600" checked>
                                        <div class="ml-3">
                                            <span class="font-semibold text-gray-900">Razorpay</span>
                                            <p class="text-sm text-gray-500">Credit/Debit Card, UPI, Net Banking, Wallets</p>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                        <input type="radio" name="gateway" value="payu" class="h-4 w-4 text-indigo-600">
                                        <div class="ml-3">
                                            <span class="font-semibold text-gray-900">PayU</span>
                                            <p class="text-sm text-gray-500">All major payment methods supported</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Features Summary -->
                            <div class="mt-6 p-4 bg-indigo-50 rounded-lg">
                                <h4 class="font-semibold text-indigo-900 mb-3">What's Included:</h4>
                                <ul class="space-y-2 text-sm text-indigo-800">
                                    <li class="flex items-center">
                                        <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $plan->applications_limit == -1 ? 'Unlimited' : $plan->applications_limit }} job applications per month
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $plan->ai_credits == -1 ? 'Unlimited' : $plan->ai_credits }} AI credits per month
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $plan->assessment_limit == -1 ? 'Unlimited' : $plan->assessment_limit }} skill assessments per month
                                    </li>
                                    @if($plan->has_priority_support)
                                    <li class="flex items-center">
                                        <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Priority customer support
                                    </li>
                                    @endif
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-6 flex gap-4">
                                <a href="{{ route('subscriptions.pricing') }}" 
                                   class="flex-1 py-3 px-6 text-center border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="flex-1 py-3 px-6 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
                                    Proceed to Payment
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Terms -->
                    <div class="mt-6 text-xs text-gray-500 text-center">
                        By proceeding, you agree to our <a href="#" class="text-indigo-600 hover:underline">Terms of Service</a> and 
                        <a href="#" class="text-indigo-600 hover:underline">Refund Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
