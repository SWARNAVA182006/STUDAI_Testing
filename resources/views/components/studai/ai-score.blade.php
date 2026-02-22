{{--
    StudAI AI Score Component
    
    Usage:
    <x-studai.ai-score :score="89" />
    <x-studai.ai-score :score="75" size="lg" :show-label="true" />
--}}

@props([
    'score' => 0,
    'size' => 'md', // sm, md, lg, xl
    'showLabel' => false,
    'animated' => true,
])

@php
    $scoreLevel = match(true) {
        $score >= 85 => 'excellent',
        $score >= 70 => 'good',
        $score >= 50 => 'average',
        default => 'poor',
    };
    
    $colors = match($scoreLevel) {
        'excellent' => ['text' => 'text-google-green-600', 'bg' => 'bg-google-green-500', 'ring' => 'stroke-google-green-500'],
        'good' => ['text' => 'text-google-blue-600', 'bg' => 'bg-google-blue-500', 'ring' => 'stroke-google-blue-500'],
        'average' => ['text' => 'text-google-yellow-600', 'bg' => 'bg-google-yellow-500', 'ring' => 'stroke-google-yellow-500'],
        'poor' => ['text' => 'text-google-red-600', 'bg' => 'bg-google-red-500', 'ring' => 'stroke-google-red-500'],
    };
    
    $sizes = match($size) {
        'sm' => ['container' => 'w-10 h-10', 'ring' => '36', 'stroke' => '3', 'text' => 'text-xs', 'label' => 'text-xs'],
        'lg' => ['container' => 'w-20 h-20', 'ring' => '72', 'stroke' => '4', 'text' => 'text-xl', 'label' => 'text-sm'],
        'xl' => ['container' => 'w-28 h-28', 'ring' => '100', 'stroke' => '5', 'text' => 'text-2xl', 'label' => 'text-base'],
        default => ['container' => 'w-14 h-14', 'ring' => '50', 'stroke' => '4', 'text' => 'text-sm', 'label' => 'text-xs'],
    };
    
    $ringSize = intval($sizes['ring']);
    $radius = ($ringSize / 2) - (intval($sizes['stroke']) / 2);
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($score / 100 * $circumference);
@endphp

<div 
    {{ $attributes->merge(['class' => 'flex flex-col items-center']) }}
    @if($animated) x-data="{ shown: false }" x-intersect.once="shown = true" @endif
>
    <div class="relative {{ $sizes['container'] }}">
        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 {{ $ringSize }} {{ $ringSize }}">
            {{-- Background ring --}}
            <circle
                class="stroke-surface-200"
                stroke-width="{{ $sizes['stroke'] }}"
                fill="transparent"
                r="{{ $radius }}"
                cx="{{ $ringSize / 2 }}"
                cy="{{ $ringSize / 2 }}"
            />
            {{-- Progress ring --}}
            <circle
                class="{{ $colors['ring'] }} transition-all duration-1000 ease-out"
                stroke-width="{{ $sizes['stroke'] }}"
                stroke-linecap="round"
                fill="transparent"
                r="{{ $radius }}"
                cx="{{ $ringSize / 2 }}"
                cy="{{ $ringSize / 2 }}"
                @if($animated)
                    :stroke-dasharray="{{ $circumference }}"
                    :stroke-dashoffset="shown ? {{ $offset }} : {{ $circumference }}"
                @else
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $offset }}"
                @endif
            />
        </svg>
        
        {{-- Score value --}}
        <div class="absolute inset-0 flex items-center justify-center">
            <span 
                class="{{ $sizes['text'] }} font-bold {{ $colors['text'] }}"
                @if($animated)
                    x-text="shown ? '{{ $score }}%' : '0%'"
                @endif
            >
                @if(!$animated){{ $score }}%@endif
            </span>
        </div>
    </div>
    
    @if($showLabel)
        <div class="mt-2 text-center">
            <span class="{{ $sizes['label'] }} font-medium {{ $colors['text'] }} capitalize">
                {{ $scoreLevel }} Match
            </span>
        </div>
    @endif
</div>
