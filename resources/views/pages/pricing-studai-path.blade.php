{{--
    StudAI Path — Pricing Page
    Simple, transparent pricing for job seekers and employers
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- SEO Meta Tags --}}
    <title>Pricing — Simple Plans for Job Seekers & Employers | StudAI Path</title>
    <meta name="description" content="Start free. Upgrade as you grow. Transparent pricing for job seekers and employers. No hidden fees. Cancel anytime.">
    <meta name="keywords" content="StudAI Path pricing, career OS pricing, ATS pricing, job search subscription">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="Simple Pricing. Powerful Results. | StudAI Path">
    <meta property="og:description" content="Start free. Scale as you grow. No hidden fees.">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-ink-primary" x-data="{ tab: 'seekers' }">
    {{-- Navigation --}}
    @include('partials.nav-marketing')

    <main>
        {{-- Hero --}}
        <section class="pt-32 pb-12 bg-canvas-subtle">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-ink-primary mb-6">
                    Simple Pricing. <span class="text-gradient">Powerful Results.</span>
                </h1>
                <p class="text-xl text-ink-secondary max-w-2xl mx-auto mb-8">
                    Start free. Scale as you grow. No hidden fees.
                </p>
                
                {{-- Toggle --}}
                <div class="inline-flex items-center bg-surface-100 rounded-full p-1 mb-12">
                    <button 
                        @click="tab = 'seekers'" 
                        :class="tab === 'seekers' ? 'bg-white shadow-sm text-ink-primary' : 'text-ink-secondary'"
                        class="px-6 py-2 rounded-full text-sm font-medium transition-all">
                        For Job Seekers
                    </button>
                    <button 
                        @click="tab = 'employers'" 
                        :class="tab === 'employers' ? 'bg-white shadow-sm text-ink-primary' : 'text-ink-secondary'"
                        class="px-6 py-2 rounded-full text-sm font-medium transition-all">
                        For Employers
                    </button>
                </div>
            </div>
        </section>

        {{-- Job Seeker Plans --}}
        <section x-show="tab === 'seekers'" class="py-16 bg-canvas-subtle">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-8">
                    {{-- Free Plan --}}
                    <div class="bg-white rounded-2xl border border-surface-200 shadow-card p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-ink-primary mb-1">Free</h3>
                            <p class="text-sm text-ink-tertiary">Everything you need to start your job search.</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-ink-primary">₹0</span>
                            <span class="text-ink-tertiary">/forever</span>
                        </div>
                        <a href="{{ route('register') }}" class="studai-btn studai-btn-outline w-full mb-8">
                            Get Started Free
                        </a>
                        <ul class="space-y-3">
                            @foreach(['Unlimited job search', 'Basic resume builder', '5 AI interview sessions/month', 'Application tracking', 'Basic salary insights'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Pro Plan --}}
                    <div class="bg-white rounded-2xl border-2 border-google-blue-500 shadow-elevation-3 p-8 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="px-4 py-1 bg-google-blue-600 text-white text-xs font-semibold rounded-full">Most Popular</span>
                        </div>
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-ink-primary mb-1">Pro</h3>
                            <p class="text-sm text-ink-tertiary">Accelerate your career with AI automation.</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-ink-primary">₹499</span>
                            <span class="text-ink-tertiary">/month</span>
                        </div>
                        <a href="{{ route('register') }}" class="studai-btn studai-btn-primary w-full mb-8">
                            Start Pro Trial
                        </a>
                        <ul class="space-y-3">
                            @foreach(['Everything in Free', 'Autonomous Agent (50 apps/day)', 'Unlimited AI interviews', 'Premium resume templates', 'Salary negotiation coach', 'Priority support'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Executive Plan --}}
                    <div class="bg-white rounded-2xl border border-surface-200 shadow-card p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-ink-primary mb-1">Executive</h3>
                            <p class="text-sm text-ink-tertiary">For senior professionals and career changers.</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-ink-primary">₹1,999</span>
                            <span class="text-ink-tertiary">/month</span>
                        </div>
                        <a href="{{ route('register') }}" class="studai-btn studai-btn-outline w-full mb-8">
                            Go Executive
                        </a>
                        <ul class="space-y-3">
                            @foreach(['Everything in Pro', 'Unlimited agent applications', 'Executive resume service', '1:1 career coaching sessions', 'LinkedIn profile optimization', 'Dedicated success manager'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Employer Plans --}}
        <section x-show="tab === 'employers'" class="py-16 bg-canvas-subtle" x-cloak>
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-8">
                    {{-- Starter --}}
                    <div class="bg-white rounded-2xl border border-surface-200 shadow-card p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-ink-primary mb-1">Starter</h3>
                            <p class="text-sm text-ink-tertiary">For small teams making their first hires.</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-ink-primary">₹0</span>
                            <span class="text-ink-tertiary">/forever</span>
                        </div>
                        <a href="{{ route('register') }}" class="studai-btn studai-btn-outline w-full mb-8">
                            Start Hiring Free
                        </a>
                        <ul class="space-y-3">
                            @foreach(['3 active job posts', 'Basic candidate management', 'Email notifications', 'Standard job board distribution'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Growth --}}
                    <div class="bg-white rounded-2xl border-2 border-google-blue-500 shadow-elevation-3 p-8 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="px-4 py-1 bg-google-blue-600 text-white text-xs font-semibold rounded-full">Best Value</span>
                        </div>
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-ink-primary mb-1">Growth</h3>
                            <p class="text-sm text-ink-tertiary">For growing companies scaling their teams.</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-ink-primary">₹4,999</span>
                            <span class="text-ink-tertiary">/month</span>
                        </div>
                        <a href="{{ route('register') }}" class="studai-btn studai-btn-primary w-full mb-8">
                            Start Growth Trial
                        </a>
                        <ul class="space-y-3">
                            @foreach(['Unlimited job posts', 'S.C.O.U.T. AI screening', 'Team collaboration tools', '50+ job board distribution', 'Interview scheduling', 'Basic analytics'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Enterprise --}}
                    <div class="bg-white rounded-2xl border border-surface-200 shadow-card p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-ink-primary mb-1">Enterprise</h3>
                            <p class="text-sm text-ink-tertiary">For large organizations with complex needs.</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-extrabold text-ink-primary">Custom</span>
                        </div>
                        <a href="{{ route('contact') }}" class="studai-btn studai-btn-outline w-full mb-8">
                            Contact Sales
                        </a>
                        <ul class="space-y-3">
                            @foreach(['Everything in Growth', 'Custom integrations (HRIS, ATS)', 'Dedicated account manager', 'Advanced analytics & reporting', 'SSO & advanced security', 'Custom SLAs', 'On-premise deployment option'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-ink-secondary">
                                <svg class="w-5 h-5 text-google-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Trust Note --}}
        <section class="py-8 bg-canvas-subtle">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <p class="text-sm text-ink-tertiary">
                    All plans include our core platform. No setup fees. Cancel anytime. 14-day free trial on all paid plans.
                </p>
            </div>
        </section>

        {{-- FAQ Section --}}
        <section class="py-20 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-ink-primary text-center mb-12">Frequently Asked Questions</h2>
                
                <div class="space-y-4">
                    <details class="bg-surface-50 rounded-xl border border-surface-200 group" open>
                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                            <h3 class="font-semibold text-ink-primary">Can I switch plans anytime?</h3>
                            <svg class="w-5 h-5 text-ink-tertiary group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-6 text-ink-secondary">
                            Yes. Upgrade or downgrade at any time. Changes take effect immediately, and we prorate accordingly.
                        </div>
                    </details>

                    <details class="bg-surface-50 rounded-xl border border-surface-200 group">
                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                            <h3 class="font-semibold text-ink-primary">Is there a free trial for paid plans?</h3>
                            <svg class="w-5 h-5 text-ink-tertiary group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-6 text-ink-secondary">
                            Yes. All paid plans include a 14-day free trial. No credit card required to start.
                        </div>
                    </details>

                    <details class="bg-surface-50 rounded-xl border border-surface-200 group">
                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                            <h3 class="font-semibold text-ink-primary">What payment methods do you accept?</h3>
                            <svg class="w-5 h-5 text-ink-tertiary group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-6 text-ink-secondary">
                            We accept all major credit cards, debit cards, UPI, and net banking. Enterprise plans can pay via invoice.
                        </div>
                    </details>

                    <details class="bg-surface-50 rounded-xl border border-surface-200 group">
                        <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                            <h3 class="font-semibold text-ink-primary">Is my data secure?</h3>
                            <svg class="w-5 h-5 text-ink-tertiary group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <div class="px-6 pb-6 text-ink-secondary">
                            Absolutely. We use bank-grade encryption, are SOC 2 compliant, and never sell your data.
                        </div>
                    </details>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="py-20 bg-gradient-to-br from-google-blue-600 to-purple-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Ready to put your career on autopilot?
                </h2>
                <p class="text-lg text-white/80 mb-8">
                    Start free today. No credit card required.
                </p>
                <a href="{{ route('register') }}" class="studai-btn bg-white text-google-blue-600 hover:bg-gray-100 studai-btn-xl">
                    Get Started Free
                </a>
            </div>
        </section>
    </main>

    @include('partials.footer-marketing')
</body>
</html>
