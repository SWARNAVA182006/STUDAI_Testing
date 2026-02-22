{{--
    StudAI Search Bar Component (Google-style)
    
    Usage:
    <x-studai.search-bar placeholder="Search jobs, companies, or skills..." />
    <x-studai.search-bar size="lg" :show-suggestions="true" />
--}}

@props([
    'placeholder' => 'Search...',
    'name' => 'search',
    'value' => '',
    'size' => 'lg', // md, lg, xl
    'autofocus' => false,
    'showSuggestions' => false,
])

@php
    $sizeClasses = match($size) {
        'md' => 'px-12 py-2.5 text-sm',
        'xl' => 'px-14 py-4 text-lg',
        default => 'px-12 py-3.5 text-base',
    };
    
    $iconSize = match($size) {
        'md' => 'w-4 h-4',
        'xl' => 'w-6 h-6',
        default => 'w-5 h-5',
    };
@endphp

<div 
    x-data="{ 
        query: '{{ $value }}',
        focused: false,
        suggestions: [],
        loading: false
    }"
    {{ $attributes->merge(['class' => 'relative w-full max-w-2xl mx-auto']) }}
>
    <div class="relative group">
        {{-- Search Icon --}}
        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-tertiary group-focus-within:text-google-blue-600 transition-colors">
            <svg class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        {{-- Input --}}
        <input
            type="search"
            name="{{ $name }}"
            x-model="query"
            @focus="focused = true"
            @blur="setTimeout(() => focused = false, 200)"
            placeholder="{{ $placeholder }}"
            @if($autofocus) autofocus @endif
            class="w-full {{ $sizeClasses }} bg-white rounded-full border border-surface-300 placeholder:text-ink-tertiary focus:outline-none focus:border-transparent shadow-card hover:shadow-card-hover focus:shadow-elevation-3 transition-all duration-200"
        >

        {{-- Clear Button --}}
        <button
            type="button"
            x-show="query.length > 0"
            x-transition
            @click="query = ''"
            class="absolute right-4 top-1/2 -translate-y-1/2 p-1 rounded-full text-ink-tertiary hover:text-ink-secondary hover:bg-surface-100 transition-colors"
        >
            <svg class="{{ $iconSize }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Loading indicator --}}
        <div
            x-show="loading"
            class="absolute right-4 top-1/2 -translate-y-1/2"
        >
            <svg class="{{ $iconSize }} animate-spin text-google-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    {{-- Quick Action Buttons --}}
    @if($size === 'lg' || $size === 'xl')
        <div class="flex justify-center gap-3 mt-4">
            <button type="submit" class="px-5 py-2 text-sm bg-surface-100 hover:bg-surface-200 text-ink-primary rounded-lg transition-colors">
                Search Jobs
            </button>
            <button type="button" class="px-5 py-2 text-sm bg-surface-100 hover:bg-surface-200 text-ink-primary rounded-lg transition-colors">
                I'm Feeling Lucky
            </button>
        </div>
    @endif

    {{-- Suggestions Dropdown --}}
    @if($showSuggestions)
        <div
            x-show="focused && query.length > 2"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="absolute left-0 right-0 top-full mt-2 bg-white rounded-xl border border-surface-200 shadow-elevation-3 py-2 z-dropdown overflow-hidden"
        >
            <div class="px-4 py-2 text-xs font-medium text-ink-tertiary uppercase tracking-wider">
                Recent Searches
            </div>
            <a href="#" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-50 transition-colors">
                <svg class="w-4 h-4 text-ink-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm text-ink-primary">Software Engineer</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-50 transition-colors">
                <svg class="w-4 h-4 text-ink-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm text-ink-primary">Product Manager</span>
            </a>
            
            <div class="border-t border-surface-100 mt-2 pt-2">
                <div class="px-4 py-2 text-xs font-medium text-ink-tertiary uppercase tracking-wider">
                    Trending
                </div>
                <a href="#" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-50 transition-colors">
                    <svg class="w-4 h-4 text-google-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span class="text-sm text-ink-primary">AI/ML Engineer</span>
                    <span class="ml-auto text-xs text-google-green-600 bg-google-green-50 px-2 py-0.5 rounded-full">+127% demand</span>
                </a>
            </div>
        </div>
    @endif
</div>
