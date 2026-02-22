<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Complete Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Plan Details -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 mb-4">{{ $plan->description }}</p>
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-3xl font-bold text-indigo-600">₹{{ number_format($amount / 100, 2) }}</span>
                                <span class="text-gray-500">/ {{ $billingCycle }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- PayU Payment Form -->
                    <form id="payuForm" method="POST" action="{{ $orderData['action_url'] }}">
                        @foreach($orderData['params'] as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <button type="submit" id="payu-button" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                            <i class="fas fa-lock mr-2"></i> Pay Securely with PayU
                        </button>
                    </form>

                    <!-- Security Note -->
                    <div class="mt-6 text-center text-sm text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Your payment is secured with 256-bit encryption
                    </div>

                    <!-- Payment Information -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="font-semibold text-blue-800 mb-2">Payment Information</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• You will be redirected to PayU's secure payment gateway</li>
                            <li>• Support for Credit/Debit Cards, Net Banking, UPI, and Wallets</li>
                            <li>• Your subscription will be activated immediately after payment</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-submit form if needed, or add loading state
        document.getElementById('payu-button').addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Redirecting to PayU...';
        });
    </script>
    @endpush
</x-app-layout>
