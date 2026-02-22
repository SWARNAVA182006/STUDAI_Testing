@extends('layouts.mobile')

@section('title', 'Saved Jobs')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-4"
            style="padding-top: calc(var(--sat, 0) + 1rem);">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Saved Jobs</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Jobs you've saved for later</p>
    </header>

    <!-- Offline Banner -->
    <div x-data="{ offline: !navigator.onLine }"
         x-init="
            window.addEventListener('online', () => offline = false);
            window.addEventListener('offline', () => offline = true);
         "
         x-show="offline"
         x-transition
         class="mx-4 mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
            </svg>
            <span class="text-sm font-medium">You're offline. Showing cached jobs.</span>
        </div>
    </div>

    <!-- Jobs List Container -->
    <div x-data="savedJobsHandler()" x-init="init()" class="px-4 py-4">
        <!-- Loading State -->
        <template x-if="loading">
            <div class="space-y-4">
                @for($i = 0; $i < 3; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 animate-pulse">
                    <div class="flex gap-3">
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="!loading && jobs.length === 0">
            <div class="text-center py-16">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No saved jobs yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Start swiping to save jobs you're interested in</p>
                <a href="{{ route('mobile.swipe') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white font-medium rounded-lg hover:opacity-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Browse Jobs
                </a>
            </div>
        </template>

        <!-- Jobs List -->
        <template x-if="!loading && jobs.length > 0">
            <div class="space-y-3">
                <template x-for="job in jobs" :key="job.id">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                         x-data="{ expanded: false }">
                        <!-- Main Content -->
                        <a :href="'/jobs/' + job.id" class="block p-4">
                            <div class="flex gap-3">
                                <!-- Company Logo -->
                                <div class="flex-shrink-0">
                                    <template x-if="job.company?.logo">
                                        <img :src="job.company.logo" :alt="job.company.name" class="w-12 h-12 rounded-lg object-cover">
                                    </template>
                                    <template x-if="!job.company?.logo">
                                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-pink-400 to-purple-600 flex items-center justify-center text-white font-bold">
                                            <span x-text="(job.company?.name || 'J').charAt(0)"></span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Job Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate" x-text="job.title"></h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="job.company?.name"></p>
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            <span x-text="job.location"></span>
                                        </span>
                                        <template x-if="job.is_remote">
                                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">Remote</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Salary -->
                                <div class="text-right">
                                    <template x-if="job.salary_min && job.salary_max">
                                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            $<span x-text="(job.salary_min / 1000).toFixed(0)">k</span>-<span x-text="(job.salary_max / 1000).toFixed(0)"></span>k
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </a>

                        <!-- Actions -->
                        <div class="flex border-t border-gray-100 dark:border-gray-700">
                            <button @click="applyToJob(job)"
                                    class="flex-1 py-3 text-center text-sm font-medium text-pink-600 dark:text-pink-400 hover:bg-pink-50 dark:hover:bg-pink-900/20 transition">
                                Quick Apply
                            </button>
                            <div class="w-px bg-gray-100 dark:bg-gray-700"></div>
                            <button @click="removeJob(job)"
                                    class="flex-1 py-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                Remove
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

<!-- Quick Apply Component -->
@livewire('mobile.quick-apply')

@push('scripts')
<script>
function savedJobsHandler() {
    return {
        jobs: [],
        loading: true,

        async init() {
            // Wait for offline storage to be ready
            if (!window.offlineStorage?.db) {
                await new Promise(resolve => {
                    window.addEventListener('offline-storage-ready', resolve, { once: true });
                    // Fallback if already initialized
                    setTimeout(resolve, 1000);
                });
            }

            await this.loadJobs();
        },

        async loadJobs() {
            this.loading = true;

            try {
                if (navigator.onLine) {
                    // Fetch from server
                    const response = await fetch('/api/mobile/saved-jobs');
                    if (response.ok) {
                        this.jobs = await response.json();
                        // Cache for offline use
                        if (window.offlineStorage?.db) {
                            for (const job of this.jobs) {
                                await window.offlineStorage.saveJob(job);
                            }
                        }
                    }
                } else {
                    // Load from IndexedDB
                    if (window.offlineStorage?.db) {
                        this.jobs = await window.offlineStorage.getSavedJobs();
                    }
                }
            } catch (error) {
                console.error('Failed to load saved jobs:', error);
                // Try loading from cache
                if (window.offlineStorage?.db) {
                    this.jobs = await window.offlineStorage.getSavedJobs();
                }
            }

            this.loading = false;
        },

        applyToJob(job) {
            Livewire.dispatch('open-quick-apply', { jobId: job.id });
        },

        async removeJob(job) {
            // Remove from local list
            this.jobs = this.jobs.filter(j => j.id !== job.id);

            // Remove from IndexedDB
            if (window.offlineStorage?.db) {
                await window.offlineStorage.removeSavedJob(job.id);
            }

            // Remove from server if online
            if (navigator.onLine) {
                try {
                    await fetch(`/api/jobs/${job.id}/toggle-save`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });
                } catch (error) {
                    console.error('Failed to unsave job on server:', error);
                    // Add to sync queue
                    if (window.offlineStorage?.db) {
                        await window.offlineStorage.addToSyncQueue('unsave-job', { job_id: job.id });
                    }
                }
            }
        }
    };
}
</script>
@endpush
@endsection
