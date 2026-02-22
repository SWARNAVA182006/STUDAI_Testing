@extends('layouts.app')

@section('title', 'Terms and Conditions - StudAI Career | Service Terms & User Agreement')

@section('meta')
<meta name="description" content="Read the Terms and Conditions for StudAI Career services. Learn about user obligations, service terms, intellectual property rights, and liability provisions.">
<meta name="keywords" content="terms and conditions, terms of service, user agreement, service terms, legal agreement">
<meta property="og:title" content="Terms and Conditions - StudAI Career">
<meta property="og:description" content="Terms of Service governing the use of StudAI Career platform and services.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('terms') }}">
<link rel="canonical" href="{{ route('terms') }}">
@endsection

@section('content')
<!-- Hero Section -->
<section class="relative py-16 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20 mb-8">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-medium text-white">Last Updated: December 1, 2024</span>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight">
            Terms and Conditions
        </h1>
        <p class="text-xl text-gray-200 leading-relaxed">
            Please read these Terms and Conditions carefully before using our services.
        </p>
    </div>
</section>

<!-- Table of Contents -->
<section class="py-8 bg-slate-950 border-b border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-white mb-4">Table of Contents</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @foreach ([
                ['title' => '1. Acceptance of Terms', 'anchor' => 'acceptance-of-terms'],
                ['title' => '2. Description of Services', 'anchor' => 'description-of-services'],
                ['title' => '3. User Accounts', 'anchor' => 'user-accounts'],
                ['title' => '4. User Obligations', 'anchor' => 'user-obligations'],
                ['title' => '5. Prohibited Uses', 'anchor' => 'prohibited-uses'],
                ['title' => '6. Content and Intellectual Property', 'anchor' => 'content-intellectual-property'],
                ['title' => '7. Payments and Subscriptions', 'anchor' => 'payments-subscriptions'],
                ['title' => '8. Privacy and Data Protection', 'anchor' => 'privacy-data-protection'],
                ['title' => '9. Service Availability', 'anchor' => 'service-availability'],
                ['title' => '10. Disclaimers', 'anchor' => 'disclaimers'],
                ['title' => '11. Limitation of Liability', 'anchor' => 'limitation-of-liability'],
                ['title' => '12. Indemnification', 'anchor' => 'indemnification'],
                ['title' => '13. Termination', 'anchor' => 'termination'],
                ['title' => '14. Dispute Resolution', 'anchor' => 'dispute-resolution'],
                ['title' => '15. Governing Law', 'anchor' => 'governing-law'],
                ['title' => '16. Changes to Terms', 'anchor' => 'changes-to-terms'],
                ['title' => '17. Contact Information', 'anchor' => 'contact-information']
            ] as $item)
                <a href="#{{ $item['anchor'] }}" class="text-blue-400 hover:text-blue-300 transition-colors">{{ $item['title'] }}</a>
            @endforeach
        </div>
    </div>
</section>

<!-- Terms Content -->
<section class="py-16 bg-slate-950">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-invert prose-blue max-w-none">
            
            <!-- Introduction -->
            <div class="bg-slate-900/50 backdrop-blur-sm rounded-3xl border border-slate-800 p-8 mb-12">
                <p class="text-lg text-gray-200 leading-relaxed mb-6">
                    These Terms and Conditions ("Terms", "Agreement") govern your use of the StudAI Career website, mobile application, and services ("Services") operated by StudAI Career Private Limited ("we", "us", "our", or "Company").
                </p>
                <p class="text-gray-300 leading-relaxed">
                    By accessing or using our Services, you agree to be bound by these Terms. If you disagree with any part of these Terms, then you may not access the Services.
                </p>
            </div>

            <!-- Section 1: Acceptance of Terms -->
            <div id="acceptance-of-terms" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">1. Acceptance of Terms</h2>
                <p class="text-gray-300 mb-4">
                    By accessing, browsing, or otherwise using our Services, you acknowledge that you have read, understood, and agree to be bound by these Terms and all applicable laws and regulations. You also agree to comply with our Privacy Policy, which is incorporated by reference into these Terms.
                </p>
                <p class="text-gray-300 mb-4">
                    If you are using our Services on behalf of an organization, you represent and warrant that you have the authority to bind that organization to these Terms.
                </p>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-3">Eligibility</h3>
                    <p class="text-gray-300 text-sm">
                        You must be at least 16 years old to use our Services. By using our Services, you represent and warrant that you meet this age requirement.
                    </p>
                </div>
            </div>

            <!-- Section 2: Description of Services -->
            <div id="description-of-services" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">2. Description of Services</h2>
                <p class="text-gray-300 mb-4">StudAI Career provides an AI-powered career platform that includes:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h3 class="text-lg font-semibold text-white mb-3">For Job Seekers</h3>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-1">
                            <li>AI-powered job matching and recommendations</li>
                            <li>Resume optimization and review services</li>
                            <li>Career guidance and coaching</li>
                            <li>Skill assessments and certifications</li>
                            <li>Interview preparation and practice</li>
                            <li>Application tracking and management</li>
                        </ul>
                    </div>
                    
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h3 class="text-lg font-semibold text-white mb-3">For Employers</h3>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-1">
                            <li>Intelligent candidate matching</li>
                            <li>Applicant tracking system (ATS)</li>
                            <li>Job posting and management</li>
                            <li>AI-powered screening and assessments</li>
                            <li>Interview scheduling and management</li>
                            <li>Analytics and reporting</li>
                        </ul>
                    </div>
                </div>
                
                <p class="text-gray-300 text-sm">
                    We reserve the right to modify, suspend, or discontinue any aspect of our Services at any time, with or without notice.
                </p>
            </div>

            <!-- Section 3: User Accounts -->
            <div id="user-accounts" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">3. User Accounts</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">3.1 Account Creation</h3>
                <p class="text-gray-300 mb-4">To access certain features of our Services, you must create an account. You agree to:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Provide accurate, complete, and current information during registration</li>
                    <li>Maintain and promptly update your account information</li>
                    <li>Maintain the security and confidentiality of your login credentials</li>
                    <li>Accept responsibility for all activities that occur under your account</li>
                    <li>Notify us immediately of any unauthorized use of your account</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">3.2 Account Types</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-slate-900/50 rounded-2xl border border-slate-800">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Account Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Features</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Obligations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-white">Job Seeker</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Profile creation, job search, applications</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Accurate profile information</td>
                            </tr>
                            <tr class="bg-slate-800/30">
                                <td class="px-6 py-4 text-sm font-medium text-white">Employer</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Job posting, candidate management, ATS</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Valid business information, legal compliance</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-white">Premium</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Enhanced features, priority support</td>
                                <td class="px-6 py-4 text-sm text-gray-300">Payment obligations, fair usage</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 4: User Obligations -->
            <div id="user-obligations" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">4. User Obligations</h2>
                <p class="text-gray-300 mb-4">When using our Services, you agree to:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h3 class="text-lg font-semibold text-white mb-3">General Obligations</h3>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                            <li>Use the Services lawfully and in good faith</li>
                            <li>Respect the rights and privacy of other users</li>
                            <li>Provide accurate and truthful information</li>
                            <li>Comply with applicable laws and regulations</li>
                            <li>Report violations or suspicious activity</li>
                        </ul>
                    </div>
                    
                    <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                        <h3 class="text-lg font-semibold text-white mb-3">Content Standards</h3>
                        <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                            <li>Ensure content is accurate and not misleading</li>
                            <li>Avoid discriminatory or offensive language</li>
                            <li>Respect intellectual property rights</li>
                            <li>Maintain professional communication standards</li>
                            <li>Protect confidential information</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Section 5: Prohibited Uses -->
            <div id="prohibited-uses" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">5. Prohibited Uses</h2>
                <p class="text-gray-300 mb-4">You may not use our Services to:</p>
                
                <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Strictly Prohibited Activities</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300">
                        <ul class="list-disc list-inside space-y-2">
                            <li>Violate any applicable laws or regulations</li>
                            <li>Infringe on intellectual property rights</li>
                            <li>Transmit malicious code or viruses</li>
                            <li>Engage in fraudulent or deceptive practices</li>
                            <li>Harass, threaten, or abuse other users</li>
                            <li>Spam or send unsolicited communications</li>
                        </ul>
                        <ul class="list-disc list-inside space-y-2">
                            <li>Scrape or extract data without permission</li>
                            <li>Attempt to gain unauthorized access</li>
                            <li>Interfere with service functionality</li>
                            <li>Create multiple accounts to circumvent limits</li>
                            <li>Use the service for competitive intelligence</li>
                            <li>Post discriminatory or offensive content</li>
                        </ul>
                    </div>
                </div>
                
                <p class="text-gray-300 text-sm">
                    Violation of these prohibited uses may result in immediate termination of your account and potential legal action.
                </p>
            </div>

            <!-- Section 6: Content and Intellectual Property -->
            <div id="content-intellectual-property" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">6. Content and Intellectual Property</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">6.1 User Content</h3>
                <p class="text-gray-300 mb-4">You retain ownership of content you submit to our Services ("User Content"). However, by submitting User Content, you grant us:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>A worldwide, non-exclusive, royalty-free license to use, reproduce, modify, adapt, publish, translate, and distribute your User Content</li>
                    <li>The right to sublicense these rights to third parties (such as employers for job applications)</li>
                    <li>The right to use your User Content for service improvement and AI training (in aggregated, anonymized form)</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">6.2 Our Intellectual Property</h3>
                <p class="text-gray-300 mb-4">The Services and their content, including but not limited to:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Software, algorithms, and AI models</li>
                    <li>Text, graphics, logos, and design elements</li>
                    <li>Trademarks, service marks, and trade names</li>
                    <li>Data compilations and database structures</li>
                </ul>
                <p class="text-gray-300 mb-4">
                    Are owned by StudAI Career or our licensors and are protected by copyright, trademark, and other intellectual property laws.
                </p>

                <h3 class="text-2xl font-semibold text-white mb-4">6.3 DMCA Compliance</h3>
                <p class="text-gray-300 mb-4">
                    We respect intellectual property rights and comply with the Digital Millennium Copyright Act (DMCA). If you believe your copyright has been infringed, please contact us at legal@studai.careers with:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Identification of the copyrighted work</li>
                    <li>Location of the allegedly infringing material</li>
                    <li>Your contact information</li>
                    <li>A statement of good faith belief</li>
                    <li>A statement of accuracy under penalty of perjury</li>
                    <li>Your physical or electronic signature</li>
                </ul>
            </div>

            <!-- Section 7: Payments and Subscriptions -->
            <div id="payments-subscriptions" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">7. Payments and Subscriptions</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">7.1 Subscription Plans</h3>
                <p class="text-gray-300 mb-4">We offer various subscription plans with different features and pricing. By subscribing, you agree to:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Pay all applicable fees and charges</li>
                    <li>Provide accurate billing information</li>
                    <li>Authorize recurring charges for subscription renewals</li>
                    <li>Comply with usage limits and fair use policies</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">7.2 Payment Processing</h3>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6 mb-6">
                    <p class="text-gray-300 mb-4">We use secure third-party payment processors:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-white mb-2">Razorpay</h4>
                            <p class="text-sm text-gray-300">Primary payment gateway for Indian users supporting UPI, cards, net banking, and wallets</p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white mb-2">PayU</h4>
                            <p class="text-sm text-gray-300">Alternative payment gateway with additional banking partnerships</p>
                        </div>
                    </div>
                </div>

                <h3 class="text-2xl font-semibold text-white mb-4">7.3 Refunds and Cancellations</h3>
                <p class="text-gray-300 mb-4">
                    Refund and cancellation policies are detailed in our <a href="{{ route('refund-policy') }}" class="text-blue-400 hover:text-blue-300">Refund Policy</a>. Key points include:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>7-day money-back guarantee for new subscriptions</li>
                    <li>Pro-rated refunds for annual plans cancelled within 30 days</li>
                    <li>No refunds for usage-based charges or completed services</li>
                    <li>Cancellation takes effect at the end of the current billing period</li>
                </ul>
            </div>

            <!-- Section 8: Privacy and Data Protection -->
            <div id="privacy-data-protection" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">8. Privacy and Data Protection</h2>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                    <p class="text-gray-300 mb-4">
                        Your privacy is important to us. Our collection, use, and protection of your personal information is governed by our <a href="{{ route('privacy') }}" class="text-blue-400 hover:text-blue-300">Privacy Policy</a>, which is incorporated into these Terms by reference.
                    </p>
                    <p class="text-gray-300 text-sm">
                        By using our Services, you consent to the collection and use of your information as described in our Privacy Policy, including the use of cookies and similar technologies.
                    </p>
                </div>
            </div>

            <!-- Section 9: Service Availability -->
            <div id="service-availability" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">9. Service Availability</h2>
                <p class="text-gray-300 mb-4">We strive to provide reliable and continuous service, but we cannot guarantee:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Uninterrupted access to our Services</li>
                    <li>Error-free operation of all features</li>
                    <li>Availability during maintenance periods</li>
                    <li>Compatibility with all devices or browsers</li>
                    <li>Access during force majeure events</li>
                </ul>
                
                <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-3">Service Level Expectations</h3>
                    <p class="text-gray-300 text-sm mb-2">We target 99.5% uptime excluding scheduled maintenance. Planned maintenance will be announced at least 24 hours in advance when possible.</p>
                    <p class="text-gray-300 text-sm">For service status updates, visit our status page or follow our social media channels.</p>
                </div>
            </div>

            <!-- Section 10: Disclaimers -->
            <div id="disclaimers" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">10. Disclaimers</h2>
                <div class="bg-orange-500/10 border border-orange-500/20 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Important Legal Disclaimers</h3>
                    <div class="space-y-4 text-sm text-gray-300">
                        <p>
                            <strong>AS IS BASIS:</strong> Our Services are provided "as is" and "as available" without warranties of any kind, either express or implied, including but not limited to implied warranties of merchantability, fitness for a particular purpose, or non-infringement.
                        </p>
                        <p>
                            <strong>NO EMPLOYMENT GUARANTEE:</strong> We do not guarantee that use of our Services will result in employment offers, successful job applications, or any specific career outcomes.
                        </p>
                        <p>
                            <strong>AI LIMITATIONS:</strong> Our AI-powered features are tools to assist users and may not always provide accurate or complete information. Users should exercise their own judgment and not rely solely on AI recommendations.
                        </p>
                        <p>
                            <strong>THIRD-PARTY CONTENT:</strong> We are not responsible for the accuracy, completeness, or reliability of job postings, employer information, or other third-party content on our platform.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 11: Limitation of Liability -->
            <div id="limitation-of-liability" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">11. Limitation of Liability</h2>
                <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-6">
                    <p class="text-gray-300 mb-4">
                        To the maximum extent permitted by applicable law, StudAI Career shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to:
                    </p>
                    <ul class="list-disc list-inside text-gray-300 mb-4 space-y-2">
                        <li>Loss of profits, revenue, or business opportunities</li>
                        <li>Loss of data or information</li>
                        <li>Cost of procurement of substitute services</li>
                        <li>Business interruption or downtime</li>
                        <li>Personal injury or emotional distress</li>
                    </ul>
                    <p class="text-gray-300 text-sm">
                        Our total liability for any claims arising from or related to these Terms or our Services shall not exceed the amount you paid us in the 12 months preceding the event giving rise to liability, or ₹10,000, whichever is less.
                    </p>
                </div>
            </div>

            <!-- Section 12: Indemnification -->
            <div id="indemnification" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">12. Indemnification</h2>
                <p class="text-gray-300 mb-4">
                    You agree to indemnify, defend, and hold harmless StudAI Career, its officers, directors, employees, and agents from and against any claims, damages, losses, liabilities, costs, and expenses (including reasonable attorneys' fees) arising from or related to:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Your use of our Services</li>
                    <li>Your violation of these Terms</li>
                    <li>Your User Content or its use by us or our partners</li>
                    <li>Your violation of any rights of a third party</li>
                    <li>Your violation of applicable laws or regulations</li>
                </ul>
            </div>

            <!-- Section 13: Termination -->
            <div id="termination" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">13. Termination</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">13.1 Termination by You</h3>
                <p class="text-gray-300 mb-4">You may terminate your account at any time by:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Using the account deletion feature in your settings</li>
                    <li>Contacting our support team</li>
                    <li>Following the cancellation process for paid subscriptions</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">13.2 Termination by Us</h3>
                <p class="text-gray-300 mb-4">We may suspend or terminate your account if you:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Violate these Terms or our policies</li>
                    <li>Engage in fraudulent or illegal activities</li>
                    <li>Fail to pay applicable fees</li>
                    <li>Remain inactive for an extended period</li>
                    <li>Pose a security risk to our platform</li>
                </ul>

                <h3 class="text-2xl font-semibold text-white mb-4">13.3 Effect of Termination</h3>
                <p class="text-gray-300 mb-4">Upon termination:</p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Your access to the Services will be immediately suspended</li>
                    <li>Your account data may be retained for legal and operational purposes</li>
                    <li>Outstanding payment obligations remain in effect</li>
                    <li>Certain provisions of these Terms will survive termination</li>
                </ul>
            </div>

            <!-- Section 14: Dispute Resolution -->
            <div id="dispute-resolution" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">14. Dispute Resolution</h2>
                
                <h3 class="text-2xl font-semibold text-white mb-4">14.1 Informal Resolution</h3>
                <p class="text-gray-300 mb-4">
                    Before initiating formal proceedings, we encourage you to contact us directly to resolve any disputes. Please email us at legal@studai.careers with a detailed description of the issue.
                </p>

                <h3 class="text-2xl font-semibold text-white mb-4">14.2 Arbitration</h3>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6 mb-6">
                    <p class="text-gray-300 mb-4">
                        Any disputes that cannot be resolved informally shall be resolved through binding arbitration in accordance with the Arbitration and Conciliation Act, 2015 of India.
                    </p>
                    <ul class="list-disc list-inside text-gray-300 text-sm space-y-2">
                        <li>Arbitration will be conducted in Bengaluru, Karnataka, India</li>
                        <li>The arbitration will be conducted in English</li>
                        <li>Each party will bear their own costs unless otherwise determined by the arbitrator</li>
                        <li>The arbitrator's decision will be final and binding</li>
                    </ul>
                </div>

                <h3 class="text-2xl font-semibold text-white mb-4">14.3 Class Action Waiver</h3>
                <p class="text-gray-300 mb-4">
                    You agree that disputes will be resolved on an individual basis and waive any right to participate in class actions or representative proceedings.
                </p>
            </div>

            <!-- Section 15: Governing Law -->
            <div id="governing-law" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">15. Governing Law</h2>
                <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-slate-800 p-6">
                    <p class="text-gray-300 mb-4">
                        These Terms and your use of our Services are governed by and construed in accordance with the laws of India, without regard to conflict of law principles.
                    </p>
                    <p class="text-gray-300 text-sm">
                        The courts of Bengaluru, Karnataka, India shall have exclusive jurisdiction over any disputes arising from or related to these Terms or our Services, subject to the arbitration provisions above.
                    </p>
                </div>
            </div>

            <!-- Section 16: Changes to Terms -->
            <div id="changes-to-terms" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">16. Changes to These Terms</h2>
                <p class="text-gray-300 mb-4">
                    We may update these Terms from time to time to reflect changes in our services, legal requirements, or business practices. When we make material changes, we will:
                </p>
                <ul class="list-disc list-inside text-gray-300 mb-6 space-y-2">
                    <li>Update the "Last Updated" date at the top of these Terms</li>
                    <li>Notify you via email or prominent notice on our platform</li>
                    <li>Provide at least 30 days notice for material changes</li>
                    <li>Obtain consent where required by applicable law</li>
                </ul>
                <p class="text-gray-300 mb-4">
                    Your continued use of our Services after any changes constitutes acceptance of the updated Terms.
                </p>
            </div>

            <!-- Section 17: Contact Information -->
            <div id="contact-information" class="mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">17. Contact Information</h2>
                <div class="bg-gradient-to-br from-blue-500/10 to-purple-500/10 rounded-3xl border border-blue-500/20 p-8">
                    <p class="text-gray-300 mb-6">
                        If you have any questions about these Terms, please contact us:
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-3">Legal Department</h3>
                            <div class="space-y-2 text-gray-300">
                                <p>Email: legal@studai.careers</p>
                                <p>Phone: +91-80-4567-8900</p>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-3">Registered Office</h3>
                            <div class="text-gray-300">
                                <p>StudAI Career Private Limited</p>
                                <p>WeWork Prestige Atlanta</p>
                                <p>80 Feet Road, Koramangala 4th Block</p>
                                <p>Bengaluru, Karnataka 560034</p>
                                <p>India</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t border-blue-500/20">
                        <p class="text-sm text-gray-400">
                            <strong>Company Registration:</strong> CIN L72900KA2024PTC180234 | <strong>GST:</strong> 29AABCS1234C1Z5
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
    'name' => 'Terms and Conditions - StudAI Career',
    'description' => 'Terms and Conditions governing the use of StudAI Career services and platform',
    'url' => route('terms'),
    'lastReviewed' => '2024-12-01',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'StudAI Career',
        'url' => url('/'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'WeWork Prestige Atlanta, 80 Feet Road, Koramangala 4th Block',
            'addressLocality' => 'Bengaluru',
            'addressRegion' => 'Karnataka',
            'postalCode' => '560034',
            'addressCountry' => 'IN'
        ]
    ],
    'mainEntity' => [
        '@type' => 'TermsOfService',
        'name' => 'StudAI Career Terms and Conditions',
        'text' => 'Terms and Conditions governing the use of StudAI Career platform and services'
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