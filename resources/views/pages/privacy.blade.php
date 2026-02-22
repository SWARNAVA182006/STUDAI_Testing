@extends('layouts.app')

@section('title', 'Privacy Policy - StudAI Career | Data Protection & User Privacy')

@section('meta')
<meta name="description" content="Learn how StudAI Career protects your personal data and privacy. Our comprehensive privacy policy covers data collection, usage, sharing, and your rights under GDPR and Indian data protection laws.">
<meta name="keywords" content="privacy policy, data protection, GDPR compliance, personal data, user privacy, data rights">
<meta property="og:title" content="Privacy Policy - StudAI Career">
<meta property="og:description" content="Our commitment to protecting your privacy and personal data. Transparent policies and user rights.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('privacy') }}">
<link rel="canonical" href="{{ route('privacy') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative py-16 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20 mb-8">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <span class="text-sm font-medium text-white">Last Updated: December 1, 2024</span>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight">
            Privacy Policy
        </h1>
        <p class="text-xl text-gray-200 leading-relaxed">
            Your privacy is important to us. This policy explains how we collect, use, and protect your personal information.
        </p>
    </div>
</section>

<!-- Table of Contents -->
<section class="py-8 bg-slate-950 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-white mb-4">Table of Contents</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @foreach ([
                ['title' => '1. Information We Collect', 'anchor' => 'information-we-collect'],
                ['title' => '2. How We Use Your Information', 'anchor' => 'how-we-use'],
                ['title' => '3. Information Sharing', 'anchor' => 'information-sharing'],
                ['title' => '4. Data Security', 'anchor' => 'data-security'],
                ['title' => '5. International Transfers', 'anchor' => 'international-transfers'],
                ['title' => '6. Data Retention', 'anchor' => 'data-retention'],
                ['title' => '7. Your Rights', 'anchor' => 'your-rights'],
                ['title' => '8. Cookies and Tracking', 'anchor' => 'cookies-tracking'],
                ['title' => '9. Children\'s Privacy', 'anchor' => 'childrens-privacy'],
                ['title' => '10. Changes to This Policy', 'anchor' => 'policy-changes'],
                ['title' => '11. Contact Us', 'anchor' => 'contact-us']
            ] as $item)
                <a href="#{{ $item['anchor'] }}" class="text-pink-400 hover:text-pink-300 transition-colors">{{ $item['title'] }}</a>
            @endforeach
        </div>
    </div>
</section>

<!-- Privacy Policy Content -->
<section class="py-16 bg-slate-950">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-invert prose-pink max-w-none">
            
            <!-- Introduction -->
            <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 mb-12">
                <p class="text-lg text-gray-200 leading-relaxed mb-6">
                    StudAI Career Private Limited ("we," "our," or "us") is committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website, mobile application, and services (collectively, the "Services").
                </p>
                <p class="text-gray-300 leading-relaxed">
                    By using our Services, you consent to the data practices described in this policy. If you do not agree with the terms of this Privacy Policy, please do not access or use our Services.
                </p>
            </div>

            <!-- Section 1: Information We Collect -->
            <div id="information-we-collect" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">1. Information We Collect</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">1.1 Personal Information You Provide</h3>
                <p class="text-gray-300 mb-4">We collect information you provide directly to us, including:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li><strong>Account Information:</strong> Name, email address, phone number, password, and profile picture</li>
                    <li><strong>Professional Information:</strong> Resume, work experience, education history, skills, certifications, and career preferences</li>
                    <li><strong>Payment Information:</strong> Billing address and payment method details (processed securely by our payment processors)</li>
                    <li><strong>Communication Data:</strong> Messages, feedback, and correspondence with our support team</li>
                    <li><strong>Job Application Data:</strong> Cover letters, application responses, and interview scheduling information</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">1.2 Information We Collect Automatically</h3>
                <p class="text-gray-300 mb-4">When you use our Services, we automatically collect:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li><strong>Usage Data:</strong> Pages visited, features used, time spent, and interaction patterns</li>
                    <li><strong>Device Information:</strong> IP address, browser type, operating system, device identifiers</li>
                    <li><strong>Location Data:</strong> General geographic location based on IP address</li>
                    <li><strong>Performance Data:</strong> Error logs, loading times, and system performance metrics</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">1.3 Information from Third Parties</h3>
                <p class="text-gray-300 mb-4">We may receive information from:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li><strong>Social Media:</strong> Profile information when you connect social media accounts</li>
                    <li><strong>Employers:</strong> Job listings, company information, and application status updates</li>
                    <li><strong>Data Providers:</strong> Professional networking data, industry insights, and market analytics</li>
                </ul>
            </div>

            <!-- Section 2: How We Use Your Information -->
            <div id="how-we-use" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">2. How We Use Your Information</h2>
                <p class="text-gray-300 mb-4">We use your information for the following purposes:</p>
                
                <h3 class="text-2xl font-semibold text-white mb-4">2.1 Service Provision</h3>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Create and manage your account</li>
                    <li>Match you with relevant job opportunities</li>
                    <li>Process job applications and facilitate communications with employers</li>
                    <li>Provide AI-powered resume optimization and career guidance</li>
                    <li>Enable interview scheduling and feedback collection</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">2.2 Service Improvement</h3>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Analyze usage patterns to improve our algorithms and features</li>
                    <li>Conduct research and development for new services</li>
                    <li>Personalize your experience and recommendations</li>
                    <li>Monitor and ensure the security and integrity of our Services</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">2.3 Communication</h3>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Send service-related notifications and updates</li>
                    <li>Provide customer support and respond to inquiries</li>
                    <li>Share career insights, job alerts, and promotional content (with your consent)</li>
                    <li>Conduct surveys and collect feedback</li>
                </ul>
            </div>

            <!-- Section 3: Information Sharing -->
            <div id="information-sharing" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">3. Information Sharing</h2>
                <p class="text-gray-300 mb-4">We share your information in the following circumstances:</p>

                <h3 class="text-2xl font-semibold text-white mb-4">3.1 With Employers</h3>
                <p class="text-gray-300 mb-4">When you apply for jobs or express interest in opportunities, we share relevant profile information with prospective employers, including:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Resume and professional experience</li>
                    <li>Skills, qualifications, and certifications</li>
                    <li>Contact information (with your consent)</li>
                    <li>Application responses and cover letters</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">3.2 Service Providers</h3>
                <p class="text-gray-300 mb-4">We work with trusted third-party service providers who assist in operating our Services:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Cloud hosting and storage providers</li>
                    <li>Payment processors (Razorpay, PayU)</li>
                    <li>Email and communication services</li>
                    <li>Analytics and monitoring tools</li>
                    <li>AI and machine learning service providers</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">3.3 Legal Compliance</h3>
                <p class="text-gray-300 mb-4">We may disclose information when required by law or to:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Comply with legal obligations, court orders, or government requests</li>
                    <li>Protect our rights, property, or safety, and that of our users</li>
                    <li>Investigate and prevent fraud, security breaches, or illegal activities</li>
                    <li>Enforce our Terms of Service</li>
                </ul>
            </div>

            <!-- Section 4: Data Security -->
            <div id="data-security" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">4. Data Security</h2>
                <p class="text-gray-300 mb-4">We implement comprehensive security measures to protect your information:</p>
                
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6 mb-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Technical Safeguards</h3>
                    <ul class="list-disc list-inside text-gray-300 space-y-2">
                        <li><strong>Encryption:</strong> Data encrypted in transit (TLS 1.3) and at rest (AES-256)</li>
                        <li><strong>Access Controls:</strong> Role-based access with multi-factor authentication</li>
                        <li><strong>Infrastructure:</strong> Secure cloud hosting with regular security updates</li>
                        <li><strong>Monitoring:</strong> 24/7 security monitoring and incident response</li>
                    </ul>
                </div>

                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6 mb-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Organizational Measures</h3>
                    <ul class="list-disc list-inside text-gray-300 space-y-2">
                        <li>Regular security training for all employees</li>
                        <li>Data minimization and purpose limitation principles</li>
                        <li>Regular security audits and penetration testing</li>
                        <li>Incident response and breach notification procedures</li>
                    </ul>
                </div>

                <p class="text-gray-300 text-sm">
                    <strong>Important:</strong> While we use industry-standard security measures, no method of transmission over the internet or electronic storage is 100% secure. We cannot guarantee absolute security but are committed to protecting your information to the best of our ability.
                </p>
            </div>

            <!-- Section 5: International Transfers -->
            <div id="international-transfers" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">5. International Data Transfers</h2>
                <p class="text-gray-300 mb-4">
                    StudAI Career operates globally with offices in India, Singapore, and Germany. Your information may be transferred to and processed in countries other than your country of residence, including countries that may have different data protection laws.
                </p>
                <p class="text-gray-300 mb-4">
                    When we transfer personal data internationally, we ensure appropriate safeguards are in place:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Standard Contractual Clauses approved by the European Commission</li>
                    <li>Adequacy decisions by relevant data protection authorities</li>
                    <li>Binding Corporate Rules for intra-group transfers</li>
                    <li>Consent for specific transfers where legally required</li>
                </ul>
            </div>

            <!-- Section 6: Data Retention -->
            <div id="data-retention" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">6. Data Retention</h2>
                <p class="text-gray-300 mb-4">We retain your information for as long as necessary to provide our Services and fulfill legal obligations:</p>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-slate-900/50 rounded-2xl border border-slate-800">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Data Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Retention Period</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-300">Account Information</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Until account deletion + 30 days</td>
                            </tr>
                            <tr class="bg-slate-800/30">
                                <td class="px-6 py-4 text-sm text-gray-300">Resume and Profile Data</td>
                                <td class="px-6 py-4 text-sm text-gray-300">5 years after last activity</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-300">Job Application Data</td>
                                <td class="px-6 py-4 text-sm text-gray-300">3 years after application</td>
                            </tr>
                            <tr class="bg-slate-800/30">
                                <td class="px-6 py-4 text-sm text-gray-300">Payment Information</td>
                                <td class="px-6 py-4 text-sm text-gray-300">7 years (tax compliance)</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-300">Usage and Analytics Data</td>
                                <td class="px-6 py-4 text-sm text-gray-300">2 years</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 7: Your Rights -->
            <div id="your-rights" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">7. Your Rights</h2>
                <p class="text-gray-300 mb-4">You have the following rights regarding your personal information:</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    @foreach ([
                        [
                            'title' => 'Access',
                            'description' => 'Request a copy of the personal information we hold about you',
                            'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'
                        ],
                        [
                            'title' => 'Rectification',
                            'description' => 'Request correction of inaccurate or incomplete information',
                            'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
                        ],
                        [
                            'title' => 'Erasure',
                            'description' => 'Request deletion of your personal information (subject to legal obligations)',
                            'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'
                        ],
                        [
                            'title' => 'Portability',
                            'description' => 'Receive your data in a structured, machine-readable format',
                            'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'
                        ],
                        [
                            'title' => 'Restriction',
                            'description' => 'Request limitation of processing in certain circumstances',
                            'icon' => 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z'
                        ],
                        [
                            'title' => 'Objection',
                            'description' => 'Object to processing based on legitimate interests or for marketing',
                            'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728'
                        ]
                    ] as $right)
                        <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-pink-500/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $right['icon'] }}"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-white">{{ $right['title'] }}</h3>
                            </div>
                            <p class="text-gray-300 text-sm">{{ $right['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-gradient-to-br from-pink-500/10 to-purple-500/10 rounded-2xl border border-pink-500/20 p-6">
                    <h3 class="text-xl font-semibold text-white mb-3">How to Exercise Your Rights</h3>
                    <p class="text-gray-300 mb-4">To exercise any of these rights, please contact us at:</p>
                    <ul class="list-disc list-inside text-gray-300 space-y-1">
                        <li>Email: privacy@studai.careers</li>
                        <li>Phone: +91-80-4567-8900</li>
                        <li>Mail: StudAI Career Private Limited, WeWork Prestige Atlanta, Bengaluru 560034</li>
                    </ul>
                    <p class="text-gray-300 text-sm mt-4">
                        We will respond to your request within 30 days and may require verification of your identity for security purposes.
                    </p>
                </div>
            </div>

            <!-- Section 8: Cookies and Tracking -->
            <div id="cookies-tracking" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">8. Cookies and Tracking Technologies</h2>
                <p class="text-gray-300 mb-4">We use cookies and similar technologies to enhance your experience:</p>

                <h3 class="text-2xl font-semibold text-white mb-4">8.1 Types of Cookies</h3>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li><strong>Essential Cookies:</strong> Required for basic functionality and security</li>
                    <li><strong>Performance Cookies:</strong> Help us analyze usage and improve our Services</li>
                    <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                    <li><strong>Marketing Cookies:</strong> Used for personalized advertising (with consent)</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">8.2 Cookie Management</h3>
                <p class="text-gray-300 mb-4">You can control cookies through:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Our cookie consent banner when you first visit our site</li>
                    <li>Your browser settings to block or delete cookies</li>
                    <li>Opt-out tools provided by advertising networks</li>
                    <li>Your account privacy settings for personalization features</li>
                </ul>
            </div>

            <!-- Section 9: Children's Privacy -->
            <div id="childrens-privacy" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">9. Children's Privacy</h2>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                    <p class="text-gray-300 mb-4">
                        Our Services are not intended for children under 16 years of age. We do not knowingly collect personal information from children under 16.
                    </p>
                    <p class="text-gray-300">
                        If we become aware that we have collected personal information from a child under 16 without parental consent, we will take steps to delete that information as quickly as possible. If you believe we have collected information from a child under 16, please contact us immediately.
                    </p>
                </div>
            </div>

            <!-- Section 10: Policy Changes -->
            <div id="policy-changes" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">10. Changes to This Privacy Policy</h2>
                <p class="text-gray-300 mb-4">
                    We may update this Privacy Policy from time to time to reflect changes in our practices, technology, legal requirements, or other factors.
                </p>
                <p class="text-gray-300 mb-4">
                    When we make material changes, we will:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Update the "Last Updated" date at the top of this policy</li>
                    <li>Notify you via email or prominent notice on our website</li>
                    <li>Provide at least 30 days notice for material changes</li>
                    <li>Obtain consent where required by applicable law</li>
                </ul>
            </div>

            <!-- Section 11: Contact Us -->
            <div id="contact-us" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">11. Contact Information</h2>
                <div class="bg-gradient-to-br from-pink-500/10 to-purple-500/10 rounded-3xl border border-pink-500/20 p-8">
                    <p class="text-gray-300 mb-6">
                        If you have questions about this Privacy Policy or our data practices, please contact us:
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-3">Data Protection Officer</h3>
                            <div class="space-y-2 text-gray-300">
                                <p>Email: privacy@studai.careers</p>
                                <p>Phone: +91-80-4567-8900</p>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-3">Postal Address</h3>
                            <div class="text-gray-300">
                                <p>StudAI Career Private Limited</p>
                                <p>WeWork Prestige Atlanta</p>
                                <p>80 Feet Road, Koramangala 4th Block</p>
                                <p>Bengaluru, Karnataka 560034</p>
                                <p>India</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-pink-500/20">
                        <p class="text-sm text-gray-400">
                            For EU residents: You also have the right to lodge a complaint with your local data protection authority if you believe we have not addressed your concerns adequately.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Privacy Policy - StudAI Career',
    'description' => 'StudAI Career\'s privacy policy explaining how we collect, use, and protect your personal information',
    'url' => route('privacy'),
    'lastReviewed' => '2024-12-01',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'StudAI Career',
        'url' => url('/')
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@push('styles')
<style>
.prose h2 {
    scroll-margin-top: 2rem;
}
.prose h3 {
    scroll-margin-top: 2rem;
}
html {
    scroll-behavior: smooth;
}
</style>
@endpush