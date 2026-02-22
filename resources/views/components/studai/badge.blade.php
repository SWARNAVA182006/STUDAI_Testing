{{--
    StudAI Badge Component
    
    Usage:
    <x-studai.badge>Default</x-studai.badge>
    <x-studai.badge variant="success">Hired</x-studai.badge>
    <x-studai.badge variant="warning" dot>Pending</x-studai.badge>
--}}

@props([
    'variant' => 'default', // default, primary, success, warning, error
    'size' => 'md', // sm, md
    'dot' => false,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'px-1.5 py-0.5 text-[10px]',
        default => 'px-2 py-0.5 text-xs',
    };
    
    $variantClasses = match($variant) {
        'primary' => 'bg-google-blue-100 text-google-blue-700',
        'success' => 'bg-google-green-100 text-google-green-700',
        'warning' => 'bg-google-yellow-100 text-google-yellow-800',
        'error' => 'bg-google-red-100 text-google-red-700',
        default => 'bg-surface-200 text-ink-secondary',
    };
    
    $dotColors = match($variant) {
        'primary' => 'bg-google-blue-500',
        'success' => 'bg-google-green-500',
        'warning' => 'bg-google-yellow-500',
        'error' => 'bg-google-red-500',
        default => 'bg-surface-500',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium rounded-full {$sizeClasses} {$variantClasses}"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors }}"></span>
    @endif
    {{ $slot }}
</span>
