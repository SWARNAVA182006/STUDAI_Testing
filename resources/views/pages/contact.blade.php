@extends('layouts.app')

@section('title', 'Contact Us - StudAI Career | Support, Sales & Partnership Inquiries')

@section('meta')
<meta name="description" content="Get in touch with StudAI Career for support, sales inquiries, partnerships, or general questions. Multiple contact channels and global offices to serve you better.">
<meta name="keywords" content="contact StudAI Career, customer support, sales inquiries, partnership, help center, contact form">
<meta property="og:title" content="Contact Us - StudAI Career">
<meta property="og:description" content="Reach out to our team for support, sales inquiries, or partnerships. We're here to help accelerate your career journey.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('contact') }}">
<link rel="canonical" href="{{ route('contact') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative py-24 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 overflow-hidden">
    <!-- Background Animation -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 -left-4 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-0 -right-4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center text-white space-y-8">
            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="text-sm font-medium">24/7 Global Support</span>
            </div>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold leading-tight">
                Let's Connect &
                <span class="bg-gradient-to-r from-pink-400 via-purple-400 to-blue-400 bg-clip-text text-transparent">
                    Grow Together
                </span>
            </h1>

            <p class="text-xl md:text-2xl text-gray-200 leading-relaxed max-w-4xl mx-auto">
                Whether you need support, have questions about our platform, or want to explore partnership opportunities, 
                our team is here to help you succeed.
            </p>
        </div>
    </div>
</section>

<!-- Contact Methods -->
<section class="py-16 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Choose Your Preferred Way to Connect</h2>
            <p class="text-xl text-gray-300">Multiple channels to get the help you need, when you need it</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
            @foreach ([
                [
                    'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                    'title' => 'Live Chat',
                    'description' => 'Instant support for quick questions',
                    'detail' => 'Available 24/7',
                    'action' => 'Start Chat',
                    'color' => 'green'
                ],
                [
                    'icon' => 'M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'title' => 'Email Support',
                    'description' => 'Detailed help for complex issues',
                    'detail' => 'support@studai.careers',
                    'action' => 'Send Email',
                    'color' => 'blue'
                ],
                [
                    'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                    'title' => 'Phone Support',
                    'description' => 'Speak directly with our experts',
                    'detail' => '+91-80-4567-8900',
                    'action' => 'Call Now',
                    'color' => 'purple'
                ],
                [
                    'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => 'Help Center',
                    'description' => 'Self-service knowledge base',
                    'detail' => '500+ Articles & Guides',
                    'action' => 'Browse FAQ',
                    'color' => 'pink'
                ]
            ] as $method)
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 text-center hover:border-{{ $method['color'] }}-500/30 transition-all duration-300 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-{{ $method['color'] }}-500/20 to-{{ $method['color'] }}-600/20 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-{{ $method['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $method['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3">{{ $method['title'] }}</h3>
                    <p class="text-gray-300 mb-2">{{ $method['description'] }}</p>
                    <p class="text-sm text-{{ $method['color'] }}-400 font-semibold mb-6">{{ $method['detail'] }}</p>
                    <button class="inline-flex items-center gap-2 text-{{ $method['color'] }}-400 hover:text-{{ $method['color'] }}-300 font-semibold transition-colors">
                        {{ $method['action'] }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Contact Form & Info -->
<section class="py-16 bg-slate-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
            <!-- Contact Form -->
            <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8">
                <h2 class="text-3xl font-bold text-white mb-6">Send us a message</h2>
                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6" x-data="{ loading: false }">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all duration-300">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all duration-300">
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-300 mb-2">Subject *</label>
                        <select id="subject" name="subject" required 
                                class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all duration-300">
                            <option value="">Choose a topic</option>
                            <option value="general">General Inquiry</option>
                            <option value="support">Technical Support</option>
                            <option value="billing">Billing Question</option>
                            <option value="feature">Feature Request</option>
                            <option value="partnership">Partnership</option>
                            <option value="demo">Schedule Demo</option>
                        </select>
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-300 mb-2">Company (Optional)</label>
                        <input type="text" id="company" name="company" 
                               class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all duration-300">
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-300 mb-2">Message *</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Tell us how we can help you..."
                                  class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all duration-300 resize-none"></textarea>
                    </div>

                    <button type="submit" 
                            :disabled="loading"
                            @click="loading = true"
                            class="w-full px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl hover:shadow-pink-500/40 transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading" class="flex items-center justify-center gap-2">
                            Send Message
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </span>
                        <span x-show="loading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Company Info -->
            <div class="space-y-8">
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8">
                    <h2 class="text-3xl font-bold text-white mb-6">Global Offices</h2>
                    <div class="space-y-6">
                        @foreach ([
                            [
                                'city' => 'Bengaluru, India',
                                'address' => 'WeWork Prestige Atlanta, 80 Feet Road, Koramangala 4th Block',
                                'phone' => '+91-80-4567-8900',
                                'hours' => 'Mon-Fri: 9:00 AM - 6:00 PM IST'
                            ],
                            [
                                'city' => 'Singapore',
                                'address' => '1 Raffles Place, #20-61, One Raffles Place Tower 2',
                                'phone' => '+65-6789-0123',
                                'hours' => 'Mon-Fri: 9:00 AM - 6:00 PM SGT'
                            ],
                            [
                                'city' => 'Berlin, Germany',
                                'address' => 'Hackescher Markt 4, 10178 Berlin',
                                'phone' => '+49-30-1234-5678',
                                'hours' => 'Mon-Fri: 9:00 AM - 6:00 PM CET'
                            ]
                        ] as $office)
                            <div class="border-b border-slate-700 last:border-b-0 pb-6 last:pb-0">
                                <h3 class="text-xl font-semibold text-white mb-3">{{ $office['city'] }}</h3>
                                <div class="space-y-2 text-gray-300">
                                    <p class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-pink-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $office['address'] }}
                                    </p>
                                    <p class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ $office['phone'] }}
                                    </p>
                                    <p class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $office['hours'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gradient-to-br from-pink-500/10 to-purple-500/10 rounded-3xl border border-pink-500/20 p-8 backdrop-blur-md">
                    <h3 class="text-2xl font-bold text-white mb-4">Need Immediate Help?</h3>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        For critical issues affecting your account or urgent technical problems, 
                        please contact our priority support line.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="mailto:urgent@studai.careers" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-pink-500 hover:bg-pink-600 text-white font-semibold rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Priority Email
                        </a>
                        <a href="tel:+918045678900" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Call Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-16 bg-gradient-to-b from-slate-950 to-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Frequently Asked Questions</h2>
            <p class="text-xl text-gray-300">Quick answers to common questions</p>
        </div>

        <div class="space-y-6">
            @foreach ([
                [
                    'question' => 'How quickly do you respond to support requests?',
                    'answer' => 'We aim to respond to all inquiries within 24 hours during business days. Priority support requests are typically handled within 4 hours.'
                ],
                [
                    'question' => 'Do you offer phone support?',
                    'answer' => 'Yes, phone support is available for premium subscribers during business hours (9 AM - 6 PM IST). General inquiries can be handled via email or live chat.'
                ],
                [
                    'question' => 'Can I schedule a demo of the platform?',
                    'answer' => 'Absolutely! You can request a personalized demo by selecting "Schedule Demo" in the contact form above, or by emailing us directly at sales@studai.careers.'
                ],
                [
                    'question' => 'What information should I include in my support request?',
                    'answer' => 'Please include your account email, a detailed description of the issue, any error messages you\'re seeing, and steps to reproduce the problem if applicable.'
                ],
                [
                    'question' => 'Do you provide implementation support for enterprise customers?',
                    'answer' => 'Yes, we offer dedicated implementation support, training sessions, and account management for enterprise customers. Contact our sales team for more details.'
                ]
            ] as $index => $faq)
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800" x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full text-left p-6 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 focus:ring-offset-slate-900 rounded-2xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white pr-4">{{ $faq['question'] }}</h3>
                            <svg class="w-6 h-6 text-pink-400 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="px-6 pb-6">
                        <p class="text-gray-300 leading-relaxed">{{ $faq['answer'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => 'Contact StudAI Career',
    'description' => 'Get in touch with StudAI Career for support, sales inquiries, partnerships, or general questions',
    'url' => route('contact'),
    'mainEntity' => [
        '@type' => 'Organization',
        'name' => 'StudAI Career',
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => 'support@studai.careers',
                'telephone' => '+91-80-4567-8900',
                'areaServed' => 'IN',
                'availableLanguage' => ['English', 'Hindi']
            ],
            [
                '@type' => 'ContactPoint',
                'contactType' => 'sales',
                'email' => 'sales@studai.careers',
                'areaServed' => ['IN', 'SG', 'DE'],
                'availableLanguage' => 'English'
            ]
        ],
        'address' => [
            [
                '@type' => 'PostalAddress',
                'addressCountry' => 'IN',
                'addressLocality' => 'Bengaluru',
                'streetAddress' => 'WeWork Prestige Atlanta, 80 Feet Road, Koramangala 4th Block'
            ],
            [
                '@type' => 'PostalAddress',
                'addressCountry' => 'SG',
                'addressLocality' => 'Singapore',
                'streetAddress' => '1 Raffles Place, #20-61, One Raffles Place Tower 2'
            ]
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@push('styles')
<style>
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
@endpush
