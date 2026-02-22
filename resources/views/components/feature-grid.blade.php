@props([
    'title' => 'Powerful Features',
    'subtitle' => 'Everything you need to find your dream job',
    'features' => []
])

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                {{ $title }}
            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                {{ $subtitle }}
            </p>
        </div>

        {{-- Features Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($features as $index => $feature)
            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" 
                 data-aos="fade-up" 
                 data-aos-delay="{{ $index * 100 }}">
                {{-- Icon --}}
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    @if(isset($feature['icon']))
                        {!! $feature['icon'] !!}
                    @else
                        <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    @endif
                </div>

                {{-- Content --}}
                <h3 class="text-xl font-semibold text-gray-900 mb-3">
                    {{ $feature['title'] ?? 'Feature Title' }}
                </h3>
                
                <p class="text-gray-600 mb-4">
                    {{ $feature['description'] ?? 'Feature description goes here.' }}
                </p>

                @if(isset($feature['link']))
                <a href="{{ $feature['link'] }}" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                @endif

                {{-- Hover Gradient Border Effect --}}
                <div class="absolute inset-0 bg-gradient-to-r from-pink-500 to-purple-500 rounded-2xl opacity-0 group-hover:opacity-10 transition -z-10"></div>
            </div>
            @empty
            {{-- Default Features --}}
            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" data-aos="fade-up">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">AI-Powered Matching</h3>
                <p class="text-gray-600 mb-4">Our advanced AI algorithms match you with jobs that perfectly fit your skills, experience, and career goals.</p>
                <a href="#" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" data-aos="fade-up" data-aos-delay="100">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Smart Resume Builder</h3>
                <p class="text-gray-600 mb-4">Create ATS-friendly resumes optimized for each job application with our AI-powered resume builder and analyzer.</p>
                <a href="#" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" data-aos="fade-up" data-aos-delay="200">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Interview Preparation</h3>
                <p class="text-gray-600 mb-4">Get ready with AI-powered mock interviews, personalized feedback, and company-specific interview questions.</p>
                <a href="#" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" data-aos="fade-up" data-aos-delay="300">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Job Alerts</h3>
                <p class="text-gray-600 mb-4">Never miss an opportunity with instant notifications for jobs matching your profile and preferences.</p>
                <a href="#" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" data-aos="fade-up" data-aos-delay="400">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Career Analytics</h3>
                <p class="text-gray-600 mb-4">Track your job search progress, application success rates, and get insights to improve your strategy.</p>
                <a href="#" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <div class="group relative bg-white p-8 rounded-2xl border border-gray-200 hover:border-pink-500 hover:shadow-xl transition duration-300" data-aos="fade-up" data-aos-delay="500">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition transform">
                    <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Skill Development</h3>
                <p class="text-gray-600 mb-4">Identify skill gaps and get personalized learning recommendations to boost your career prospects.</p>
                <a href="#" class="inline-flex items-center text-pink-600 hover:text-pink-700 font-medium group-hover:translate-x-2 transition transform">
                    Learn more
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
            @endforelse
        </div>
    </div>
</section>
