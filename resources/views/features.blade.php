<x-layouts.marketing>
    {{-- Hero Section --}}
    <section class="relative overflow-hidden pt-20 pb-16 lg:pt-32 lg:pb-24">
        <div class="orb orb-indigo w-96 h-96 -top-20 -right-20 opacity-40"></div>
        <div class="orb orb-fuchsia w-64 h-64 bottom-0 left-0 opacity-30"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <span class="inline-flex items-center rounded-full border border-slate-700/70 bg-slate-900/70 px-3 py-1 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-slate-300 shadow-[0_0_0_1px_rgba(15,23,42,0.9)] mb-6">
                <span class="mr-1.5 flex h-1.5 w-1.5 rounded-full bg-gradient-to-tr from-indigo-400 via-fuchsia-400 to-cyan-300"></span>
                Platform Features
            </span>
            
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight mb-6">
                <span class="block text-slate-100">Your complete</span>
                <span class="block gradient-text">career command center</span>
            </h1>
            
            <p class="text-lg text-slate-300/90 max-w-2xl mx-auto leading-relaxed">
                StudAI Career replaces fragmented tools with a unified, AI-powered workflow designed to get you hired faster and at a higher salary.
            </p>
        </div>
    </section>

    {{-- Features Grid --}}
    <section class="relative py-16 lg:py-24 bg-slate-950/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                
                {{-- Smart Job Matching --}}
                <div class="glass-panel p-8 rounded-2xl group hover:bg-slate-900/80 transition-colors">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-500/20 to-indigo-600/20 flex items-center justify-center text-indigo-400 mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-100 mb-3">Smart Job Matching</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Our AI analyzes your skills, experience, and preferences to recommend jobs where you're most likely to succeed, filtering out noise.
                    </p>
                </div>

                {{-- Resume Optimization --}}
                <div class="glass-panel p-8 rounded-2xl group hover:bg-slate-900/80 transition-colors">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-fuchsia-500/20 to-fuchsia-600/20 flex items-center justify-center text-fuchsia-400 mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-100 mb-3">Resume Optimization</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Get instant feedback on your resume. Our AI suggests keywords and formatting changes to beat ATS filters and impress recruiters.
                    </p>
                </div>

                {{-- Interview Simulator --}}
                <div class="glass-panel p-8 rounded-2xl group hover:bg-slate-900/80 transition-colors">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-cyan-500/20 to-cyan-600/20 flex items-center justify-center text-cyan-400 mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-100 mb-3">Interview Simulator</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Practice with realistic AI interviewers. Receive detailed feedback on your answers, tone, and body language to build confidence.
                    </p>
                </div>

                {{-- Career Pathing --}}
                <div class="glass-panel p-8 rounded-2xl group hover:bg-slate-900/80 transition-colors">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 flex items-center justify-center text-emerald-400 mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-100 mb-3">Career Pathing</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Visualize your potential career trajectory. See what skills you need to acquire to reach your dream role in 3, 5, or 10 years.
                    </p>
                </div>

                {{-- Salary Negotiation --}}
                <div class="glass-panel p-8 rounded-2xl group hover:bg-slate-900/80 transition-colors">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-amber-500/20 to-amber-600/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-100 mb-3">Salary Negotiation</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Don't leave money on the table. Get real-time market data and AI-generated scripts to negotiate the best possible offer.
                    </p>
                </div>

                {{-- Skill Gap Analysis --}}
                <div class="glass-panel p-8 rounded-2xl group hover:bg-slate-900/80 transition-colors">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-rose-500/20 to-rose-600/20 flex items-center justify-center text-rose-400 mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-100 mb-3">Skill Gap Analysis</h3>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Identify the exact skills you're missing for your target roles and get personalized learning resources to bridge the gap.
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="relative py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950 to-indigo-950/20 pointer-events-none"></div>
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-100 mb-6">
                Ready to transform your career?
            </h2>
            <p class="text-lg text-slate-300/90 mb-10">
                Join thousands of professionals who are landing their dream jobs faster with StudAI Career.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-cyan-400 px-8 py-3.5 text-sm font-semibold text-slate-950 shadow-[0_18px_45px_rgba(15,23,42,0.95)] hover:brightness-110 hover:-translate-y-0.5 transition">
                    Get Started for Free
                </a>
                <a href="{{ route('pricing') }}" class="w-full sm:w-auto inline-flex items-center justify-center rounded-full border border-slate-700 bg-slate-900/50 px-8 py-3.5 text-sm font-semibold text-slate-200 hover:bg-slate-800 transition">
                    View Pricing
                </a>
            </div>
        </div>
    </section>
                    </dt>
                    <dd class="mt-2 ml-9 text-base text-gray-500">
                        Visualize your career trajectory. See what skills you need to acquire to reach your next big role.
                    </dd>
                </div>
                 <div class="relative">
                    <dt>
                        <svg class="absolute h-6 w-6 text-[#1A73E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="ml-9 text-lg leading-6 font-medium text-gray-900">Salary Negotiation</p>
                    </dt>
                    <dd class="mt-2 ml-9 text-base text-gray-500">
                        Know your worth. Get real-time salary data and negotiation scripts tailored to your offer.
                    </dd>
                </div>
                 <div class="relative">
                    <dt>
                        <svg class="absolute h-6 w-6 text-[#1A73E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <p class="ml-9 text-lg leading-6 font-medium text-gray-900">Company Insights</p>
                    </dt>
                    <dd class="mt-2 ml-9 text-base text-gray-500">
                        Get the inside scoop on company culture, interview processes, and employee satisfaction before you apply.
                    </dd>
                </div>
                 <div class="relative">
                    <dt>
                        <svg class="absolute h-6 w-6 text-[#1A73E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="ml-9 text-lg leading-6 font-medium text-gray-900">Job Alerts</p>
                    </dt>
                    <dd class="mt-2 ml-9 text-base text-gray-500">
                        Never miss an opportunity. Set up custom alerts and get notified the moment a matching job is posted.
                    </dd>
                </div>
                 <div class="relative">
                    <dt>
                        <svg class="absolute h-6 w-6 text-[#1A73E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p class="ml-9 text-lg leading-6 font-medium text-gray-900">Skill Assessments</p>
                    </dt>
                    <dd class="mt-2 ml-9 text-base text-gray-500">
                        Validate your skills with our built-in assessments and earn badges to showcase your expertise to employers.
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</x-layouts.marketing>
