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

                    <!-- Payment Button -->
                    <div id="payment-section">
                        <button id="rzp-button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200">
                            <i class="fas fa-lock mr-2"></i> Pay Securely with Razorpay
                        </button>
                    </div>

                    <!-- Security Note -->
                    <div class="mt-6 text-center text-sm text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Your payment is secured with 256-bit encryption
                    </div>

                    <!-- Loading State -->
                    <div id="loading" class="hidden text-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
                        <p class="mt-4 text-gray-600">Processing payment...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        const orderData = @json($orderData);
        const returnUrl = "{{ route('subscriptions.index') }}";
        const callbackUrl = "{{ route('api.payment.razorpay.callback') }}";

        document.getElementById('rzp-button').onclick = function(e) {
            e.preventDefault();

            var options = {
                "key": orderData.key_id,
                "amount": orderData.amount,
                "currency": orderData.currency,
                "name": "{{ config('app.name') }}",
                "description": "{{ $plan->name }} Subscription",
                "order_id": orderData.order_id,
                "handler": function (response) {
                    document.getElementById('payment-section').classList.add('hidden');
                    document.getElementById('loading').classList.remove('hidden');

                    // Send payment details to backend
                    fetch(callbackUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature,
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = returnUrl + '?payment=success';
                        } else {
                            alert('Payment verification failed. Please contact support.');
                            window.location.href = returnUrl + '?payment=failed';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please contact support.');
                        window.location.href = returnUrl + '?payment=error';
                    });
                },
                "prefill": {
                    "name": "{{ auth()->user()->name }}",
                    "email": "{{ auth()->user()->email }}",
                    "contact": "{{ auth()->user()->phone ?? '' }}"
                },
                "theme": {
                    "color": "#4F46E5"
                },
                "modal": {
                    "ondismiss": function() {
                        // User closed the payment modal
                        console.log('Payment cancelled by user');
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response) {
                alert('Payment failed: ' + response.error.description);
                window.location.href = returnUrl + '?payment=failed';
            });
            rzp.open();
        };
    </script>
    @endpush
</x-app-layout>
