@props([
    'title' => 'Ready to Find Your Dream Job?',
    'subtitle' => 'Join thousands of successful job seekers using StudAI Career',
    'buttonText' => 'Get Started Free',
    'buttonUrl' => '/register',
    'secondaryButtonText' => null,
    'secondaryButtonUrl' => null,
    'backgroundColor' => 'gradient', // gradient, pink, white
    'showNewsletter' => false
])

<section class="py-20 
    @if($backgroundColor === 'gradient') bg-gradient-to-r from-pink-600 via-pink-500 to-purple-600
    @elseif($backgroundColor === 'pink') bg-pink-600
    @else bg-white @endif
    relative overflow-hidden">
    
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- Content --}}
        <div data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold {{ $backgroundColor === 'white' ? 'text-gray-900' : 'text-white' }} mb-6">
                {{ $title }}
            </h2>
            
            <p class="text-xl {{ $backgroundColor === 'white' ? 'text-gray-600' : 'text-white/90' }} max-w-3xl mx-auto mb-10">
                {{ $subtitle }}
            </p>

            @if($showNewsletter)
            {{-- Newsletter Signup --}}
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="max-w-md mx-auto mb-8" x-data="{ email: '', loading: false }">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="email" 
                           name="email"
                           x-model="email"
                           placeholder="Enter your email address"
                           required
                           class="flex-1 px-6 py-4 rounded-full border-2 border-white/20 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:outline-none focus:border-white transition">
                    <button type="submit" 
                            :disabled="loading"
                            @click="loading = true"
                            class="px-8 py-4 bg-white text-pink-600 font-semibold rounded-full hover:shadow-xl transition transform hover:scale-105 disabled:opacity-50">
                        <span x-show="!loading">Subscribe</span>
                        <span x-show="loading" class="flex items-center">
                            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Subscribing...
                        </span>
                    </button>
                </div>
                <p class="mt-3 text-sm {{ $backgroundColor === 'white' ? 'text-gray-600' : 'text-white/80' }}">
                    Get weekly job alerts and career tips. Unsubscribe anytime.
                </p>
            </form>
            @else
            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ $buttonUrl }}" 
                   class="inline-flex items-center px-8 py-4 {{ $backgroundColor === 'white' ? 'bg-gradient-to-r from-pink-600 to-pink-500 text-white' : 'bg-white text-pink-600' }} font-semibold rounded-full hover:shadow-2xl transition transform hover:scale-105">
                    {{ $buttonText }}
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>

                @if($secondaryButtonText)
                <a href="{{ $secondaryButtonUrl }}" 
                   class="inline-flex items-center px-8 py-4 {{ $backgroundColor === 'white' ? 'bg-gray-100 text-gray-800 hover:bg-gray-200' : 'bg-white/10 backdrop-blur-sm text-white border-2 border-white/30 hover:bg-white/20' }} font-semibold rounded-full transition">
                    {{ $secondaryButtonText }}
                </a>
                @endif
            </div>
            @endif

            {{-- Trust Indicators --}}
            <div class="mt-12 flex flex-col sm:flex-row justify-center items-center gap-8 {{ $backgroundColor === 'white' ? 'text-gray-600' : 'text-white/80' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">Free to start</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">No credit card required</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium">Cancel anytime</span>
                </div>
            </div>
        </div>
    </div>
</section>
