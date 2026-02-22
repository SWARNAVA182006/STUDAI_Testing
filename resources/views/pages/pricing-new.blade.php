<x-marketing-layout 
    title="Pricing - StudAI Career | Choose Your Plan"
    description="Flexible pricing plans for every job seeker. Start free or upgrade to unlock premium AI-powered features. No hidden fees, cancel anytime.">

    {{-- Razorpay Checkout Script --}}
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    {{-- Hero Section --}}
    <section class="pt-32 pb-12 bg-gradient-to-br from-pink-50 via-white to-purple-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-6" data-aos="fade-up">
                Simple, Transparent <span class="gradient-text">Pricing</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8" data-aos="fade-up" data-aos-delay="100">
                Choose the perfect plan for your job search journey. All plans include 14-day money-back guarantee.
            </p>

            {{-- Billing Toggle --}}
            <div class="flex items-center justify-center gap-4 mb-12" data-aos="fade-up" data-aos-delay="200">
                <span class="text-gray-700 font-medium" id="monthly-label">Monthly</span>
                <button 
                    type="button" 
                    onclick="toggleBilling()" 
                    class="relative inline-flex h-8 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2"
                    role="switch"
                    aria-checked="false"
                    aria-labelledby="billing-toggle"
                    id="billing-toggle">
                    <span class="sr-only">Toggle billing period</span>
                    <span class="pointer-events-none inline-block h-7 w-7 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0" id="toggle-indicator"></span>
                </button>
                <span class="text-gray-700 font-medium" id="yearly-label">
                    Yearly 
                    <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                        Save up to 25%
                    </span>
                </span>
            </div>
        </div>
    </section>

    {{-- Pricing Cards --}}
    <section class="pb-20 -mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Monthly Plans --}}
            <div id="monthly-plans" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($monthlyPlans as $plan)
                <div class="relative bg-white rounded-2xl shadow-lg overflow-hidden {{ $plan->is_featured ? 'ring-2 ring-pink-500 scale-105 md:scale-110 z-10' : '' }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    @if($plan->is_featured)
                    <div class="absolute top-0 right-0 bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-1 text-xs font-semibold rounded-bl-lg">
                        MOST POPULAR
                    </div>
                    @endif

                    <div class="p-8">
                        {{-- Plan Name --}}
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 mb-6 min-h-[48px]">{{ $plan->description }}</p>

                        {{-- Price --}}
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-extrabold text-gray-900">₹{{ number_format($plan->price, 0) }}</span>
                                <span class="ml-2 text-gray-600">/month</span>
                            </div>
                            @if($plan->price == 0)
                            <p class="mt-2 text-sm text-gray-500">Forever free</p>
                            @else
                            <p class="mt-2 text-sm text-gray-500">Billed monthly</p>
                            @endif
                        </div>

                        {{-- CTA Button --}}
                        @auth
                            <button 
                                onclick="initiatePayment({{ $plan->id }}, 'razorpay')" 
                                class="w-full py-3 px-6 rounded-lg font-semibold transition-all duration-200 {{ $plan->is_featured ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:shadow-lg hover:scale-105' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                @if($plan->price == 0)
                                    Get Started Free
                                @else
                                    Subscribe Now
                                @endif
                            </button>
                        @else
                            <a 
                                href="{{ route('register') }}" 
                                class="block w-full text-center py-3 px-6 rounded-lg font-semibold transition-all duration-200 {{ $plan->is_featured ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:shadow-lg hover:scale-105' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                Sign Up to Get Started
                            </a>
                        @endauth

                        {{-- Features List --}}
                        <div class="mt-8 space-y-4">
                            <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">What's Included</h4>
                            
                            {{-- Applications Limit --}}
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">
                                    @if($plan->applications_limit)
                                        {{ $plan->applications_limit }} job applications/month
                                    @else
                                        Unlimited job applications
                                    @endif
                                </span>
                            </div>

                            {{-- AI Credits --}}
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">
                                    @if($plan->ai_credits > 0)
                                        {{ $plan->ai_credits }} AI credits/month
                                    @else
                                        Unlimited AI credits
                                    @endif
                                </span>
                            </div>

                            {{-- Job Alerts --}}
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">
                                    @if($plan->job_alerts_limit)
                                        {{ $plan->job_alerts_limit }} job alerts
                                    @else
                                        Unlimited job alerts
                                    @endif
                                </span>
                            </div>

                            {{-- Feature checks from JSON --}}
                            @if($plan->features)
                                @php $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features; @endphp
                                
                                @if(isset($features['ai_resume_review']) && $features['ai_resume_review'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">AI Resume Review</span>
                                </div>
                                @else
                                <div class="flex items-start opacity-40">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-400 line-through">AI Resume Review</span>
                                </div>
                                @endif

                                @if(isset($features['ai_cover_letter']) && $features['ai_cover_letter'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">AI Cover Letter Generator</span>
                                </div>
                                @else
                                <div class="flex items-start opacity-40">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-400 line-through">AI Cover Letter Generator</span>
                                </div>
                                @endif

                                @if(isset($features['ai_interview_prep']) && $features['ai_interview_prep'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">AI Interview Preparation</span>
                                </div>
                                @else
                                <div class="flex items-start opacity-40">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-400 line-through">AI Interview Preparation</span>
                                </div>
                                @endif

                                @if(isset($features['one_click_apply']) && $features['one_click_apply'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">One-Click Apply</span>
                                </div>
                                @else
                                <div class="flex items-start opacity-40">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-400 line-through">One-Click Apply</span>
                                </div>
                                @endif
                            @endif

                            {{-- Priority Support --}}
                            @if($plan->priority_support)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">Priority Support</span>
                            </div>
                            @endif

                            {{-- API Access --}}
                            @if($plan->api_access)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">API Access ({{ number_format($plan->api_calls_limit) }} calls/month)</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Yearly Plans --}}
            <div id="yearly-plans" class="hidden grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($yearlyPlans as $plan)
                <div class="relative bg-white rounded-2xl shadow-lg overflow-hidden {{ $plan->is_featured ? 'ring-2 ring-pink-500 scale-105 md:scale-110 z-10' : '' }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    @if($plan->is_featured)
                    <div class="absolute top-0 right-0 bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-1 text-xs font-semibold rounded-bl-lg">
                        BEST VALUE
                    </div>
                    @endif

                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 mb-6 min-h-[48px]">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-extrabold text-gray-900">₹{{ number_format($plan->price, 0) }}</span>
                                <span class="ml-2 text-gray-600">/year</span>
                            </div>
                            @php
                                $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features;
                                $savings = isset($features['savings_text']) ? $features['savings_text'] : null;
                            @endphp
                            @if($savings)
                            <p class="mt-2 text-sm text-green-600 font-semibold">{{ $savings }}</p>
                            @endif
                        </div>

                        @auth
                            <button 
                                onclick="initiatePayment({{ $plan->id }}, 'razorpay')" 
                                class="w-full py-3 px-6 rounded-lg font-semibold transition-all duration-200 {{ $plan->is_featured ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:shadow-lg hover:scale-105' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                Subscribe Now
                            </button>
                        @else
                            <a 
                                href="{{ route('register') }}" 
                                class="block w-full text-center py-3 px-6 rounded-lg font-semibold transition-all duration-200 {{ $plan->is_featured ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:shadow-lg hover:scale-105' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                Sign Up to Get Started
                            </a>
                        @endauth

                        <div class="mt-8 space-y-4">
                            <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">What's Included</h4>
                            
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">
                                    @if($plan->applications_limit)
                                        {{ number_format($plan->applications_limit) }} job applications/year
                                    @else
                                        Unlimited job applications
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">
                                    @if($plan->ai_credits > 0)
                                        {{ number_format($plan->ai_credits) }} AI credits/year
                                    @else
                                        Unlimited AI credits
                                    @endif
                                </span>
                            </div>

                            @if($plan->features)
                                @php $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features; @endphp
                                
                                @if(isset($features['ai_resume_review']) && $features['ai_resume_review'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">AI Resume Review</span>
                                </div>
                                @endif

                                @if(isset($features['ai_cover_letter']) && $features['ai_cover_letter'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">AI Cover Letter Generator</span>
                                </div>
                                @endif

                                @if(isset($features['ai_interview_prep']) && $features['ai_interview_prep'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">AI Interview Preparation</span>
                                </div>
                                @endif

                                @if(isset($features['one_click_apply']) && $features['one_click_apply'])
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-700">One-Click Apply</span>
                                </div>
                                @endif
                            @endif

                            @if($plan->priority_support)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">Priority Support</span>
                            </div>
                            @endif

                            @if($plan->api_access)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-3 text-gray-700">API Access ({{ number_format($plan->api_calls_limit) }} calls/year)</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Frequently Asked Questions
                </h2>
            </div>

            <div class="space-y-6" data-aos="fade-up">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I cancel my subscription anytime?</h3>
                    <p class="text-gray-600">Yes, you can cancel your subscription at any time. You'll continue to have access until the end of your billing period.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">What payment methods do you accept?</h3>
                    <p class="text-gray-600">We accept all major payment methods including UPI, credit/debit cards, net banking, and digital wallets through our secure payment partners Razorpay and PayU.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Is my payment information secure?</h3>
                    <p class="text-gray-600">Absolutely! We use industry-standard encryption and partner with trusted payment gateways. We never store your card details on our servers.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Do you offer refunds?</h3>
                    <p class="text-gray-600">Yes, we offer a 14-day money-back guarantee on all paid plans. If you're not satisfied, contact our support team for a full refund.</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I upgrade or downgrade my plan?</h3>
                    <p class="text-gray-600">Yes, you can change your plan anytime from your dashboard. Upgrades take effect immediately, while downgrades apply at the next billing cycle.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Payment Integration Script --}}
    @auth
    <script>
        let currentBillingPeriod = 'monthly';

        function toggleBilling() {
            currentBillingPeriod = currentBillingPeriod === 'monthly' ? 'yearly' : 'monthly';
            
            const toggle = document.getElementById('billing-toggle');
            const indicator = document.getElementById('toggle-indicator');
            const monthlyPlans = document.getElementById('monthly-plans');
            const yearlyPlans = document.getElementById('yearly-plans');
            
            if (currentBillingPeriod === 'yearly') {
                toggle.classList.add('bg-pink-600');
                toggle.classList.remove('bg-gray-200');
                indicator.classList.add('translate-x-6');
                indicator.classList.remove('translate-x-0');
                monthlyPlans.classList.add('hidden');
                yearlyPlans.classList.remove('hidden');
                yearlyPlans.classList.add('grid');
            } else {
                toggle.classList.remove('bg-pink-600');
                toggle.classList.add('bg-gray-200');
                indicator.classList.remove('translate-x-6');
                indicator.classList.add('translate-x-0');
                yearlyPlans.classList.add('hidden');
                yearlyPlans.classList.remove('grid');
                monthlyPlans.classList.remove('hidden');
                monthlyPlans.classList.add('grid');
            }
        }

        async function initiatePayment(planId, gateway = 'razorpay') {
            try {
                // Show loading
                const button = event.target;
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Processing...';

                // Call API to initiate payment
                const response = await fetch('/api/payment/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + '{{ auth()->user()->createToken("temp")->plainTextToken }}',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: planId,
                        gateway: gateway
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Payment initiation failed');
                }

                if (gateway === 'razorpay') {
                    openRazorpayCheckout(data.data);
                } else if (gateway === 'payu') {
                    submitPayUForm(data.data);
                }

                // Reset button
                button.disabled = false;
                button.textContent = originalText;
            } catch (error) {
                console.error('Payment error:', error);
                alert('Payment initiation failed: ' + error.message);
                button.disabled = false;
                button.textContent = originalText;
            }
        }

        function openRazorpayCheckout(orderData) {
            const options = {
                key: orderData.key,
                amount: orderData.amount,
                currency: orderData.currency,
                name: orderData.name,
                description: orderData.description,
                image: orderData.image,
                order_id: orderData.order_id,
                handler: function (response) {
                    verifyRazorpayPayment(response);
                },
                prefill: orderData.prefill,
                theme: orderData.theme,
                modal: {
                    ondismiss: function() {
                        console.log('Checkout modal closed');
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
        }

        async function verifyRazorpayPayment(response) {
            try {
                const result = await fetch('/api/payment/razorpay/callback', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + '{{ auth()->user()->createToken("temp")->plainTextToken }}',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(response)
                });

                const data = await result.json();

                if (data.success) {
                    // Redirect to dashboard with success message
                    window.location.href = '/dashboard?payment=success';
                } else {
                    alert('Payment verification failed: ' + data.message);
                }
            } catch (error) {
                console.error('Verification error:', error);
                alert('Payment verification failed. Please contact support.');
            }
        }

        function submitPayUForm(formData) {
            // Create form dynamically
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = formData.payment_url;

            // Add all form fields
            Object.keys(formData.form_data).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = formData.form_data[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    @endauth

    {{-- AOS Animation --}}
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</x-marketing-layout>
