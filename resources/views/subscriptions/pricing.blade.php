<x-marketing-layout>
    <div class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                    Choose Your <span class="text-indigo-600">Career Acceleration</span> Plan
                </h1>
                <p class="mt-4 text-xl text-gray-600 max-w-2xl mx-auto">
                    AI-powered job matching, resume optimization, and career intelligence
                </p>
            </div>

            <!-- Billing Toggle -->
            <div class="flex justify-center items-center mb-12">
                <span class="text-gray-700 mr-3">Monthly</span>
                <button id="billing-toggle" class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <span id="toggle-dot" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-1"></span>
                </button>
                <span class="text-gray-700 ml-3">
                    Yearly
                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        Save 20%
                    </span>
                </span>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                @foreach($plans as $plan)
                <div class="relative bg-white rounded-2xl shadow-lg {{ $plan->is_featured ? 'ring-2 ring-indigo-600 scale-105 lg:scale-110' : '' }}">
                    @if($plan->is_featured)
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <span class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold bg-indigo-600 text-white">
                            Most Popular
                        </span>
                    </div>
                    @endif

                    <div class="p-8">
                        <!-- Plan Name -->
                        <h3 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h3>
                        <p class="mt-2 text-sm text-gray-500">{{ $plan->description }}</p>

                        <!-- Price -->
                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-extrabold tracking-tight text-gray-900">
                                    <span class="monthly-price">₹{{ number_format($plan->price_monthly) }}</span>
                                    <span class="yearly-price hidden">₹{{ number_format($plan->price_yearly / 12) }}</span>
                                </span>
                                <span class="ml-2 text-xl text-gray-500">/month</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500 yearly-price hidden">
                                Billed ₹{{ number_format($plan->price_yearly) }} annually
                            </p>
                        </div>

                        <!-- Features -->
                        <ul class="mt-8 space-y-4">
                            @php
                                $features = [
                                    'applications_limit' => 'job applications per month',
                                    'ai_credits' => 'AI credits per month',
                                    'assessment_limit' => 'skill assessments per month',
                                ];
                            @endphp

                            @foreach($features as $key => $label)
                                <li class="flex items-start">
                                    <svg class="flex-shrink-0 h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="ml-3 text-gray-700">
                                        <strong>{{ $plan->$key == -1 ? 'Unlimited' : number_format($plan->$key) }}</strong> {{ $label }}
                                    </span>
                                </li>
                            @endforeach

                            @if($plan->has_priority_support)
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="ml-3 text-gray-700">Priority support</span>
                            </li>
                            @endif

                            @if($plan->has_advanced_analytics)
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="ml-3 text-gray-700">Advanced analytics</span>
                            </li>
                            @endif
                        </ul>

                        <!-- CTA Button -->
                        <div class="mt-8">
                            @auth
                                @if($userPlan && $userPlan->id == $plan->id)
                                    <button disabled class="w-full py-3 px-6 rounded-lg bg-gray-300 text-gray-600 font-semibold cursor-not-allowed">
                                        Current Plan
                                    </button>
                                @else
                                    <a href="{{ route('subscriptions.select-plan', ['plan_id' => $plan->id]) }}" 
                                       class="block w-full py-3 px-6 text-center rounded-lg font-semibold transition-colors {{ $plan->is_featured ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100' }}">
                                        @if($userPlan && $plan->price_monthly > $userPlan->price_monthly)
                                            Upgrade Now
                                        @elseif($userPlan)
                                            Change Plan
                                        @else
                                            Get Started
                                        @endif
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('register') }}" 
                                   class="block w-full py-3 px-6 text-center rounded-lg font-semibold transition-colors {{ $plan->is_featured ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100' }}">
                                    Start Free Trial
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- FAQ Section -->
            <div class="mt-20">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Frequently Asked Questions</h2>
                <div class="max-w-3xl mx-auto space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I change plans anytime?</h3>
                        <p class="text-gray-600">Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">What payment methods do you accept?</h3>
                        <p class="text-gray-600">We accept all major credit/debit cards, UPI, net banking, and digital wallets through Razorpay and PayU.</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Is there a refund policy?</h3>
                        <p class="text-gray-600">Yes, we offer a 7-day money-back guarantee if you're not satisfied with our service.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const toggle = document.getElementById('billing-toggle');
        const toggleDot = document.getElementById('toggle-dot');
        const monthlyPrices = document.querySelectorAll('.monthly-price');
        const yearlyPrices = document.querySelectorAll('.yearly-price');
        let isYearly = false;

        toggle.addEventListener('click', function() {
            isYearly = !isYearly;
            
            if (isYearly) {
                toggle.classList.add('bg-indigo-600');
                toggle.classList.remove('bg-gray-200');
                toggleDot.classList.add('translate-x-6');
                toggleDot.classList.remove('translate-x-1');
                
                monthlyPrices.forEach(el => el.classList.add('hidden'));
                yearlyPrices.forEach(el => el.classList.remove('hidden'));
            } else {
                toggle.classList.remove('bg-indigo-600');
                toggle.classList.add('bg-gray-200');
                toggleDot.classList.remove('translate-x-6');
                toggleDot.classList.add('translate-x-1');
                
                monthlyPrices.forEach(el => el.classList.remove('hidden'));
                yearlyPrices.forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
    @endpush
</x-marketing-layout>
