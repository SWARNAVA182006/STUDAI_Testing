<x-layouts.marketing>
    {{-- Hero Section --}}
    <section class="relative overflow-hidden">
        <div class="orb orb-indigo w-80 h-80 -top-32 -left-10"></div>
        <div class="orb orb-pink w-72 h-72 -bottom-24 right-10"></div>
        <div class="orb orb-cyan w-64 h-64 top-20 right-1/4"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                <div class="space-y-8 relative z-10">
                    <span class="inline-flex items-center rounded-full border border-slate-700/70 bg-slate-900/70 px-3 py-1 text-[0.7rem] font-medium uppercase tracking-[0.2em] text-slate-300 shadow-[0_0_0_1px_rgba(15,23,42,0.9)]">
                        <span class="mr-1.5 flex h-1.5 w-1.5 rounded-full bg-gradient-to-tr from-indigo-400 via-fuchsia-400 to-cyan-300"></span>
                        AI CAREER COPILOT
                    </span>

                    <div class="space-y-3">
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-semibold tracking-tight leading-tight">
                            <span class="block gradient-text">Land your next role</span>
                            <span class="block text-slate-100/90">with an AI career strategist on your side.</span>
                        </h1>
                        <p class="text-base sm:text-lg text-slate-300/90 max-w-xl">
                            StudAI Career analyzes your skills, experience, and goals to match you with the right roles, rewrite your resume, and coach you through every interview—end to end.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-cyan-400 px-6 py-2.5 text-sm font-semibold text-slate-950 shadow-[0_18px_45px_rgba(15,23,42,0.95)] hover:brightness-110 hover:-translate-y-0.5 transition">
                            <span>Start free in 2 minutes</span>
                            <span class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-950/80">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 4H16V14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5 15L15.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </a>
                        <a href="{{ route('features') }}" class="inline-flex items-center text-sm font-medium text-slate-200 hover:text-white">
                            <span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-600/70 bg-slate-900/70">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.5 10H15.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 4.5L15.5 10L10 15.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            Explore how it works
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 pt-3 text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 10L8.5 13.5L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>No credit card required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 10L8.5 13.5L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>Free 14-day trial</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 10L8.5 13.5L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>Cancel anytime</span>
                        </div>
                    </div>
                </div>

                <div class="relative lg:mt-0 z-10">
                    <div class="glass-panel rounded-2xl p-6 sm:p-8 lg:p-10 shadow-[0_30px_60px_rgba(0,0,0,0.5)] hover:shadow-[0_35px_70px_rgba(0,0,0,0.6)] transition-shadow">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-fuchsia-500">
                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 2C5.6 2 2 5.6 2 10C2 14.4 5.6 18 10 18C14.4 18 18 14.4 18 10C18 5.6 14.4 2 10 2ZM10 5C11.1 5 12 5.9 12 7C12 8.1 11.1 9 10 9C8.9 9 8 8.1 8 7C8 5.9 8.9 5 10 5ZM10 15.2C8.5 15.2 7.2 14.5 6.4 13.4C6.4 12 9.4 11.2 10 11.2C10.6 11.2 13.6 12 13.6 13.4C12.8 14.5 11.5 15.2 10 15.2Z" fill="currentColor"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wider uppercase text-slate-400">Career Snapshot</p>
                                <p class="text-sm font-semibold text-slate-100">Your AI-powered profile</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm text-slate-300">
                            <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                <span class="text-slate-400">Match score</span>
                                <span class="font-semibold text-emerald-300">89%</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                <span class="text-slate-400">Active applications</span>
                                <span class="font-semibold text-slate-100">12</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-700/50">
                                <span class="text-slate-400">Interview prep sessions</span>
                                <span class="font-semibold text-slate-100">5</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-slate-400">Resume revisions</span>
                                <span class="font-semibold text-slate-100">3</span>
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-slate-700/50">
                            <p class="text-[0.7rem] text-slate-400 text-center">
                                Next action: <span class="font-semibold text-slate-200">Review interview tips for Amazon PM role</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="relative py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-14 space-y-3">
                <span class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-400 font-medium">KEY FEATURES</span>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold tracking-tight">
                    <span class="gradient-text">Everything you need</span>
                    <span class="block text-slate-100/90 mt-1">to accelerate your career</span>
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                {{-- Job canvas --}}
                <article class="card-soft group p-5 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500/20 to-indigo-600/20 text-indigo-300 shadow-lg shadow-indigo-500/10">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5H17M3 10H17M3 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-50 text-base mb-1">Job canvas</h3>
                            <p class="text-xs text-slate-400 leading-relaxed">Track multiple applications, match scores, and interview stages in one dashboard.</p>
                        </div>
                    </div>
                </article>

                {{-- Resume stack --}}
                <article class="card-soft group p-5 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-fuchsia-500/20 to-fuchsia-600/20 text-fuchsia-300 shadow-lg shadow-fuchsia-500/10">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 8H14M6 11H14M6 14H10M9 2C7.34 2 6 3.34 6 5V17C6 17.55 6.45 18 7 18H13C13.55 18 14 17.55 14 17V5C14 3.34 12.66 2 11 2H9Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-50 text-base mb-1">Resume stack</h3>
                            <p class="text-xs text-slate-400 leading-relaxed">Rewrite your resume for each role, optimized by AI using insights from job descriptions.</p>
                        </div>
                    </div>
                </article>

                {{-- Interview lab --}}
                <article class="card-soft group p-5 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500/20 to-cyan-600/20 text-cyan-300 shadow-lg shadow-cyan-500/10">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 2C5.6 2 2 5.6 2 10C2 14.4 5.6 18 10 18C14.4 18 18 14.4 18 10C18 5.6 14.4 2 10 2ZM10 5C11.1 5 12 5.9 12 7C12 8.1 11.1 9 10 9C8.9 9 8 8.1 8 7C8 5.9 8.9 5 10 5ZM10 15.2C8.5 15.2 7.2 14.5 6.4 13.4C6.4 12 9.4 11.2 10 11.2C10.6 11.2 13.6 12 13.6 13.4C12.8 14.5 11.5 15.2 10 15.2Z" stroke="currentColor" stroke-width="0.8" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-50 text-base mb-1">Interview lab</h3>
                            <p class="text-xs text-slate-400 leading-relaxed">Practice mock interviews with AI feedback, question prompts, and personalized tips.</p>
                        </div>
                    </div>
                </article>

                {{-- Offer desk --}}
                <article class="card-soft group p-5 hover:scale-105 transition-transform">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 text-emerald-300 shadow-lg shadow-emerald-500/10">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17 8L10 2L3 8V17C3 17.5304 3.21071 18.0391 3.58579 18.4142C3.96086 18.7893 4.46957 19 5 19H15C15.5304 19 16.0391 18.7893 16.4142 18.4142C16.7893 18.0391 17 17.5304 17 17V8Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 19V11H13V19" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-50 text-base mb-1">Offer desk</h3>
                            <p class="text-xs text-slate-400 leading-relaxed">Negotiate confidently with AI-driven compensation benchmarks and talking points.</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="relative py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center space-y-6">
            <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-100">
                Ready to level up your career search?
            </h2>
            <p class="text-sm text-slate-300/90">Join thousands of job seekers using AI to land better roles faster.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 via-fuchsia-500 to-cyan-400 px-7 py-3 text-sm font-semibold text-slate-950 shadow-[0_18px_45px_rgba(15,23,42,0.95)] hover:brightness-110 hover:-translate-y-0.5 transition">
                <span>Get started for free</span>
            </a>
        </div>
    </section>
</x-layouts.marketing>
