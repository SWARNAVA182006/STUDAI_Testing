<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-pink-900 pb-20"
     x-data="swipeHandler()"
     x-init="init()">
    
    <!-- Header -->
    <header class="px-4 py-4 flex items-center justify-between" style="padding-top: calc(var(--sat, 0) + 1rem);">
        <h1 class="text-xl font-bold text-white">Discover Jobs</h1>
        <button wire:click="$toggle('showFilters')" class="p-2 rounded-full bg-white/10 text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
        </button>
    </header>

    <!-- Stats Bar -->
    <div class="px-4 mb-4 flex items-center justify-center gap-4 text-sm text-white/60">
        <span>{{ $remainingJobs }} jobs remaining</span>
        <span>•</span>
        <span>{{ count($swipeHistory) }} viewed</span>
    </div>

    <!-- Card Stack Container -->
    <div class="relative px-4 flex-1" style="min-height: 70vh;">
        @if($currentJob)
            <!-- Next Cards (stacked behind) -->
            @for($i = min(2, count($jobs) - $currentIndex - 1); $i > 0; $i--)
                <div class="absolute inset-x-4 top-0 rounded-3xl bg-white dark:bg-gray-800 shadow-xl"
                     style="transform: scale({{ 1 - ($i * 0.05) }}) translateY({{ $i * 15 }}px); opacity: {{ 1 - ($i * 0.2) }};">
                    <div class="h-[500px]"></div>
                </div>
            @endfor

            <!-- Current Card -->
            <div class="swipe-card relative rounded-3xl bg-white dark:bg-gray-800 shadow-2xl overflow-hidden"
                 x-ref="card"
                 :style="`transform: translateX(${swipeX}px) rotate(${swipeX * 0.05}deg); transition: ${isDragging ? 'none' : 'transform 0.3s ease-out'}`"
                 @touchstart="startSwipe($event)"
                 @touchmove="moveSwipe($event)"
                 @touchend="endSwipe()"
                 @mousedown="startSwipe($event)"
                 @mousemove="moveSwipe($event)"
                 @mouseup="endSwipe()"
                 @mouseleave="endSwipe()">
                
                <!-- Swipe Indicators -->
                <div class="absolute top-8 left-8 z-20 transform -rotate-12 opacity-0 transition-opacity"
                     :style="`opacity: ${Math.min(1, swipeX / 100)}`">
                    <div class="border-4 border-green-500 text-green-500 font-bold text-2xl px-4 py-2 rounded-lg">
                        SAVE
                    </div>
                </div>
                <div class="absolute top-8 right-8 z-20 transform rotate-12 opacity-0 transition-opacity"
                     :style="`opacity: ${Math.min(1, -swipeX / 100)}`">
                    <div class="border-4 border-red-500 text-red-500 font-bold text-2xl px-4 py-2 rounded-lg">
                        SKIP
                    </div>
                </div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20 opacity-0 transition-opacity"
                     :style="`opacity: ${Math.min(1, -swipeY / 100)}`">
                    <div class="border-4 border-blue-500 text-blue-500 font-bold text-2xl px-6 py-3 rounded-lg bg-white/90">
                        APPLY
                    </div>
                </div>

                <!-- Company Logo Header -->
                <div class="relative h-32 bg-gradient-to-br from-pink-400 to-purple-600">
                    @if($currentJob['company_logo'])
                        <img src="{{ $currentJob['company_logo'] }}" 
                             alt="{{ $currentJob['company'] }}"
                             class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 w-20 h-20 rounded-2xl bg-white object-cover shadow-lg border-4 border-white">
                    @else
                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 w-20 h-20 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 shadow-lg border-4 border-white flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-400">{{ substr($currentJob['company'], 0, 1) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Card Content -->
                <div class="px-6 pt-12 pb-6" wire:click="toggleDetails">
                    <div class="text-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ $currentJob['title'] }}
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400">{{ $currentJob['company'] }}</p>
                    </div>

                    <!-- Quick Info -->
                    <div class="flex flex-wrap justify-center gap-2 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $currentJob['location'] }}
                        </span>
                        
                        @if($currentJob['is_remote'])
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                🏠 Remote
                            </span>
                        @endif
                        
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                            {{ ucfirst(str_replace('_', ' ', $currentJob['job_type'])) }}
                        </span>
                    </div>

                    <!-- Salary -->
                    <div class="text-center mb-4">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ $currentJob['salary_display'] }}
                        </span>
                    </div>

                    <!-- Description (collapsed by default) -->
                    @if(!$showDetails)
                        <p class="text-gray-600 dark:text-gray-400 text-sm text-center mb-4">
                            {{ $currentJob['short_description'] }}
                        </p>
                        <p class="text-center text-pink-500 text-sm">Tap for more details</p>
                    @endif

                    <!-- Expanded Details -->
                    @if($showDetails)
                        <div class="mt-4 max-h-48 overflow-y-auto hide-scrollbar">
                            <!-- Skills -->
                            @if(!empty($currentJob['skills']))
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Required Skills</h4>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($currentJob['skills'] as $skill)
                                            <span class="px-2 py-1 bg-pink-100 dark:bg-pink-900/30 text-pink-800 dark:text-pink-300 rounded text-xs">
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Full Description -->
                            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                                {!! nl2br(e(Str::limit($currentJob['description'], 500))) !!}
                            </div>

                            <!-- View Full Details Button -->
                            <button wire:click.stop="viewFullJob" 
                                    class="mt-4 w-full py-2 text-center text-pink-500 text-sm font-medium">
                                View Full Job Details →
                            </button>
                        </div>
                    @endif

                    <!-- Posted Time -->
                    <p class="text-center text-gray-400 text-xs mt-4">
                        Posted {{ $currentJob['posted_at'] }}
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="absolute bottom-4 inset-x-0 flex justify-center items-center gap-4 px-8">
                <!-- Skip Button -->
                <button wire:click="skipJob" 
                        class="w-14 h-14 rounded-full bg-white shadow-lg flex items-center justify-center text-red-500 hover:scale-110 transition transform active:scale-95">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Undo Button -->
                @if($currentIndex > 0)
                    <button wire:click="previousJob"
                            class="w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center text-yellow-500 hover:scale-110 transition transform active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </button>
                @endif

                <!-- Quick Apply Button -->
                <button wire:click="quickApply"
                        class="w-16 h-16 rounded-full bg-gradient-to-r from-pink-500 to-purple-600 shadow-lg flex items-center justify-center text-white hover:scale-110 transition transform active:scale-95">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                </button>

                <!-- Save Button -->
                <button wire:click="saveJob"
                        class="w-14 h-14 rounded-full bg-white shadow-lg flex items-center justify-center text-green-500 hover:scale-110 transition transform active:scale-95"
                        title="Save Job">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </button>
            </div>

        @else
            <!-- No More Jobs -->
            <div class="flex flex-col items-center justify-center h-[70vh] text-center px-8">
                <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">All Caught Up!</h2>
                <p class="text-white/60 mb-6">You've viewed all available jobs matching your criteria.</p>
                <div class="flex gap-4">
                    <a href="{{ route('jobs.search') }}" 
                       class="px-6 py-3 bg-white text-pink-600 font-semibold rounded-lg hover:bg-pink-50 transition">
                        Browse All Jobs
                    </a>
                    <button wire:click="$refresh"
                            class="px-6 py-3 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition">
                        Refresh
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Swipe Instructions (shown first time) -->
    @if($currentIndex === 0 && count($jobs) > 0)
        <div x-data="{ show: !localStorage.getItem('swipe-tutorial-seen') }"
             x-show="show"
             x-transition
             @click="show = false; localStorage.setItem('swipe-tutorial-seen', 'true')"
             class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-8">
            <div class="text-center text-white max-w-xs">
                <div class="mb-8">
                    <div class="flex items-center justify-center gap-8 mb-6">
                        <div class="text-center">
                            <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </div>
                            <span class="text-sm">Skip</span>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <span class="text-sm">Save</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </div>
                        <span class="text-sm">Quick Apply</span>
                    </div>
                </div>
                <p class="text-white/60 text-sm">Tap anywhere to start</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function swipeHandler() {
    return {
        swipeX: 0,
        swipeY: 0,
        startX: 0,
        startY: 0,
        isDragging: false,
        threshold: 100,

        init() {
            // Listen for offline job save events
            Livewire.on('save-job-offline', ({ job }) => {
                this.saveJobToIndexedDB(job);
            });
        },

        startSwipe(e) {
            this.isDragging = true;
            const point = e.touches ? e.touches[0] : e;
            this.startX = point.clientX;
            this.startY = point.clientY;
        },

        moveSwipe(e) {
            if (!this.isDragging) return;
            
            const point = e.touches ? e.touches[0] : e;
            this.swipeX = point.clientX - this.startX;
            this.swipeY = point.clientY - this.startY;
        },

        endSwipe() {
            if (!this.isDragging) return;
            this.isDragging = false;

            // Check thresholds
            if (this.swipeX > this.threshold) {
                // Swipe right - Save
                Livewire.dispatch('swipe-right');
            } else if (this.swipeX < -this.threshold) {
                // Swipe left - Skip
                Livewire.dispatch('swipe-left');
            } else if (this.swipeY < -this.threshold) {
                // Swipe up - Quick Apply
                Livewire.dispatch('swipe-up');
            }

            // Reset position
            this.swipeX = 0;
            this.swipeY = 0;
        },

        async saveJobToIndexedDB(job) {
            if (!('indexedDB' in window)) return;

            const dbName = 'studai-career-offline';
            const storeName = 'saved-jobs';

            const request = indexedDB.open(dbName, 1);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'id' });
                }
            };

            request.onsuccess = (event) => {
                const db = event.target.result;
                const transaction = db.transaction([storeName], 'readwrite');
                const store = transaction.objectStore(storeName);
                store.put({ ...job, savedAt: new Date().toISOString() });
            };
        }
    };
}
</script>
@endpush
