<div x-data="installPromptHandler()"
     x-show="showPrompt && !@js($isDismissed)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     x-cloak
     class="fixed bottom-20 inset-x-4 z-40">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-4 border border-gray-100 dark:border-gray-700">
        <div class="flex items-start gap-4">
            <!-- App Icon -->
            <div class="flex-shrink-0">
                <img src="/images/icons/icon-72x72.png" 
                     alt="StudAI Career" 
                     class="w-14 h-14 rounded-xl shadow-md"
                     onerror="this.style.display='none'">
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-gray-900 dark:text-white text-sm mb-1">
                    Install StudAI Career
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Add to your home screen for a faster, app-like experience with offline access.
                </p>
                
                <!-- Buttons -->
                <div class="flex gap-2">
                    <button @click="install()"
                            class="flex-1 py-2 px-4 bg-gradient-to-r from-pink-500 to-purple-600 text-white text-sm font-medium rounded-lg hover:opacity-90 transition">
                        Install
                    </button>
                    <button @click="dismissPrompt(); $wire.dismiss()"
                            class="py-2 px-4 text-gray-500 dark:text-gray-400 text-sm font-medium hover:text-gray-700 dark:hover:text-gray-300 transition">
                        Not now
                    </button>
                </div>
            </div>
            
            <!-- Close Button -->
            <button @click="dismissPrompt(); $wire.dismiss()" 
                    class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- iOS Instructions (shown on iOS devices) -->
        <div x-show="isIOS" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                To install on iOS:
            </p>
            <ol class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <li class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-gray-100 dark:bg-gray-700 rounded text-center text-[10px] font-bold">1</span>
                    Tap the Share button
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-gray-100 dark:bg-gray-700 rounded text-center text-[10px] font-bold">2</span>
                    Select "Add to Home Screen"
                </li>
            </ol>
        </div>
    </div>
</div>

<script>
function installPromptHandler() {
    return {
        showPrompt: false,
        deferredPrompt: null,
        isIOS: false,
        
        init() {
            // Check if already installed
            if (this.isAppInstalled()) {
                return;
            }
            
            // Check if dismissed recently
            if (this.isDismissedRecently()) {
                return;
            }
            
            // Check for iOS
            this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            
            if (this.isIOS) {
                // Show iOS instructions after a delay
                setTimeout(() => {
                    this.showPrompt = true;
                }, 3000);
                return;
            }
            
            // Listen for beforeinstallprompt event
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                
                // Show prompt after a delay
                setTimeout(() => {
                    this.showPrompt = true;
                }, 3000);
            });
            
            // Listen for app installed event
            window.addEventListener('appinstalled', () => {
                console.log('PWA was installed');
                this.showPrompt = false;
                this.deferredPrompt = null;
            });
        },
        
        isAppInstalled() {
            // Check if running as PWA
            if (window.matchMedia('(display-mode: standalone)').matches) {
                return true;
            }
            if (window.navigator.standalone === true) {
                return true;
            }
            return false;
        },
        
        isDismissedRecently() {
            const dismissedAt = localStorage.getItem('install-prompt-dismissed');
            if (!dismissedAt) return false;
            
            const dismissedDate = new Date(dismissedAt);
            const daysSinceDismissed = (Date.now() - dismissedDate.getTime()) / (1000 * 60 * 60 * 24);
            
            // Show again after 7 days
            return daysSinceDismissed < 7;
        },
        
        async install() {
            if (!this.deferredPrompt) {
                if (this.isIOS) {
                    // Can't programmatically install on iOS, just show instructions
                    return;
                }
                return;
            }
            
            try {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                
                console.log(`User ${outcome} the install prompt`);
                
                if (outcome === 'accepted') {
                    this.showPrompt = false;
                }
            } catch (error) {
                console.error('Install failed:', error);
            }
            
            this.deferredPrompt = null;
        },
        
        dismissPrompt() {
            this.showPrompt = false;
            localStorage.setItem('install-prompt-dismissed', new Date().toISOString());
        }
    };
}
</script>
