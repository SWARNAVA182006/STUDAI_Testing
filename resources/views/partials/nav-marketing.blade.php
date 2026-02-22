{{-- StudAI Path Marketing Navigation --}}
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#1A73E8] to-purple-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-semibold text-gray-900">StudAI<span class="text-[#1A73E8]">Path</span></span>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('features') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">Features</a>
                <a href="{{ route('pricing') }}" class="text-sm font-medium {{ request()->routeIs('pricing') ? 'text-[#1A73E8]' : 'text-gray-600 hover:text-gray-900' }} transition-colors">Pricing</a>
                <a href="{{ route('employers') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">For Employers</a>
                <a href="{{ route('about') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">About</a>
            </div>

            {{-- Auth Buttons --}}
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-[#1A73E8] rounded-lg hover:bg-[#1557B0] transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors hidden sm:block">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-[#1A73E8] rounded-lg hover:bg-[#1557B0] transition-colors">
                        Start Free
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
