@extends('layouts.app')

@section('title', 'Transaction Details - StudAI Career')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('payments.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Payment History
            </a>
        </div>

        <!-- Transaction Summary Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-pink-500 to-purple-600 px-6 py-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium opacity-90">Transaction ID</p>
                        <p class="text-xl font-mono font-semibold mt-1">{{ $transaction->transaction_id }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium opacity-90">Amount</p>
                        <p class="text-3xl font-bold mt-1">₹{{ number_format($transaction->amount, 2) }}</p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Date</p>
                        <p class="font-semibold">{{ $transaction->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                    <div>
                        @if($transaction->status === 'completed')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-500 text-white">
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Payment Successful
                            </span>
                        @elseif($transaction->status === 'pending')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-500 text-white">
                                <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing
                            </span>
                        @elseif($transaction->status === 'failed')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-500 text-white">
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Payment Failed
                            </span>
                        @elseif($transaction->status === 'refunded')
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-purple-500 text-white">
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                                Refunded
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="px-6 py-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Details</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Subscription Plan</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">
                            {{ $transaction->subscriptionPlan->name ?? 'N/A' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Billing Period</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            {{ ucfirst($transaction->subscriptionPlan->billing_period ?? 'N/A') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Payment Gateway</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            @if($transaction->payment_gateway === 'razorpay')
                                <span class="inline-flex items-center">
                                    <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
                                    Razorpay
                                </span>
                            @elseif($transaction->payment_gateway === 'payu')
                                <span class="inline-flex items-center">
                                    <span class="w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                                    PayU
                                </span>
                            @else
                                {{ ucfirst($transaction->payment_gateway ?? 'N/A') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            {{ ucfirst($transaction->payment_method ?? 'N/A') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Currency</dt>
                        <dd class="mt-1 text-base text-gray-900">
                            {{ strtoupper($transaction->currency) }}
                        </dd>
                    </div>
                    @if($transaction->gateway_transaction_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Gateway Transaction ID</dt>
                        <dd class="mt-1 text-base font-mono text-gray-900 text-sm">
                            {{ $transaction->gateway_transaction_id }}
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Plan Details Card -->
        @if($transaction->subscriptionPlan)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
            <div class="px-6 py-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Features</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Monthly Applications</p>
                            <p class="text-sm text-gray-600">{{ $transaction->subscriptionPlan->applications_per_month }} applications</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">AI Credits</p>
                            <p class="text-sm text-gray-600">{{ $transaction->subscriptionPlan->ai_credits_per_month }} credits/month</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Job Alerts</p>
                            <p class="text-sm text-gray-600">{{ $transaction->subscriptionPlan->job_alerts_per_month }} alerts/month</p>
                        </div>
                    </div>
                    @if($transaction->subscriptionPlan->priority_support)
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Priority Support</p>
                            <p class="text-sm text-gray-600">24/7 premium support</p>
                        </div>
                    </div>
                    @endif
                </div>

                @if($transaction->subscriptionPlan->features)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Additional Features</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach(json_decode($transaction->subscriptionPlan->features, true) ?? [] as $feature => $enabled)
                            @if($enabled)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-700">{{ str_replace('_', ' ', ucfirst($feature)) }}</span>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-4">
                    <!-- Download Receipt -->
                    <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Receipt
                    </button>

                    <!-- Request Refund (if eligible) -->
                    @if($transaction->status === 'completed' && $transaction->created_at->diffInDays(now()) <= 7)
                    <button onclick="requestRefund({{ $transaction->id }})" class="inline-flex items-center px-6 py-3 bg-white border-2 border-purple-500 text-purple-600 font-semibold rounded-lg shadow-md hover:bg-purple-50 transition-all transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Request Refund
                    </button>
                    @endif

                    <!-- Contact Support -->
                    <a href="mailto:support@studai.com?subject=Transaction {{ $transaction->transaction_id }}" class="inline-flex items-center px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 font-semibold rounded-lg shadow-md hover:bg-gray-50 transition-all transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function requestRefund(transactionId) {
    if (!confirm('Are you sure you want to request a refund? This action will be reviewed by our team.')) {
        return;
    }

    fetch(`/api/payment/refund/${transactionId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Refund request submitted successfully. We will process it within 5-7 business days.');
            location.reload();
        } else {
            alert(data.message || 'Failed to submit refund request. Please contact support.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again or contact support.');
    });
}
</script>

@endsection
