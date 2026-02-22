{{--
    StudAI Button Component
    
    Usage:
    <x-studai.button>Click me</x-studai.button>
    <x-studai.button variant="secondary" size="lg">Submit</x-studai.button>
    <x-studai.button variant="ghost" icon="heroicon-o-plus">Add Item</x-studai.button>
--}}

@props([
    'variant' => 'primary', // primary, secondary, ghost, outline, danger, success
    'size' => 'md', // xs, sm, md, lg, xl
    'type' => 'button',
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
    'href' => null,
    'as' => null,
])

@php
    $tag = $as ?? ($href ? 'a' : 'button');
    
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium rounded-button transition-all duration-150 focus-ring disabled:opacity-50 disabled:cursor-not-allowed';
    
    $sizeClasses = match($size) {
        'xs' => 'px-2.5 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-6 py-2.5 text-base',
        'xl' => 'px-8 py-3 text-base',
        default => 'px-4 py-2 text-sm',
    };
    
    $variantClasses = match($variant) {
        'secondary' => 'bg-canvas-subtle text-google-blue-600 border border-surface-300 hover:bg-google-blue-50 hover:border-google-blue-600',
        'ghost' => 'bg-transparent text-ink-secondary hover:bg-surface-100 hover:text-ink-primary',
        'outline' => 'bg-transparent border border-surface-300 text-ink-primary hover:bg-surface-50 hover:border-surface-400',
        'danger' => 'bg-google-red-500 text-white hover:bg-google-red-600 shadow-button hover:shadow-button-hover',
        'success' => 'bg-google-green-500 text-white hover:bg-google-green-600 shadow-button hover:shadow-button-hover',
        default => 'bg-google-blue-600 text-white hover:bg-google-blue-700 shadow-button hover:shadow-button-hover active:scale-[0.98]',
    };
    
    $classes = implode(' ', [$baseClasses, $sizeClasses, $variantClasses]);
@endphp

<{{ $tag }}
    @if($tag === 'button')
        type="{{ $type }}"
        @if($disabled || $loading) disabled @endif
    @endif
    @if($href)
        href="{{ $href }}"
    @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($loading)
        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <x-dynamic-component :component="$icon" class="w-5 h-5" />
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'right' && !$loading)
        <x-dynamic-component :component="$icon" class="w-5 h-5" />
    @endif
</{{ $tag }}>
