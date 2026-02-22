{{--
    StudAI Card Component
    
    Usage:
    <x-studai.card>Content</x-studai.card>
    <x-studai.card variant="elevated" padding="lg">Content</x-studai.card>
    <x-studai.card variant="glass" :hoverable="true">Content</x-studai.card>
--}}

@props([
    'variant' => 'default', // default, flat, elevated, glass, interactive
    'padding' => 'md', // none, sm, md, lg
    'hoverable' => false,
    'as' => 'div',
])

@php
    $baseClasses = 'rounded-card transition-all duration-200';
    
    $variantClasses = match($variant) {
        'flat' => 'bg-white border border-surface-200',
        'elevated' => 'bg-white shadow-elevation-2 hover:shadow-elevation-3 hover:-translate-y-0.5',
        'glass' => 'bg-white/80 backdrop-blur-xl border border-white/20 shadow-soft',
        'interactive' => 'bg-white border border-surface-200 shadow-card hover:shadow-card-hover hover:-translate-y-0.5 cursor-pointer',
        default => 'bg-white border border-surface-200 shadow-card hover:shadow-card-hover',
    };
    
    $paddingClasses = match($padding) {
        'none' => '',
        'sm' => 'p-4',
        'lg' => 'p-8',
        default => 'p-6',
    };
    
    $hoverClasses = $hoverable ? 'hover:shadow-card-hover hover:-translate-y-0.5' : '';
    
    $classes = implode(' ', array_filter([$baseClasses, $variantClasses, $paddingClasses, $hoverClasses]));
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
