<!-- Cookie Consent Banner Component -->
<div x-data="cookieConsent()" 
     x-show="!consentGiven" 
     x-cloak
     class="fixed bottom-0 left-0 right-0 z-50 transform transition-transform duration-300"
     :class="showBanner ? 'translate-y-0' : 'translate-y-full'">
    
    <!-- Backdrop Overlay -->
    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>
    
    <!-- Content Container -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl overflow-hidden">
            
            <!-- Simple Mode -->
            <div x-show="!showDetails" class="p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-center gap-6">
                    <!-- Icon and Message -->
                    <div class="flex-1">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-10 h-10 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2">We Value Your Privacy</h3>
                                <p class="text-gray-300 text-sm leading-relaxed">
                                    We use cookies to enhance your experience, analyze site traffic, and personalize content. By clicking "Accept All", you consent to our use of cookies. You can customize your preferences or learn more in our 
                                    <a href="{{ route('privacy') }}" class="text-pink-400 hover:text-pink-300 underline">Privacy Policy</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 md:flex-shrink-0">
                        <button @click="acceptAll()" 
                                class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-pink-500/30 transition-all duration-300">
                            Accept All
                        </button>
                        <button @click="acceptEssential()" 
                                class="px-6 py-3 bg-slate-800 text-white font-semibold rounded-xl hover:bg-slate-700 transition-colors">
                            Essential Only
                        </button>
                        <button @click="showDetails = true" 
                                class="px-6 py-3 border border-slate-600 text-gray-300 font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                            Customize
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detailed Settings Mode -->
            <div x-show="showDetails" class="p-6 md:p-8">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-white mb-2">Cookie Preferences</h3>
                    <p class="text-gray-300 text-sm">
                        Manage your cookie preferences below. Essential cookies are required for the site to function and cannot be disabled.
                    </p>
                </div>

                <!-- Cookie Categories -->
                <div class="space-y-4 mb-6">
                    
                    <!-- Essential Cookies -->
                    <div class="bg-slate-800/50 rounded-2xl border border-slate-700 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-white">Essential Cookies</h4>
                                    <span class="text-xs bg-green-500/20 text-green-300 px-3 py-1 rounded-full font-medium">Always Active</span>
                                </div>
                                <p class="text-sm text-gray-400">
                                    Required for basic site functionality including authentication, security, and navigation. Cannot be disabled.
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <input type="checkbox" checked disabled 
                                       class="w-5 h-5 rounded bg-green-500/20 border-green-500 text-green-500 cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- Performance Cookies -->
                    <div class="bg-slate-800/50 rounded-2xl border border-slate-700 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-white">Performance Cookies</h4>
                                </div>
                                <p class="text-sm text-gray-400 mb-2">
                                    Help us analyze site usage and performance to improve our services. Includes analytics and error tracking.
                                </p>
                                <p class="text-xs text-gray-500">
                                    Examples: Google Analytics, error monitoring, page load metrics
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <label class="relative inline-block w-12 h-6 cursor-pointer">
                                    <input type="checkbox" 
                                           x-model="preferences.performance" 
                                           class="sr-only peer">
                                    <div class="w-12 h-6 bg-slate-700 rounded-full peer peer-checked:bg-blue-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Functional Cookies -->
                    <div class="bg-slate-800/50 rounded-2xl border border-slate-700 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-white">Functional Cookies</h4>
                                </div>
                                <p class="text-sm text-gray-400 mb-2">
                                    Remember your preferences and settings to provide enhanced, personalized features.
                                </p>
                                <p class="text-xs text-gray-500">
                                    Examples: Language preferences, theme settings, saved filters
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <label class="relative inline-block w-12 h-6 cursor-pointer">
                                    <input type="checkbox" 
                                           x-model="preferences.functional" 
                                           class="sr-only peer">
                                    <div class="w-12 h-6 bg-slate-700 rounded-full peer peer-checked:bg-purple-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Marketing Cookies -->
                    <div class="bg-slate-800/50 rounded-2xl border border-slate-700 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-white">Marketing Cookies</h4>
                                </div>
                                <p class="text-sm text-gray-400 mb-2">
                                    Used to deliver relevant ads and track campaign effectiveness. May be set by third-party advertisers.
                                </p>
                                <p class="text-xs text-gray-500">
                                    Examples: LinkedIn Insight, Google Ads, Facebook Pixel
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <label class="relative inline-block w-12 h-6 cursor-pointer">
                                    <input type="checkbox" 
                                           x-model="preferences.marketing" 
                                           class="sr-only peer">
                                    <div class="w-12 h-6 bg-slate-700 rounded-full peer peer-checked:bg-pink-500 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 justify-between items-center pt-4 border-t border-slate-700">
                    <button @click="showDetails = false" 
                            class="text-gray-400 hover:text-white transition-colors text-sm">
                        ← Back to Simple View
                    </button>
                    <div class="flex gap-3">
                        <button @click="acceptSelected()" 
                                class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-pink-500/30 transition-all duration-300">
                            Save Preferences
                        </button>
                        <button @click="acceptAll()" 
                                class="px-6 py-3 border border-slate-600 text-gray-300 font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                            Accept All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cookieConsent() {
    return {
        showBanner: false,
        showDetails: false,
        consentGiven: false,
        preferences: {
            essential: true, // Always true
            performance: true,
            functional: true,
            marketing: false
        },

        init() {
            // Check if consent has been given
            const consent = this.getCookie('cookie_consent');
            if (consent) {
                this.consentGiven = true;
                this.loadPreferences(consent);
            } else {
                // Show banner after a short delay
                setTimeout(() => {
                    this.showBanner = true;
                }, 1000);
            }
        },

        acceptAll() {
            this.preferences = {
                essential: true,
                performance: true,
                functional: true,
                marketing: true
            };
            this.saveConsent();
        },

        acceptEssential() {
            this.preferences = {
                essential: true,
                performance: false,
                functional: false,
                marketing: false
            };
            this.saveConsent();
        },

        acceptSelected() {
            this.preferences.essential = true; // Always true
            this.saveConsent();
        },

        saveConsent() {
            // Save preferences as JSON
            const consentData = JSON.stringify(this.preferences);
            this.setCookie('cookie_consent', consentData, 365);
            
            // Save individual category cookies for easy access
            this.setCookie('cookie_essential', '1', 365);
            this.setCookie('cookie_performance', this.preferences.performance ? '1' : '0', 365);
            this.setCookie('cookie_functional', this.preferences.functional ? '1' : '0', 365);
            this.setCookie('cookie_marketing', this.preferences.marketing ? '1' : '0', 365);

            // Initialize tracking scripts based on preferences
            this.initializeTracking();

            // Hide banner
            this.showBanner = false;
            setTimeout(() => {
                this.consentGiven = true;
            }, 300);

            // Send event to analytics (if performance tracking is enabled)
            if (this.preferences.performance && typeof gtag === 'function') {
                gtag('event', 'cookie_consent', {
                    'event_category': 'consent',
                    'event_label': 'preferences_saved'
                });
            }
        },

        loadPreferences(consentData) {
            try {
                const saved = JSON.parse(consentData);
                this.preferences = { ...this.preferences, ...saved };
                this.initializeTracking();
            } catch (e) {
                console.error('Failed to parse cookie consent:', e);
            }
        },

        initializeTracking() {
            // Initialize Google Analytics if performance cookies are enabled
            if (this.preferences.performance && typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
            }

            // Initialize marketing scripts if marketing cookies are enabled
            if (this.preferences.marketing) {
                // Load marketing pixels (Google Ads, Facebook Pixel, LinkedIn Insight, etc.)
                this.loadMarketingScripts();
            }

            // Initialize functional features if enabled
            if (this.preferences.functional) {
                // Enable personalization features
                this.enablePersonalization();
            }
        },

        loadMarketingScripts() {
            // Placeholder for marketing script initialization
            // Add your marketing pixels here
            console.log('Marketing scripts enabled');
        },

        enablePersonalization() {
            // Placeholder for personalization features
            console.log('Personalization features enabled');
        },

        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },

        setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = `expires=${date.toUTCString()}`;
            document.cookie = `${name}=${value}; ${expires}; path=/; SameSite=Lax`;
        }
    }
}
</script>
@endpush

@push('styles')
<style>
[x-cloak] {
    display: none !important;
}
</style>
@endpush