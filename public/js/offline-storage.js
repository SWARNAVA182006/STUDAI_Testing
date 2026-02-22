/**
 * StudAI Career - Offline Storage Manager
 * Handles IndexedDB operations for offline job saving and resume access
 */

class OfflineStorageManager {
    constructor() {
        this.dbName = 'studai-career-offline';
        this.version = 1;
        this.db = null;
        this.stores = {
            savedJobs: 'saved-jobs',
            viewedJobs: 'viewed-jobs',
            applications: 'draft-applications',
            resumes: 'offline-resumes',
            preferences: 'user-preferences',
            syncQueue: 'sync-queue'
        };
    }

    /**
     * Initialize the database
     */
    async init() {
        return new Promise((resolve, reject) => {
            if (!('indexedDB' in window)) {
                reject(new Error('IndexedDB not supported'));
                return;
            }

            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => {
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('OfflineStorage: Database initialized');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Saved Jobs Store
                if (!db.objectStoreNames.contains(this.stores.savedJobs)) {
                    const savedJobsStore = db.createObjectStore(this.stores.savedJobs, { keyPath: 'id' });
                    savedJobsStore.createIndex('savedAt', 'savedAt', { unique: false });
                    savedJobsStore.createIndex('company', 'company', { unique: false });
                }

                // Viewed Jobs Store (for swipe history)
                if (!db.objectStoreNames.contains(this.stores.viewedJobs)) {
                    const viewedJobsStore = db.createObjectStore(this.stores.viewedJobs, { keyPath: 'id' });
                    viewedJobsStore.createIndex('viewedAt', 'viewedAt', { unique: false });
                    viewedJobsStore.createIndex('action', 'action', { unique: false });
                }

                // Draft Applications Store
                if (!db.objectStoreNames.contains(this.stores.applications)) {
                    const applicationsStore = db.createObjectStore(this.stores.applications, { keyPath: 'id', autoIncrement: true });
                    applicationsStore.createIndex('jobId', 'jobId', { unique: false });
                    applicationsStore.createIndex('createdAt', 'createdAt', { unique: false });
                }

                // Offline Resumes Store
                if (!db.objectStoreNames.contains(this.stores.resumes)) {
                    const resumesStore = db.createObjectStore(this.stores.resumes, { keyPath: 'id' });
                    resumesStore.createIndex('userId', 'userId', { unique: false });
                    resumesStore.createIndex('isPrimary', 'isPrimary', { unique: false });
                }

                // User Preferences Store
                if (!db.objectStoreNames.contains(this.stores.preferences)) {
                    db.createObjectStore(this.stores.preferences, { keyPath: 'key' });
                }

                // Sync Queue Store (for background sync)
                if (!db.objectStoreNames.contains(this.stores.syncQueue)) {
                    const syncStore = db.createObjectStore(this.stores.syncQueue, { keyPath: 'id', autoIncrement: true });
                    syncStore.createIndex('type', 'type', { unique: false });
                    syncStore.createIndex('createdAt', 'createdAt', { unique: false });
                }

                console.log('OfflineStorage: Database schema created');
            };
        });
    }

    /**
     * Save a job for offline access
     */
    async saveJob(job) {
        return this.put(this.stores.savedJobs, {
            ...job,
            savedAt: new Date().toISOString(),
            isSynced: navigator.onLine
        });
    }

    /**
     * Get all saved jobs
     */
    async getSavedJobs() {
        return this.getAll(this.stores.savedJobs);
    }

    /**
     * Remove a saved job
     */
    async removeSavedJob(jobId) {
        return this.delete(this.stores.savedJobs, jobId);
    }

    /**
     * Check if a job is saved
     */
    async isJobSaved(jobId) {
        const job = await this.get(this.stores.savedJobs, jobId);
        return !!job;
    }

    /**
     * Record viewed job action (skip/save/apply)
     */
    async recordJobAction(jobId, action, jobData = null) {
        return this.put(this.stores.viewedJobs, {
            id: jobId,
            action: action,
            viewedAt: new Date().toISOString(),
            jobData: jobData
        });
    }

    /**
     * Get viewed jobs history
     */
    async getViewedJobs() {
        return this.getAll(this.stores.viewedJobs);
    }

    /**
     * Save draft application
     */
    async saveDraftApplication(application) {
        return this.put(this.stores.applications, {
            ...application,
            createdAt: new Date().toISOString(),
            isSynced: false
        });
    }

    /**
     * Get draft applications
     */
    async getDraftApplications() {
        return this.getAll(this.stores.applications);
    }

    /**
     * Save resume for offline access
     */
    async saveResume(resume) {
        return this.put(this.stores.resumes, {
            ...resume,
            savedAt: new Date().toISOString()
        });
    }

    /**
     * Get offline resumes
     */
    async getResumes() {
        return this.getAll(this.stores.resumes);
    }

    /**
     * Get primary resume
     */
    async getPrimaryResume() {
        const resumes = await this.getAllByIndex(this.stores.resumes, 'isPrimary', true);
        return resumes[0] || null;
    }

    /**
     * Save user preference
     */
    async setPreference(key, value) {
        return this.put(this.stores.preferences, { key, value });
    }

    /**
     * Get user preference
     */
    async getPreference(key) {
        const pref = await this.get(this.stores.preferences, key);
        return pref ? pref.value : null;
    }

    /**
     * Add to sync queue for background sync
     */
    async addToSyncQueue(type, data) {
        return this.put(this.stores.syncQueue, {
            type: type,
            data: data,
            createdAt: new Date().toISOString(),
            attempts: 0
        });
    }

    /**
     * Get items from sync queue
     */
    async getSyncQueue() {
        return this.getAll(this.stores.syncQueue);
    }

    /**
     * Remove from sync queue
     */
    async removeFromSyncQueue(id) {
        return this.delete(this.stores.syncQueue, id);
    }

    /**
     * Process sync queue when online
     */
    async processSyncQueue() {
        if (!navigator.onLine) return;

        const queue = await this.getSyncQueue();
        
        for (const item of queue) {
            try {
                await this.syncItem(item);
                await this.removeFromSyncQueue(item.id);
            } catch (error) {
                console.error('Sync failed for item:', item, error);
                // Update attempt count
                item.attempts++;
                item.lastError = error.message;
                await this.put(this.stores.syncQueue, item);
            }
        }
    }

    /**
     * Sync a single item
     */
    async syncItem(item) {
        const endpoints = {
            'save-job': '/api/jobs/save',
            'apply-job': '/api/applications/submit',
            'view-job': '/api/jobs/view'
        };

        const endpoint = endpoints[item.type];
        if (!endpoint) return;

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(item.data)
        });

        if (!response.ok) {
            throw new Error(`Sync failed: ${response.status}`);
        }
    }

    /**
     * Generic put operation
     */
    async put(storeName, data) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(data);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Generic get operation
     */
    async get(storeName, key) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Generic getAll operation
     */
    async getAll(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Generic getAllByIndex operation
     */
    async getAllByIndex(storeName, indexName, value) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(value);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Generic delete operation
     */
    async delete(storeName, key) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Clear all data from a store
     */
    async clearStore(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get storage usage statistics
     */
    async getStorageStats() {
        const stats = {};
        
        for (const [key, storeName] of Object.entries(this.stores)) {
            const items = await this.getAll(storeName);
            stats[key] = {
                count: items.length,
                estimatedSize: new Blob([JSON.stringify(items)]).size
            };
        }

        return stats;
    }
}

// Create global instance
window.offlineStorage = new OfflineStorageManager();

// Initialize on load
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await window.offlineStorage.init();
        console.log('OfflineStorage: Ready');

        // Process sync queue when coming online
        window.addEventListener('online', () => {
            console.log('OfflineStorage: Online - processing sync queue');
            window.offlineStorage.processSyncQueue();
        });

        // Dispatch ready event
        window.dispatchEvent(new CustomEvent('offline-storage-ready'));
    } catch (error) {
        console.error('OfflineStorage: Failed to initialize', error);
    }
});
