{{--
    StudAI Progress Bar Component
    
    Usage:
    <x-studai.progress :value="75" />
    <x-studai.progress :value="90" variant="success" :show-label="true" />
--}}

@props([
    'value' => 0,
    'max' => 100,
    'variant' => 'default', // default, success, warning, error, gradient
    'size' => 'md', // sm, md, lg
    'showLabel' => false,
    'label' => null,
    'animated' => true,
])

@php
    $percentage = min(100, max(0, ($value / $max) * 100));
    
    $sizeClasses = match($size) {
        'sm' => 'h-1.5',
        'lg' => 'h-3',
        default => 'h-2',
    };
    
    $barVariant = match($variant) {
        'success' => 'bg-google-green-500',
        'warning' => 'bg-google-yellow-500',
        'error' => 'bg-google-red-500',
        'gradient' => 'bg-gradient-to-r from-google-blue-500 to-purple-500',
        default => 'bg-google-blue-500',
    };
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($showLabel || $label)
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm font-medium text-ink-primary">{{ $label ?? 'Progress' }}</span>
            <span class="text-sm text-ink-secondary">{{ round($percentage) }}%</span>
        </div>
    @endif
    
    <div class="w-full {{ $sizeClasses }} bg-surface-200 rounded-full overflow-hidden">
        <div 
            class="{{ $sizeClasses }} {{ $barVariant }} rounded-full transition-all duration-500 ease-out"
            @if($animated)
                x-data="{ shown: false }"
                x-intersect.once="shown = true"
                :style="shown ? 'width: {{ $percentage }}%' : 'width: 0%'"
            @else
                style="width: {{ $percentage }}%"
            @endif
        ></div>
    </div>
</div>
