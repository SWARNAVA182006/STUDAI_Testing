{{--
    StudAI Chip/Tag Component
    
    Usage:
    <x-studai.chip>Default</x-studai.chip>
    <x-studai.chip variant="primary">Primary</x-studai.chip>
    <x-studai.chip variant="ai" icon="sparkles">AI Powered</x-studai.chip>
    <x-studai.chip :removable="true" @remove="handleRemove">Removable</x-studai.chip>
--}}

@props([
    'variant' => 'default', // default, primary, success, warning, error, purple, ai
    'size' => 'md', // sm, md, lg
    'icon' => null,
    'removable' => false,
    'interactive' => false,
])

@php
    $baseClasses = 'inline-flex items-center gap-1.5 font-medium rounded-chip transition-all duration-150';
    
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-xs',
        'lg' => 'px-4 py-1.5 text-sm',
        default => 'px-3 py-1 text-xs',
    };
    
    $variantClasses = match($variant) {
        'primary' => 'bg-google-blue-50 text-google-blue-600',
        'success' => 'bg-google-green-50 text-google-green-600',
        'warning' => 'bg-google-yellow-50 text-google-yellow-700',
        'error' => 'bg-google-red-50 text-google-red-600',
        'purple' => 'bg-purple-50 text-purple-600',
        'ai' => 'bg-gradient-to-r from-google-blue-50 to-purple-50 text-purple-600',
        default => 'bg-surface-100 text-ink-secondary',
    };
    
    $interactiveClasses = $interactive ? 'cursor-pointer hover:shadow-sm' : '';
    
    $classes = implode(' ', [$baseClasses, $sizeClasses, $variantClasses, $interactiveClasses]);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        @if($icon === 'sparkles' || $icon === 'ai')
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
        @else
            <x-dynamic-component :component="$icon" class="w-3.5 h-3.5" />
        @endif
    @endif

    {{ $slot }}

    @if($removable)
        <button 
            type="button"
            @click="$dispatch('remove')"
            class="ml-1 -mr-1 p-0.5 rounded-full hover:bg-black/10 transition-colors"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</span>
