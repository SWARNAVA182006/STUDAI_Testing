/**
 * PWA Installation and Service Worker Management
 */

class PWAManager {
  constructor() {
    this.deferredPrompt = null;
    this.swRegistration = null;
    this.init();
  }

  /**
   * Initialize PWA features
   */
  async init() {
    // Register service worker
    if ('serviceWorker' in navigator) {
      try {
        this.swRegistration = await navigator.serviceWorker.register('/service-worker.js', {
          scope: '/',
        });
        
        console.log('[PWA] Service Worker registered:', this.swRegistration);

        // Check for updates
        this.swRegistration.addEventListener('updatefound', () => {
          this.handleUpdate();
        });

        // Handle controller change
        navigator.serviceWorker.addEventListener('controllerchange', () => {
          console.log('[PWA] Controller changed, reloading page');
          window.location.reload();
        });

      } catch (error) {
        console.error('[PWA] Service Worker registration failed:', error);
      }
    }

    // Handle install prompt
    window.addEventListener('beforeinstallprompt', (event) => {
      event.preventDefault();
      this.deferredPrompt = event;
      this.showInstallButton();
      console.log('[PWA] Install prompt ready');
    });

    // Handle app installed
    window.addEventListener('appinstalled', () => {
      console.log('[PWA] App installed');
      this.hideInstallButton();
      this.trackEvent('pwa_installed');
    });

    // Request notification permission
    this.requestNotificationPermission();

    // Check if running as PWA
    if (this.isRunningAsPWA()) {
      console.log('[PWA] Running as installed app');
      this.trackEvent('pwa_launch');
    }
  }

  /**
   * Handle service worker update
   */
  handleUpdate() {
    const newWorker = this.swRegistration.installing;
    
    console.log('[PWA] New version available');

    newWorker.addEventListener('statechange', () => {
      if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
        this.showUpdateNotification();
      }
    });
  }

  /**
   * Show update notification
   */
  showUpdateNotification() {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-4 right-4 bg-blue-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-sm';
    notification.innerHTML = `
      <div class="flex items-center justify-between">
        <div class="mr-4">
          <p class="font-semibold">New version available!</p>
          <p class="text-sm opacity-90">Click to update</p>
        </div>
        <button id="update-btn" class="bg-white text-blue-500 px-4 py-2 rounded font-semibold hover:bg-blue-50">
          Update
        </button>
      </div>
    `;
    
    document.body.appendChild(notification);

    document.getElementById('update-btn').addEventListener('click', () => {
      notification.remove();
      this.swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
    });
  }

  /**
   * Show install button
   */
  showInstallButton() {
    const installButton = document.getElementById('pwa-install-btn');
    
    if (installButton) {
      installButton.style.display = 'block';
      
      installButton.addEventListener('click', () => {
        this.promptInstall();
      });
    }
  }

  /**
   * Hide install button
   */
  hideInstallButton() {
    const installButton = document.getElementById('pwa-install-btn');
    
    if (installButton) {
      installButton.style.display = 'none';
    }
  }

  /**
   * Prompt user to install app
   */
  async promptInstall() {
    if (!this.deferredPrompt) {
      console.log('[PWA] Install prompt not available');
      return;
    }

    this.deferredPrompt.prompt();

    const { outcome } = await this.deferredPrompt.userChoice;
    
    console.log('[PWA] Install prompt outcome:', outcome);
    this.trackEvent('pwa_install_prompt', { outcome });

    this.deferredPrompt = null;
    this.hideInstallButton();
  }

  /**
   * Request notification permission
   */
  async requestNotificationPermission() {
    if (!('Notification' in window)) {
      console.log('[PWA] Notifications not supported');
      return;
    }

    if (Notification.permission === 'default') {
      // Don't request immediately, wait for user interaction
      this.showNotificationPrompt();
    } else if (Notification.permission === 'granted') {
      await this.subscribeToPush();
    }
  }

  /**
   * Show notification permission prompt
   */
  showNotificationPrompt() {
    const prompt = document.getElementById('notification-prompt');
    
    if (prompt) {
      prompt.style.display = 'block';
      
      const enableBtn = prompt.querySelector('[data-action="enable"]');
      const dismissBtn = prompt.querySelector('[data-action="dismiss"]');
      
      if (enableBtn) {
        enableBtn.addEventListener('click', async () => {
          const permission = await Notification.requestPermission();
          
          if (permission === 'granted') {
            await this.subscribeToPush();
            this.trackEvent('notifications_enabled');
          }
          
          prompt.style.display = 'none';
        });
      }
      
      if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
          prompt.style.display = 'none';
          this.trackEvent('notifications_dismissed');
        });
      }
    }
  }

  /**
   * Subscribe to push notifications
   */
  async subscribeToPush() {
    try {
      const vapidPublicKey = await this.getVapidPublicKey();
      
      const subscription = await this.swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey),
      });

      console.log('[PWA] Push subscription:', subscription);

      // Send subscription to server
      await this.sendSubscriptionToServer(subscription);

    } catch (error) {
      console.error('[PWA] Failed to subscribe to push:', error);
    }
  }

  /**
   * Get VAPID public key from server
   */
  async getVapidPublicKey() {
    const response = await fetch('/api/push/vapid-public-key');
    const data = await response.json();
    return data.publicKey;
  }

  /**
   * Send subscription to server
   */
  async sendSubscriptionToServer(subscription) {
    await fetch('/api/push/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(subscription),
    });
  }

  /**
   * Convert VAPID key
   */
  urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
      .replace(/\-/g, '+')
      .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
  }

  /**
   * Check if running as PWA
   */
  isRunningAsPWA() {
    return (
      window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true
    );
  }

  /**
   * Track analytics event
   */
  trackEvent(eventName, data = {}) {
    if (window.gtag) {
      window.gtag('event', eventName, data);
    }
    
    console.log('[PWA] Event:', eventName, data);
  }

  /**
   * Share content (Web Share API)
   */
  async share(data) {
    if (!navigator.share) {
      console.log('[PWA] Web Share API not supported');
      return false;
    }

    try {
      await navigator.share(data);
      this.trackEvent('share_success', { title: data.title });
      return true;
    } catch (error) {
      if (error.name !== 'AbortError') {
        console.error('[PWA] Share failed:', error);
      }
      return false;
    }
  }

  /**
   * Add to home screen (iOS)
   */
  showIOSInstallInstructions() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isInStandaloneMode = window.navigator.standalone === true;

    if (isIOS && !isInStandaloneMode) {
      const modal = document.getElementById('ios-install-modal');
      if (modal) {
        modal.style.display = 'block';
      }
    }
  }
}

// Initialize PWA manager
const pwaManager = new PWAManager();

// Export for global access
window.pwaManager = pwaManager;

// Offline status indicator
window.addEventListener('online', () => {
  console.log('[PWA] Back online');
  document.body.classList.remove('offline');
  
  const indicator = document.getElementById('offline-indicator');
  if (indicator) {
    indicator.style.display = 'none';
  }
});

window.addEventListener('offline', () => {
  console.log('[PWA] Offline');
  document.body.classList.add('offline');
  
  const indicator = document.getElementById('offline-indicator');
  if (indicator) {
    indicator.style.display = 'block';
  }
});
