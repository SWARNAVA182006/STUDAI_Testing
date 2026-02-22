{{--
    StudAI Path — Contact Page
    Support, Sales & Partnership Inquiries
--}}
@extends('layouts.app')

@section('title', 'Contact Us — StudAI Path | Support, Sales & Partnership Inquiries')

@section('meta')
<meta name="description" content="Get in touch with StudAI Path for support, sales inquiries, partnerships, or general questions. We're here to help automate your career.">
<meta property="og:title" content="Contact Us — StudAI Path">
<meta property="og:description" content="Reach out to our team for support or explore how we can automate your career journey.">
<link rel="canonical" href="{{ route('contact') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
        <div class="absolute top-20 right-0 h-[28rem] w-[28rem] rounded-full bg-purple-500/20 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-6xl px-6 py-24 lg:py-32 text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-semibold uppercase tracking-widest mb-6">
            <svg class="w-4 h-4 text-google-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            24/7 Support Available
        </span>
        <h1 class="text-4xl font-bold sm:text-5xl lg:text-6xl max-w-4xl mx-auto">
            We're Here to Help Your Career Take Off
        </h1>
        <p class="mt-6 text-lg text-slate-200 max-w-2xl mx-auto">
            Whether you need support, have questions, or want to explore partnerships — our team is ready to assist.
        </p>
    </div>
</section>

{{-- Quick Contact Methods --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
                    'title' => 'Live Chat',
                    'description' => 'Instant help, 24/7',
                    'action' => 'Start Chatting',
                    'color' => 'google-green',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                    'title' => 'Email Us',
                    'description' => 'hello@studaipath.com',
                    'action' => 'Send Email',
                    'color' => 'google-blue',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
                    'title' => 'Call Us',
                    'description' => '+91-80-4567-8900',
                    'action' => 'Call Now',
                    'color' => 'purple',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'title' => 'Help Center',
                    'description' => '500+ articles',
                    'action' => 'Browse FAQ',
                    'color' => 'google-yellow',
                ],
            ] as $method)
                <div class="group rounded-2xl border border-surface-200 bg-surface-50 p-6 text-center hover:shadow-card transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-{{ $method['color'] }}-100 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-{{ $method['color'] }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            {!! $method['icon'] !!}
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-ink-primary mb-1">{{ $method['title'] }}</h3>
                    <p class="text-sm text-ink-secondary mb-4">{{ $method['description'] }}</p>
                    <button class="inline-flex items-center gap-1 text-google-blue-600 font-semibold text-sm hover:underline">
                        {{ $method['action'] }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Contact Form & Company Info --}}
<section class="py-16 bg-canvas-subtle">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid lg:grid-cols-[1.3fr_1fr] gap-12">
            {{-- Contact Form --}}
            <div class="bg-white rounded-3xl border border-surface-200 p-8 shadow-card">
                <h2 class="text-2xl font-bold text-ink-primary mb-2">Send us a message</h2>
                <p class="text-ink-secondary mb-8">We'll get back to you within 24 hours.</p>
                
                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6" x-data="{ loading: false }">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-ink-primary mb-2">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-ink-primary placeholder-ink-muted focus:ring-2 focus:ring-google-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-ink-primary mb-2">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-ink-primary placeholder-ink-muted focus:ring-2 focus:ring-google-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-ink-primary mb-2">What can we help with? *</label>
                        <select id="subject" name="subject" required 
                                class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-ink-primary focus:ring-2 focus:ring-google-blue-500 focus:border-transparent transition-all">
                            <option value="">Choose a topic</option>
                            <option value="general">General Question</option>
                            <option value="support">Technical Support</option>
                            <option value="billing">Billing & Payments</option>
                            <option value="feature">Feature Request</option>
                            <option value="partnership">Partnership Inquiry</option>
                            <option value="demo">Schedule a Demo</option>
                            <option value="enterprise">Enterprise Sales</option>
                        </select>
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-medium text-ink-primary mb-2">Company (Optional)</label>
                        <input type="text" id="company" name="company" 
                               class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-ink-primary placeholder-ink-muted focus:ring-2 focus:ring-google-blue-500 focus:border-transparent transition-all">
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-ink-primary mb-2">Message *</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Tell us what's on your mind..."
                                  class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-ink-primary placeholder-ink-muted focus:ring-2 focus:ring-google-blue-500 focus:border-transparent transition-all resize-none"></textarea>
                    </div>

                    <button type="submit" 
                            :disabled="loading"
                            @click="loading = true"
                            class="w-full studai-btn studai-btn-primary studai-btn-lg flex items-center justify-center gap-2">
                        <span x-show="!loading">Send Message</span>
                        <svg x-show="!loading" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending...
                        </span>
                    </button>
                </form>
            </div>

            {{-- Company Info --}}
            <div class="space-y-8">
                {{-- Office Locations --}}
                <div class="bg-white rounded-2xl border border-surface-200 p-6">
                    <h3 class="text-xl font-semibold text-ink-primary mb-6">Our Offices</h3>
                    <div class="space-y-6">
                        @foreach ([
                            [
                                'city' => 'Bengaluru, India',
                                'flag' => '🇮🇳',
                                'address' => 'WeWork Prestige Atlanta, Koramangala 4th Block',
                                'phone' => '+91-80-4567-8900',
                                'label' => 'Headquarters',
                            ],
                            [
                                'city' => 'Singapore',
                                'flag' => '🇸🇬',
                                'address' => '1 Raffles Place, Tower 2, #20-61',
                                'phone' => '+65-6789-0123',
                                'label' => 'APAC',
                            ],
                        ] as $office)
                            <div class="flex gap-4">
                                <span class="text-2xl">{{ $office['flag'] }}</span>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-semibold text-ink-primary">{{ $office['city'] }}</h4>
                                        <span class="text-xs px-2 py-0.5 bg-google-blue-50 text-google-blue-700 rounded-full">{{ $office['label'] }}</span>
                                    </div>
                                    <p class="text-sm text-ink-secondary">{{ $office['address'] }}</p>
                                    <p class="text-sm text-ink-secondary">{{ $office['phone'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Priority Support --}}
                <div class="bg-gradient-to-br from-google-blue-50 to-purple-50 rounded-2xl border border-google-blue-200 p-6">
                    <h3 class="text-xl font-semibold text-ink-primary mb-3">Need Urgent Help?</h3>
                    <p class="text-ink-secondary mb-6">
                        For critical issues affecting your job applications or account, contact our priority line.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="mailto:urgent@studaipath.com" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-google-blue-600 hover:bg-google-blue-700 text-white font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Priority Email
                        </a>
                        <a href="tel:+918045678900" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-surface-200 text-ink-primary font-semibold rounded-xl hover:bg-surface-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Call Support
                        </a>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="bg-white rounded-2xl border border-surface-200 p-6">
                    <h3 class="text-xl font-semibold text-ink-primary mb-4">Follow Us</h3>
                    <div class="flex gap-4">
                        @foreach ([
                            ['name' => 'Twitter', 'url' => 'https://twitter.com/studaipath', 'icon' => 'M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84'],
                            ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/studai-path', 'icon' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
                        ] as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="w-10 h-10 rounded-full bg-surface-100 hover:bg-google-blue-100 flex items-center justify-center transition-colors group">
                                <svg class="w-5 h-5 text-ink-muted group-hover:text-google-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="{{ $social['icon'] }}"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FAQ Section --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-4xl px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-ink-primary mb-4">Common Questions</h2>
            <p class="text-lg text-ink-secondary">Quick answers before you reach out</p>
        </div>

        <div class="space-y-4">
            @foreach ([
                [
                    'question' => 'How quickly do you respond to support requests?',
                    'answer' => 'Most inquiries get a response within 24 hours. Priority support requests are handled within 4 hours during business hours.',
                ],
                [
                    'question' => 'Can I get a demo of the Autonomous Agent?',
                    'answer' => 'Absolutely! Select "Schedule a Demo" in the form above or email sales@studaipath.com. We\'ll set up a personalized walkthrough.',
                ],
                [
                    'question' => 'Do you offer enterprise pricing?',
                    'answer' => 'Yes. For teams of 50+ or organizations with custom compliance needs, contact our enterprise team for tailored pricing and SLAs.',
                ],
                [
                    'question' => 'How do I cancel my subscription?',
                    'answer' => 'You can cancel anytime from your account settings. No cancellation fees. Your data stays accessible for 30 days after cancellation.',
                ],
                [
                    'question' => 'Is my data secure?',
                    'answer' => 'We\'re SOC 2 Type II compliant with end-to-end encryption. Your resume and personal data are never shared with third parties.',
                ],
            ] as $index => $faq)
                <div class="rounded-2xl border border-surface-200 bg-surface-50" x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full text-left p-6 focus:outline-none focus:ring-2 focus:ring-google-blue-500 rounded-2xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-ink-primary pr-4">{{ $faq['question'] }}</h3>
                            <svg class="w-5 h-5 text-google-blue-600 transform transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>
                    <div x-show="open" x-transition class="px-6 pb-6">
                        <p class="text-ink-secondary">{{ $faq['answer'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-24 bg-gradient-to-br from-google-blue-600 to-purple-600">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
            Ready to put your career on autopilot?
        </h2>
        <p class="text-lg text-white/80 mb-8">
            Join 50,000+ professionals who let AI manage their job search.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                Start Free Today
            </a>
            <a href="{{ route('pricing') }}" class="studai-btn border-2 border-white text-white hover:bg-white/10 studai-btn-xl">
                See Pricing
            </a>
        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => 'Contact StudAI Path',
    'description' => 'Get in touch with StudAI Path for support, sales, or partnership inquiries',
    'url' => route('contact'),
    'mainEntity' => [
        '@type' => 'Organization',
        'name' => 'StudAI Path',
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => 'hello@studaipath.com',
                'telephone' => '+91-80-4567-8900',
                'areaServed' => 'IN',
                'availableLanguage' => ['English', 'Hindi'],
            ],
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'IN',
            'addressLocality' => 'Bengaluru',
            'streetAddress' => 'WeWork Prestige Atlanta, Koramangala 4th Block',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush
