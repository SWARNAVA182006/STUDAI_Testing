<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#1A73E8">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="mobile-web-app-capable" content="yes">

        <title>{{ config('app.name', 'StudAI Career') }}</title>

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json">
        
        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- PWA Install Styles -->
        <style>
            /* Safe area insets for notched devices */
            :root {
                --sat: env(safe-area-inset-top);
                --sar: env(safe-area-inset-right);
                --sab: env(safe-area-inset-bottom);
                --sal: env(safe-area-inset-left);
            }
            
            body {
                padding-top: var(--sat);
                padding-bottom: var(--sab);
                padding-left: var(--sal);
                padding-right: var(--sar);
            }
            
            /* Prevent pull-to-refresh */
            body {
                overscroll-behavior-y: contain;
            }
            
            /* Hide scrollbar on mobile */
            .hide-scrollbar::-webkit-scrollbar {
                display: none;
            }
            .hide-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            
            /* Swipe card styles */
            .swipe-card {
                touch-action: none;
                user-select: none;
                -webkit-user-select: none;
            }
            
            /* PWA Install Banner */
            .pwa-install-banner {
                animation: slideUp 0.3s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        </style>
    </head>
    <body class="h-full bg-gray-100 dark:bg-gray-900 font-sans antialiased">
        <!-- Main Content -->
        <div class="min-h-full flex flex-col">
            {{ $slot }}
        </div>

        <!-- Mobile Bottom Navigation -->
        @auth
        <nav class="fixed bottom-0 inset-x-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-50" 
             style="padding-bottom: var(--sab);">
            <div class="flex justify-around items-center h-16">
                <a href="{{ route('jobs.search') }}" 
                   class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('jobs.*') ? 'text-pink-500' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs mt-1">Jobs</span>
                </a>
                
                <a href="{{ route('mobile.swipe') }}" 
                   class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('mobile.swipe') ? 'text-pink-500' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="text-xs mt-1">Discover</span>
                </a>
                
                <a href="{{ route('applications.index') }}" 
                   class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('applications.*') ? 'text-pink-500' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span class="text-xs mt-1">Applied</span>
                </a>
                
                <a href="{{ route('saved-jobs.index') }}" 
                   class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('saved-jobs.*') ? 'text-pink-500' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                    <span class="text-xs mt-1">Saved</span>
                </a>
                
                <a href="{{ route('profile.show') }}" 
                   class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('profile.*') ? 'text-pink-500' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-xs mt-1">Profile</span>
                </a>
            </div>
        </nav>
        @endauth

        <!-- PWA Install Prompt -->
        <div id="pwa-install-banner" 
             class="pwa-install-banner fixed bottom-20 inset-x-4 bg-gradient-to-r from-pink-500 to-purple-600 rounded-2xl p-4 shadow-2xl z-50 hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="/icons/icon-72x72.png" alt="StudAI Career" class="w-12 h-12 rounded-xl">
                    <div>
                        <h3 class="text-white font-semibold">Install StudAI Career</h3>
                        <p class="text-white/80 text-sm">Get the full app experience</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="pwa-install-dismiss" class="text-white/60 hover:text-white p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button id="pwa-install-button" 
                            class="bg-white text-pink-600 px-4 py-2 rounded-lg font-semibold text-sm hover:bg-pink-50 transition">
                        Install
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div id="toast-container" class="fixed top-4 inset-x-4 z-50 pointer-events-none"></div>

        <!-- Offline Indicator -->
        <div id="offline-indicator" 
             class="fixed top-0 inset-x-0 bg-yellow-500 text-yellow-900 text-center py-2 text-sm font-medium z-50 hidden"
             style="padding-top: calc(var(--sat) + 0.5rem);">
            You're offline. Some features may be limited.
        </div>

        <!-- Scripts -->
        <!-- Offline Storage Manager -->
        <script src="/js/offline-storage.js"></script>
        
        <script>
            // PWA Install Prompt
            let deferredPrompt;
            const installBanner = document.getElementById('pwa-install-banner');
            const installButton = document.getElementById('pwa-install-button');
            const dismissButton = document.getElementById('pwa-install-dismiss');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                
                // Check if user has dismissed before
                if (!localStorage.getItem('pwa-install-dismissed')) {
                    installBanner.classList.remove('hidden');
                }
            });

            installButton?.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log('PWA install outcome:', outcome);
                    deferredPrompt = null;
                    installBanner.classList.add('hidden');
                }
            });

            dismissButton?.addEventListener('click', () => {
                installBanner.classList.add('hidden');
                localStorage.setItem('pwa-install-dismissed', 'true');
            });

            // Offline detection
            const offlineIndicator = document.getElementById('offline-indicator');
            
            window.addEventListener('online', () => {
                offlineIndicator.classList.add('hidden');
            });
            
            window.addEventListener('offline', () => {
                offlineIndicator.classList.remove('hidden');
            });
            
            if (!navigator.onLine) {
                offlineIndicator.classList.remove('hidden');
            }

            // Toast notification helper
            window.showToast = function(message, type = 'info') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                
                const colors = {
                    success: 'bg-green-500',
                    error: 'bg-red-500',
                    warning: 'bg-yellow-500',
                    info: 'bg-blue-500'
                };
                
                toast.className = `${colors[type] || colors.info} text-white px-4 py-3 rounded-lg shadow-lg mb-2 transform transition-all duration-300 translate-y-0 opacity-100 pointer-events-auto`;
                toast.textContent = message;
                
                container.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.add('-translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            };

            // Livewire toast event listener
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('show-toast', ({ message, type }) => {
                    window.showToast(message, type);
                });
            });

            // Register service worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/service-worker.js')
                        .then((registration) => {
                            console.log('Service Worker registered:', registration.scope);
                        })
                        .catch((error) => {
                            console.log('Service Worker registration failed:', error);
                        });
                });
            }
        </script>

        @stack('scripts')
        @livewireScripts
    </body>
</html>
