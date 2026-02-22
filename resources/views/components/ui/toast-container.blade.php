@props([
    'position' => 'bottom-right',
    'maxToasts' => 5,
    'defaultDuration' => 4000,
])

@php
    $positionClasses = match($position) {
        'top-left' => 'top-4 left-4',
        'top-right' => 'top-4 right-4',
        'top-center' => 'top-4 left-1/2 -translate-x-1/2',
        'bottom-left' => 'bottom-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-center' => 'bottom-4 left-1/2 -translate-x-1/2',
        default => 'bottom-4 right-4',
    };
@endphp

<div
    x-data="toastContainer({
        maxToasts: {{ $maxToasts }},
        defaultDuration: {{ $defaultDuration }},
        position: '{{ $position }}'
    })"
    x-on:toast.window="addToast($event.detail)"
    {{ $attributes->merge(['class' => 'fixed z-50 flex flex-col gap-3 pointer-events-none ' . $positionClasses]) }}
    style="max-width: 420px; width: 100%;"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-data="{ show: false, progress: 100 }"
            x-init="
                $nextTick(() => { show = true });
                if (toast.duration > 0) {
                    let startTime = Date.now();
                    let interval = setInterval(() => {
                        let elapsed = Date.now() - startTime;
                        progress = Math.max(0, 100 - (elapsed / toast.duration) * 100);
                        if (progress <= 0) {
                            clearInterval(interval);
                            removeToast(toast.id);
                        }
                    }, 50);
                    toast._interval = interval;
                }
            "
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            @mouseenter="if (toast._interval) { clearInterval(toast._interval); toast._paused = true; }"
            @mouseleave="
                if (toast._paused && toast.duration > 0) {
                    let remainingTime = (progress / 100) * toast.duration;
                    let startTime = Date.now();
                    toast._interval = setInterval(() => {
                        let elapsed = Date.now() - startTime;
                        progress = Math.max(0, progress - (elapsed / remainingTime) * progress);
                        if (progress <= 0) {
                            clearInterval(toast._interval);
                            removeToast(toast.id);
                        }
                        startTime = Date.now();
                        remainingTime = (progress / 100) * toast.duration;
                    }, 50);
                    toast._paused = false;
                }
            "
            class="pointer-events-auto w-full"
        >
            <div
                :class="{
                    'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800': toast.type === 'success',
                    'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800': toast.type === 'error',
                    'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800': toast.type === 'warning',
                    'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800': toast.type === 'info',
                }"
                class="relative overflow-hidden rounded-lg border shadow-lg backdrop-blur-sm"
            >
                {{-- Main Content --}}
                <div class="flex items-start gap-3 p-4">
                    {{-- Icon --}}
                    <div
                        :class="{
                            'text-green-500 dark:text-green-400': toast.type === 'success',
                            'text-red-500 dark:text-red-400': toast.type === 'error',
                            'text-yellow-500 dark:text-yellow-400': toast.type === 'warning',
                            'text-blue-500 dark:text-blue-400': toast.type === 'info',
                        }"
                        class="flex-shrink-0 mt-0.5"
                    >
                        {{-- Success Icon --}}
                        <template x-if="toast.type === 'success'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>

                        {{-- Error Icon --}}
                        <template x-if="toast.type === 'error'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>

                        {{-- Warning Icon --}}
                        <template x-if="toast.type === 'warning'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </template>

                        {{-- Info Icon --}}
                        <template x-if="toast.type === 'info'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                    </div>

                    {{-- Message & Title --}}
                    <div class="flex-1 min-w-0">
                        <template x-if="toast.title">
                            <p
                                :class="{
                                    'text-green-800 dark:text-green-200': toast.type === 'success',
                                    'text-red-800 dark:text-red-200': toast.type === 'error',
                                    'text-yellow-800 dark:text-yellow-200': toast.type === 'warning',
                                    'text-blue-800 dark:text-blue-200': toast.type === 'info',
                                }"
                                class="text-sm font-semibold"
                                x-text="toast.title"
                            ></p>
                        </template>
                        <p
                            :class="{
                                'text-green-700 dark:text-green-300': toast.type === 'success',
                                'text-red-700 dark:text-red-300': toast.type === 'error',
                                'text-yellow-700 dark:text-yellow-300': toast.type === 'warning',
                                'text-blue-700 dark:text-blue-300': toast.type === 'info',
                            }"
                            class="text-sm"
                            x-text="toast.message"
                        ></p>

                        {{-- Action Button --}}
                        <template x-if="toast.action">
                            <div class="mt-2">
                                <button
                                    @click="
                                        if (toast.action.onClick) toast.action.onClick();
                                        removeToast(toast.id);
                                    "
                                    :class="{
                                        'text-green-700 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300': toast.type === 'success',
                                        'text-red-700 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300': toast.type === 'error',
                                        'text-yellow-700 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300': toast.type === 'warning',
                                        'text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300': toast.type === 'info',
                                    }"
                                    class="text-sm font-medium underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-1 rounded"
                                    x-text="toast.action.label"
                                ></button>
                            </div>
                        </template>
                    </div>

                    {{-- Close Button --}}
                    <button
                        @click="removeToast(toast.id)"
                        :class="{
                            'text-green-400 hover:text-green-600 dark:text-green-500 dark:hover:text-green-300': toast.type === 'success',
                            'text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-300': toast.type === 'error',
                            'text-yellow-400 hover:text-yellow-600 dark:text-yellow-500 dark:hover:text-yellow-300': toast.type === 'warning',
                            'text-blue-400 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-300': toast.type === 'info',
                        }"
                        class="flex-shrink-0 p-1 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Progress Bar --}}
                <template x-if="toast.duration > 0 && toast.showProgress !== false">
                    <div class="h-1 w-full bg-black/5 dark:bg-white/10">
                        <div
                            :class="{
                                'bg-green-500 dark:bg-green-400': toast.type === 'success',
                                'bg-red-500 dark:bg-red-400': toast.type === 'error',
                                'bg-yellow-500 dark:bg-yellow-400': toast.type === 'warning',
                                'bg-blue-500 dark:bg-blue-400': toast.type === 'info',
                            }"
                            class="h-full transition-all duration-100 ease-linear"
                            :style="'width: ' + progress + '%'"
                        ></div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('toastContainer', (config) => ({
                toasts: [],
                maxToasts: config.maxToasts || 5,
                defaultDuration: config.defaultDuration || 4000,
                position: config.position || 'bottom-right',
                idCounter: 0,

                init() {
                    // Register global toast method
                    window.$toast = {
                        show: (message, options = {}) => this.addToast({ message, ...options }),
                        success: (message, options = {}) => this.addToast({ type: 'success', message, ...options }),
                        error: (message, options = {}) => this.addToast({ type: 'error', message, ...options }),
                        warning: (message, options = {}) => this.addToast({ type: 'warning', message, ...options }),
                        info: (message, options = {}) => this.addToast({ type: 'info', message, ...options }),
                        dismiss: (id) => this.removeToast(id),
                        dismissAll: () => this.toasts = [],
                    };

                    // Listen for Livewire events
                    if (typeof Livewire !== 'undefined') {
                        Livewire.on('toast', (data) => {
                            // Handle both array format (Livewire 3) and object format
                            const toastData = Array.isArray(data) ? data[0] : data;
                            this.addToast(toastData);
                        });
                    }
                },

                addToast(options) {
                    const toast = {
                        id: ++this.idCounter,
                        type: options.type || 'info',
                        title: options.title || null,
                        message: options.message || '',
                        duration: options.duration !== undefined ? options.duration : this.defaultDuration,
                        action: options.action || null,
                        showProgress: options.showProgress !== false,
                        _interval: null,
                        _paused: false,
                    };

                    // Check position to determine stack order
                    if (this.position.startsWith('top')) {
                        this.toasts.push(toast);
                    } else {
                        this.toasts.unshift(toast);
                    }

                    // Enforce max toasts limit
                    while (this.toasts.length > this.maxToasts) {
                        const removedToast = this.position.startsWith('top')
                            ? this.toasts.shift()
                            : this.toasts.pop();

                        if (removedToast && removedToast._interval) {
                            clearInterval(removedToast._interval);
                        }
                    }

                    return toast.id;
                },

                removeToast(id) {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index !== -1) {
                        const toast = this.toasts[index];
                        if (toast._interval) {
                            clearInterval(toast._interval);
                        }
                        this.toasts.splice(index, 1);
                    }
                },
            }));
        });
    </script>
    @endpush
@endonce
