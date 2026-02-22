{{-- StudAI Path Marketing Footer --}}
<footer class="bg-gray-50 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
            {{-- Brand --}}
            <div class="col-span-1 md:col-span-1">
                <a href="/" class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#1A73E8] to-purple-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-xl font-semibold text-gray-900">StudAI<span class="text-[#1A73E8]">Path</span></span>
                </a>
                <p class="text-sm text-gray-600 mb-6">
                    AI-powered job discovery and career advancement platform. Land your dream job faster.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="text-gray-400 hover:text-[#1A73E8] transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-[#1A73E8] transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-[#1A73E8] transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Product --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Product</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('features') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Features</a></li>
                    <li><a href="{{ route('pricing') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Pricing</a></li>
                    <li><a href="#" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Job Search</a></li>
                    <li><a href="#" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Resume Builder</a></li>
                    <li><a href="#" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Interview Prep</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Company</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('about') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Contact</a></li>
                    <li><a href="{{ route('employers') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">For Employers</a></li>
                    <li><a href="#" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Careers</a></li>
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Legal</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('privacy') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('refund-policy') }}" class="text-sm text-gray-600 hover:text-[#1A73E8] transition-colors">Refund Policy</a></li>
                </ul>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="border-t border-gray-200 mt-12 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} StudAI Career. All rights reserved.
                </p>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>Made with</span>
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span>in India</span>
                </div>
            </div>
        </div>
    </div>
</footer>
