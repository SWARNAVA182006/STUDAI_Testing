@props([
    'title' => 'Find Your Dream Job with AI',
    'subtitle' => 'Join thousands of job seekers who found success with our AI-powered platform',
    'primaryButtonText' => 'Get Started Free',
    'primaryButtonUrl' => '/register',
    'secondaryButtonText' => 'Watch Demo',
    'secondaryButtonUrl' => '#demo',
    'showStats' => true,
    'backgroundGradient' => true
])

<section class="relative overflow-hidden {{ $backgroundGradient ? 'bg-gradient-to-br from-pink-50 via-white to-purple-50' : 'bg-white' }} py-20 md:py-32">
    {{-- Background Decorations --}}
    @if($backgroundGradient)
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
    </div>
    @endif

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            {{-- Left Content --}}
            <div class="text-center lg:text-left" data-aos="fade-right">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                    {{ $title }}
                </h1>
                
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto lg:mx-0">
                    {{ $subtitle }}
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="{{ $primaryButtonUrl }}" 
                       class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-pink-600 to-pink-500 text-white font-semibold rounded-full hover:shadow-xl transition transform hover:scale-105">
                        {{ $primaryButtonText }}
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    
                    <a href="{{ $secondaryButtonUrl }}" 
                       class="inline-flex items-center justify-center px-8 py-4 bg-white text-gray-800 font-semibold rounded-full border-2 border-gray-300 hover:border-pink-500 hover:shadow-lg transition">
                        <svg class="mr-2 w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        {{ $secondaryButtonText }}
                    </a>
                </div>

                {{-- Trust Indicators --}}
                @if($showStats)
                <div class="mt-12 grid grid-cols-3 gap-6 max-w-md mx-auto lg:mx-0">
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold text-pink-600">50K+</div>
                        <div class="text-sm text-gray-600">Job Seekers</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold text-pink-600">10K+</div>
                        <div class="text-sm text-gray-600">Companies</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold text-pink-600">95%</div>
                        <div class="text-sm text-gray-600">Success Rate</div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Right Content - Visual --}}
            <div class="relative" data-aos="fade-left">
                <div class="relative z-10">
                    {{-- Placeholder for hero image or illustration --}}
                    <div class="aspect-square max-w-lg mx-auto">
                        <div class="w-full h-full bg-gradient-to-br from-pink-100 to-purple-100 rounded-3xl shadow-2xl p-8 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-32 h-32 mx-auto text-pink-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-500">Hero image/illustration placeholder</p>
                            </div>
                        </div>
                    </div>

                    {{-- Floating Cards --}}
                    <div class="hidden lg:block absolute -top-10 -left-10 bg-white rounded-lg shadow-xl p-4 animate-float">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Profile Match</div>
                                <div class="text-xs text-gray-600">92% Compatible</div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden lg:block absolute -bottom-10 -right-10 bg-white rounded-lg shadow-xl p-4 animate-float animation-delay-2000">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900">AI Resume Score</div>
                                <div class="text-xs text-gray-600">Excellent</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    .animate-blob { animation: blob 7s infinite; }
    .animate-float { animation: float 3s ease-in-out infinite; }
    .animation-delay-2000 { animation-delay: 2s; }
    .animation-delay-4000 { animation-delay: 4s; }
</style>
