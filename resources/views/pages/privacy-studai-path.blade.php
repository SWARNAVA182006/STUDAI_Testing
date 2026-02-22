{{--
    StudAI Path — Privacy Policy
    Data Protection & User Privacy
--}}
@extends('layouts.app')

@section('title', 'Privacy Policy — StudAI Path | Your Data, Protected')

@section('meta')
<meta name="description" content="Learn how StudAI Path protects your personal data and privacy. Our policy covers data collection, AI processing, and your rights under GDPR and Indian data protection laws.">
<meta property="og:title" content="Privacy Policy — StudAI Path">
<meta property="og:description" content="Your privacy matters. See how we protect your career data.">
<link rel="canonical" href="{{ route('privacy') }}">
@endsection

@section('content')
{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-primary via-slate-900 to-ink-primary text-white">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute -top-40 -left-12 h-96 w-96 rounded-full bg-google-blue-500/30 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-4xl px-6 py-20 text-center">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2 text-sm font-medium mb-6">
            <svg class="w-4 h-4 text-google-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Last Updated: January 15, 2025
        </div>
        <h1 class="text-4xl font-bold sm:text-5xl">Privacy Policy</h1>
        <p class="mt-4 text-lg text-slate-200 max-w-2xl mx-auto">
            Your privacy is fundamental to how we build StudAI Path. This policy explains what data we collect, why, and how we protect it.
        </p>
    </div>
</section>

{{-- Quick Summary --}}
<section class="py-8 bg-canvas-subtle border-b border-surface-200">
    <div class="mx-auto max-w-5xl px-6">
        <div class="grid md:grid-cols-4 gap-4">
            @foreach ([
                ['icon' => '🔒', 'title' => 'Encrypted Storage', 'body' => 'AES-256 encryption at rest'],
                ['icon' => '🚫', 'title' => 'No Data Sales', 'body' => 'We never sell your info'],
                ['icon' => '🗑️', 'title' => 'Right to Delete', 'body' => 'Delete anytime, we comply'],
                ['icon' => '🌍', 'title' => 'GDPR/DPDP Ready', 'body' => 'Global compliance'],
            ] as $item)
                <div class="bg-white rounded-xl border border-surface-200 p-4 text-center">
                    <span class="text-2xl">{{ $item['icon'] }}</span>
                    <h3 class="text-sm font-semibold text-ink-primary mt-2">{{ $item['title'] }}</h3>
                    <p class="text-xs text-ink-muted mt-1">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Table of Contents --}}
<section class="py-8 bg-white border-b border-surface-200">
    <div class="mx-auto max-w-4xl px-6">
        <h2 class="text-lg font-semibold text-ink-primary mb-4">Contents</h2>
        <div class="grid md:grid-cols-2 gap-2 text-sm">
            @foreach ([
                ['title' => '1. Information We Collect', 'anchor' => 'info-collect'],
                ['title' => '2. How We Use Your Data', 'anchor' => 'how-use'],
                ['title' => '3. AI & Automated Processing', 'anchor' => 'ai-processing'],
                ['title' => '4. Data Sharing', 'anchor' => 'data-sharing'],
                ['title' => '5. Data Security', 'anchor' => 'security'],
                ['title' => '6. Your Rights', 'anchor' => 'your-rights'],
                ['title' => '7. Cookies & Tracking', 'anchor' => 'cookies'],
                ['title' => '8. Data Retention', 'anchor' => 'retention'],
                ['title' => '9. International Transfers', 'anchor' => 'transfers'],
                ['title' => '10. Contact Us', 'anchor' => 'contact'],
            ] as $item)
                <a href="#{{ $item['anchor'] }}" class="text-google-blue-600 hover:underline">{{ $item['title'] }}</a>
            @endforeach
        </div>
    </div>
</section>

{{-- Policy Content --}}
<section class="py-16 bg-white">
    <div class="mx-auto max-w-4xl px-6">
        <div class="prose prose-lg max-w-none">
            
            {{-- Introduction --}}
            <div class="bg-surface-50 rounded-2xl border border-surface-200 p-6 mb-12 not-prose">
                <p class="text-ink-secondary">
                    StudAI Path Technologies Private Limited ("we," "our," or "us") operates the StudAI Path platform (the "Services"). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website, mobile apps, and AI-powered career tools.
                </p>
                <p class="text-ink-secondary mt-4">
                    By using our Services, you agree to this policy. If you disagree, please don't use our Services.
                </p>
            </div>

            {{-- Section 1 --}}
            <div id="info-collect" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">1. Information We Collect</h2>
                
                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">1.1 Information You Provide</h3>
                <ul class="space-y-2 text-ink-secondary">
                    <li><strong>Account Info:</strong> Name, email, phone, password, profile photo</li>
                    <li><strong>Resume/CV:</strong> Work history, education, skills, certifications</li>
                    <li><strong>Preferences:</strong> Job preferences, salary expectations, locations</li>
                    <li><strong>Communications:</strong> Messages you send via chat or support</li>
                    <li><strong>Payment Info:</strong> Billing details processed securely by Razorpay/Stripe</li>
                </ul>

                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">1.2 Information Collected Automatically</h3>
                <ul class="space-y-2 text-ink-secondary">
                    <li><strong>Usage Data:</strong> Pages viewed, features used, session duration</li>
                    <li><strong>Device Info:</strong> IP address, browser type, OS, device ID</li>
                    <li><strong>Interview Sessions:</strong> Audio/video during mock interviews (with consent)</li>
                </ul>

                <h3 class="text-xl font-semibold text-ink-primary mt-6 mb-3">1.3 Information from Third Parties</h3>
                <ul class="space-y-2 text-ink-secondary">
                    <li><strong>LinkedIn:</strong> Profile data if you connect your account</li>
                    <li><strong>Job Boards:</strong> Application status from integrated platforms</li>
                </ul>
            </div>

            {{-- Section 2 --}}
            <div id="how-use" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">2. How We Use Your Data</h2>
                <p class="text-ink-secondary mt-4">We use your information to:</p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>✅ Provide, operate, and improve our Services</li>
                    <li>✅ Match you with relevant job opportunities</li>
                    <li>✅ Generate personalized resume suggestions</li>
                    <li>✅ Power AI interview coaching and feedback</li>
                    <li>✅ Provide salary benchmarks and market insights</li>
                    <li>✅ Process payments and manage subscriptions</li>
                    <li>✅ Send service updates and marketing (with opt-out)</li>
                    <li>✅ Detect fraud and ensure platform security</li>
                </ul>
            </div>

            {{-- Section 3 --}}
            <div id="ai-processing" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">3. AI & Automated Processing</h2>
                <div class="bg-google-blue-50 border border-google-blue-200 rounded-xl p-6 mt-4 not-prose">
                    <h4 class="font-semibold text-ink-primary mb-2">Important: How AI Uses Your Data</h4>
                    <p class="text-ink-secondary text-sm">
                        StudAI Path uses AI models (including Azure OpenAI) to power features like resume optimization, job matching, and interview coaching. Here's how your data is handled:
                    </p>
                    <ul class="text-sm text-ink-secondary mt-3 space-y-2">
                        <li>🔹 Your data is processed but <strong>never used to train public AI models</strong></li>
                        <li>🔹 AI outputs are generated in real-time and not stored long-term</li>
                        <li>🔹 You can opt out of AI processing (some features will be limited)</li>
                        <li>🔹 Human review is available for automated decisions on request</li>
                    </ul>
                </div>
            </div>

            {{-- Section 4 --}}
            <div id="data-sharing" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">4. Data Sharing</h2>
                <p class="text-ink-secondary mt-4">We share your data only in these cases:</p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>With Employers:</strong> When you apply to jobs, your profile/resume is shared</li>
                    <li><strong>Service Providers:</strong> Cloud hosting (Azure), payments (Razorpay), analytics</li>
                    <li><strong>Legal Requirements:</strong> If required by law or to protect rights</li>
                    <li><strong>Business Transfers:</strong> In case of merger or acquisition (with notice)</li>
                </ul>
                <p class="text-ink-secondary mt-4 font-semibold">We never sell your personal data to advertisers or data brokers.</p>
            </div>

            {{-- Section 5 --}}
            <div id="security" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">5. Data Security</h2>
                <p class="text-ink-secondary mt-4">We implement industry-standard security measures:</p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li>🔐 AES-256 encryption for data at rest</li>
                    <li>🔐 TLS 1.3 encryption for data in transit</li>
                    <li>🔐 SOC 2 Type II certified infrastructure</li>
                    <li>🔐 Regular penetration testing and audits</li>
                    <li>🔐 Role-based access control for employees</li>
                    <li>🔐 24/7 security monitoring and alerting</li>
                </ul>
            </div>

            {{-- Section 6 --}}
            <div id="your-rights" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">6. Your Rights</h2>
                <p class="text-ink-secondary mt-4">Depending on your location, you may have these rights:</p>
                <div class="grid md:grid-cols-2 gap-4 mt-4 not-prose">
                    @foreach ([
                        ['right' => 'Access', 'desc' => 'Request a copy of your data'],
                        ['right' => 'Correction', 'desc' => 'Fix inaccurate information'],
                        ['right' => 'Deletion', 'desc' => 'Delete your account and data'],
                        ['right' => 'Portability', 'desc' => 'Export your data in a standard format'],
                        ['right' => 'Opt-out', 'desc' => 'Unsubscribe from marketing'],
                        ['right' => 'Restrict', 'desc' => 'Limit how we process your data'],
                    ] as $item)
                        <div class="bg-surface-50 rounded-lg border border-surface-200 p-4">
                            <h4 class="font-semibold text-ink-primary">{{ $item['right'] }}</h4>
                            <p class="text-sm text-ink-secondary">{{ $item['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="text-ink-secondary mt-4">To exercise these rights, email <a href="mailto:privacy@studaipath.com" class="text-google-blue-600 hover:underline">privacy@studaipath.com</a> or use the in-app settings.</p>
            </div>

            {{-- Section 7 --}}
            <div id="cookies" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">7. Cookies & Tracking</h2>
                <p class="text-ink-secondary mt-4">We use cookies for:</p>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>Essential:</strong> Login sessions, security, preferences</li>
                    <li><strong>Analytics:</strong> Usage patterns to improve the product</li>
                    <li><strong>Marketing:</strong> Personalized ads (with consent, opt-out available)</li>
                </ul>
                <p class="text-ink-secondary mt-4">Manage preferences in your browser settings or our cookie banner.</p>
            </div>

            {{-- Section 8 --}}
            <div id="retention" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">8. Data Retention</h2>
                <ul class="space-y-2 text-ink-secondary mt-4">
                    <li><strong>Active accounts:</strong> Data retained while account is active</li>
                    <li><strong>Deleted accounts:</strong> Data deleted within 30 days (backups within 90)</li>
                    <li><strong>Legal requirements:</strong> Some data retained as required by law</li>
                </ul>
            </div>

            {{-- Section 9 --}}
            <div id="transfers" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">9. International Transfers</h2>
                <p class="text-ink-secondary mt-4">
                    Your data may be processed in India, Singapore, or the USA where our servers and partners are located. We ensure adequate protection through Standard Contractual Clauses and equivalent safeguards.
                </p>
            </div>

            {{-- Section 10 --}}
            <div id="contact" class="mb-12">
                <h2 class="text-2xl font-bold text-ink-primary">10. Contact Us</h2>
                <div class="bg-surface-50 rounded-xl border border-surface-200 p-6 mt-4 not-prose">
                    <p class="text-ink-secondary mb-4">Questions about this policy or your data?</p>
                    <ul class="space-y-2 text-ink-secondary">
                        <li><strong>Email:</strong> <a href="mailto:privacy@studaipath.com" class="text-google-blue-600 hover:underline">privacy@studaipath.com</a></li>
                        <li><strong>Data Protection Officer:</strong> <a href="mailto:dpo@studaipath.com" class="text-google-blue-600 hover:underline">dpo@studaipath.com</a></li>
                        <li><strong>Address:</strong> StudAI Path Technologies Pvt. Ltd., WeWork Prestige Atlanta, Koramangala, Bengaluru 560034, India</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
