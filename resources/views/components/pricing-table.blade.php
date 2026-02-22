@props([
    'title' => 'Choose Your Plan',
    'subtitle' => 'Select the perfect plan for your job search journey',
    'plans' => [],
    'billingPeriod' => 'monthly' // monthly or yearly
])

<section class="py-20 bg-gradient-to-br from-gray-50 to-white" x-data="{ billingPeriod: '{{ $billingPeriod }}' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                {{ $title }}
            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-8">
                {{ $subtitle }}
            </p>

            {{-- Billing Toggle --}}
            <div class="inline-flex items-center bg-white rounded-full p-1 shadow-md">
                <button @click="billingPeriod = 'monthly'" 
                        :class="billingPeriod === 'monthly' ? 'bg-pink-600 text-white' : 'text-gray-600'"
                        class="px-6 py-2 rounded-full font-medium transition">
                    Monthly
                </button>
                <button @click="billingPeriod = 'yearly'" 
                        :class="billingPeriod === 'yearly' ? 'bg-pink-600 text-white' : 'text-gray-600'"
                        class="px-6 py-2 rounded-full font-medium transition">
                    Yearly
                    <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Save 17%</span>
                </button>
            </div>
        </div>

        {{-- Pricing Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
            @forelse($plans as $index => $plan)
            <div class="relative bg-white rounded-2xl shadow-xl border-2 {{ $plan['featured'] ?? false ? 'border-pink-500 transform scale-105' : 'border-gray-200' }} p-8 transition hover:shadow-2xl"
                 data-aos="fade-up" 
                 data-aos-delay="{{ $index * 100 }}">
                
                {{-- Featured Badge --}}
                @if($plan['featured'] ?? false)
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-gradient-to-r from-pink-600 to-pink-500 text-white px-4 py-1 rounded-full text-sm font-semibold shadow-lg">
                        Most Popular
                    </span>
                </div>
                @endif

                {{-- Plan Header --}}
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        {{ $plan['name'] ?? 'Plan Name' }}
                    </h3>
                    <p class="text-gray-600 text-sm">
                        {{ $plan['description'] ?? 'Plan description' }}
                    </p>
                </div>

                {{-- Pricing --}}
                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center">
                        <span class="text-gray-600 mr-2">₹</span>
                        <span class="text-5xl font-extrabold text-gray-900" x-text="billingPeriod === 'monthly' ? '{{ $plan['price_monthly'] ?? 0 }}' : '{{ $plan['price_yearly'] ?? 0 }}'">
                            {{ $plan['price_monthly'] ?? 0 }}
                        </span>
                        <span class="text-gray-600 ml-2" x-text="billingPeriod === 'monthly' ? '/month' : '/year'">
                            /month
                        </span>
                    </div>
                    @if(isset($plan['price_yearly']))
                    <p class="text-sm text-gray-500 mt-2" x-show="billingPeriod === 'yearly'">
                        ₹{{ number_format($plan['price_yearly'] / 12, 0) }}/month billed annually
                    </p>
                    @endif
                </div>

                {{-- CTA Button --}}
                <a href="{{ route('register') }}?plan={{ $plan['slug'] ?? 'free' }}" 
                   class="block w-full py-3 px-6 text-center font-semibold rounded-lg mb-6 transition {{ $plan['featured'] ?? false ? 'bg-gradient-to-r from-pink-600 to-pink-500 text-white hover:shadow-xl transform hover:scale-105' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                    {{ $plan['cta_text'] ?? 'Get Started' }}
                </a>

                {{-- Features List --}}
                <ul class="space-y-4">
                    @if(isset($plan['features']) && is_array($plan['features']))
                        @foreach($plan['features'] as $feature)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </li>
                        @endforeach
                    @endif
                </ul>

                @if(isset($plan['limitations']) && is_array($plan['limitations']))
                <ul class="mt-4 space-y-4 border-t pt-4">
                    @foreach($plan['limitations'] as $limitation)
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-500 line-through">{{ $limitation }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @empty
            {{-- Default Plans if none provided --}}
            <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-200 p-8 transition hover:shadow-xl" data-aos="fade-up">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                    <p class="text-gray-600 text-sm">Perfect for getting started</p>
                </div>
                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center">
                        <span class="text-gray-600 mr-2">₹</span>
                        <span class="text-5xl font-extrabold text-gray-900">0</span>
                        <span class="text-gray-600 ml-2">/month</span>
                    </div>
                </div>
                <a href="{{ route('register') }}" class="block w-full py-3 px-6 text-center bg-gray-100 text-gray-900 font-semibold rounded-lg hover:bg-gray-200 mb-6 transition">
                    Get Started
                </a>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">5 job applications/month</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Basic job search</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Profile creation</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Weekly job alerts</span>
                    </li>
                </ul>
            </div>

            <div class="relative bg-white rounded-2xl shadow-xl border-2 border-pink-500 p-8 transform scale-105 transition hover:shadow-2xl" data-aos="fade-up" data-aos-delay="100">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span class="bg-gradient-to-r from-pink-600 to-pink-500 text-white px-4 py-1 rounded-full text-sm font-semibold shadow-lg">
                        Most Popular
                    </span>
                </div>
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
                    <p class="text-gray-600 text-sm">For serious job seekers</p>
                </div>
                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center">
                        <span class="text-gray-600 mr-2">₹</span>
                        <span class="text-5xl font-extrabold text-gray-900" x-text="billingPeriod === 'monthly' ? '499' : '4999'">499</span>
                        <span class="text-gray-600 ml-2" x-text="billingPeriod === 'monthly' ? '/month' : '/year'">/month</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2" x-show="billingPeriod === 'yearly'">₹416/month billed annually</p>
                </div>
                <a href="{{ route('register') }}?plan=professional" class="block w-full py-3 px-6 text-center bg-gradient-to-r from-pink-600 to-pink-500 text-white font-semibold rounded-lg hover:shadow-xl transform hover:scale-105 mb-6 transition">
                    Get Started
                </a>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">50 applications/month</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">AI resume optimization</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Cover letter generator</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Daily job alerts</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Priority email support</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border-2 border-gray-200 p-8 transition hover:shadow-xl" data-aos="fade-up" data-aos-delay="200">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Premium</h3>
                    <p class="text-gray-600 text-sm">Maximum career acceleration</p>
                </div>
                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center">
                        <span class="text-gray-600 mr-2">₹</span>
                        <span class="text-5xl font-extrabold text-gray-900" x-text="billingPeriod === 'monthly' ? '1499' : '14999'">1499</span>
                        <span class="text-gray-600 ml-2" x-text="billingPeriod === 'monthly' ? '/month' : '/year'">/month</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2" x-show="billingPeriod === 'yearly'">₹1250/month billed annually</p>
                </div>
                <a href="{{ route('register') }}?plan=premium" class="block w-full py-3 px-6 text-center bg-gray-100 text-gray-900 font-semibold rounded-lg hover:bg-gray-200 mb-6 transition">
                    Get Started
                </a>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Unlimited applications</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">AI interview preparation</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Profile highlighting</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Instant job alerts</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">Dedicated support</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-pink-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">API access</span>
                    </li>
                </ul>
            </div>
            @endforelse
        </div>

        {{-- FAQ or Additional Info --}}
        <div class="mt-16 text-center">
            <p class="text-gray-600 mb-4">
                All plans include a 14-day money-back guarantee. Cancel anytime.
            </p>
            <a href="{{ route('contact') }}" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium">
                Need a custom plan? Contact us
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
