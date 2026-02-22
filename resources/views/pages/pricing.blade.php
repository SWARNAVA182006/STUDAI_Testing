<x-marketing-layout 
    title="Pricing - StudAI Career | Choose Your Plan"
    description="Flexible pricing plans for every job seeker. Start free or upgrade to unlock premium AI-powered features. No hidden fees, cancel anytime.">

    {{-- Hero Section --}}
    <section class="pt-32 pb-12 bg-gradient-to-br from-pink-50 via-white to-purple-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 mb-6" data-aos="fade-up">
                Simple, Transparent <span class="gradient-text">Pricing</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-4" data-aos="fade-up" data-aos-delay="100">
                Choose the perfect plan for your job search journey. All plans include 14-day money-back guarantee.
            </p>
        </div>
    </section>

    {{-- Pricing Table --}}
    <x-pricing-table />

    {{-- Comparison Table --}}
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Feature Comparison
                </h2>
                <p class="text-xl text-gray-600">
                    See what's included in each plan
                </p>
            </div>

            <div class="overflow-x-auto" data-aos="fade-up">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="py-4 px-6 text-left font-semibold text-gray-900">Feature</th>
                            <th class="py-4 px-6 text-center font-semibold text-gray-900">Free</th>
                            <th class="py-4 px-6 text-center font-semibold text-pink-600 bg-pink-50">Professional</th>
                            <th class="py-4 px-6 text-center font-semibold text-gray-900">Premium</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Job Applications per Month</td>
                            <td class="py-4 px-6 text-center">5</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50">50</td>
                            <td class="py-4 px-6 text-center">Unlimited</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">AI Job Matching</td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-6 text-center bg-pink-50/50">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Resume Optimization</td>
                            <td class="py-4 px-6 text-center text-gray-400">−</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Cover Letter Generator</td>
                            <td class="py-4 px-6 text-center text-gray-400">−</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Interview Preparation</td>
                            <td class="py-4 px-6 text-center text-gray-400">−</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50 text-gray-400">−</td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Profile Highlighting</td>
                            <td class="py-4 px-6 text-center text-gray-400">−</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50 text-gray-400">−</td>
                            <td class="py-4 px-6 text-center">
                                <svg class="w-5 h-5 mx-auto text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Job Alerts</td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600">Weekly</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50 text-sm text-gray-600">Daily</td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600">Instant</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">Email Support</td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600">Standard</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50 text-sm text-gray-600">Priority</td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600">Dedicated</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-6 text-gray-700">API Access</td>
                            <td class="py-4 px-6 text-center text-gray-400">−</td>
                            <td class="py-4 px-6 text-center bg-pink-50/50 text-gray-400">−</td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600">1000 calls/mo</td>
                        </tr>
                    </tbody>
                </table>
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
                <div class="bg-white rounded-lg p-6 shadow-sm" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-semibold text-gray-900">Can I cancel my subscription anytime?</h3>
                        <svg class="w-5 h-5 transform transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-4 text-gray-600">
                        Yes! You can cancel your subscription at any time. There are no long-term contracts or cancellation fees. If you cancel, you'll continue to have access until the end of your current billing period.
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-semibold text-gray-900">What payment methods do you accept?</h3>
                        <svg class="w-5 h-5 transform transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-4 text-gray-600">
                        We accept all major credit/debit cards, UPI, net banking, and popular digital wallets through Razorpay and PayU. All payments are secured with industry-standard encryption.
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-semibold text-gray-900">Do you offer refunds?</h3>
                        <svg class="w-5 h-5 transform transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-4 text-gray-600">
                        Yes! We offer a 14-day money-back guarantee. If you're not satisfied with our service within the first 14 days, contact our support team for a full refund, no questions asked.
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-semibold text-gray-900">Can I upgrade or downgrade my plan?</h3>
                        <svg class="w-5 h-5 transform transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-4 text-gray-600">
                        Absolutely! You can upgrade or downgrade your plan at any time. When upgrading, you'll be charged a prorated amount. When downgrading, credits will be applied to your next billing cycle.
                    </div>
                </div>

                <div class="bg-white rounded-lg p-6 shadow-sm" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                        <h3 class="text-lg font-semibold text-gray-900">Is there a free trial?</h3>
                        <svg class="w-5 h-5 transform transition" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="mt-4 text-gray-600">
                        Our Free plan allows you to experience the platform without any payment. You can upgrade to a paid plan anytime to unlock premium features. No credit card required for the free plan.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-cta-section 
        title="Start Your Job Search Today"
        subtitle="Join thousands of successful job seekers and find your dream job"
        buttonText="Get Started Free"
        buttonUrl="{{ route('register') }}"
    />

    @push('scripts')
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
    @endpush
</x-marketing-layout>
