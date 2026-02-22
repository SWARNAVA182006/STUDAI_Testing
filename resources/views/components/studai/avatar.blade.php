{{--
    StudAI Avatar Component
    
    Usage:
    <x-studai.avatar src="/path/to/image.jpg" name="John Doe" />
    <x-studai.avatar name="Jane Smith" size="lg" status="online" />
--}}

@props([
    'src' => null,
    'name' => '',
    'size' => 'md', // xs, sm, md, lg, xl, 2xl
    'status' => null, // online, offline, busy
    'rounded' => 'full', // full, lg, md
])

@php
    $sizeClasses = match($size) {
        'xs' => 'w-6 h-6 text-xs',
        'sm' => 'w-8 h-8 text-sm',
        'lg' => 'w-12 h-12 text-lg',
        'xl' => 'w-16 h-16 text-xl',
        '2xl' => 'w-24 h-24 text-2xl',
        default => 'w-10 h-10 text-base',
    };
    
    $statusSizeClasses = match($size) {
        'xs' => 'w-2 h-2',
        'sm' => 'w-2.5 h-2.5',
        'lg' => 'w-4 h-4',
        'xl' => 'w-5 h-5',
        '2xl' => 'w-6 h-6',
        default => 'w-3 h-3',
    };
    
    $statusColors = [
        'online' => 'bg-google-green-500',
        'offline' => 'bg-surface-400',
        'busy' => 'bg-google-red-500',
    ];
    
    $roundedClasses = match($rounded) {
        'lg' => 'rounded-lg',
        'md' => 'rounded-md',
        default => 'rounded-full',
    };
    
    $initials = collect(explode(' ', $name))
        ->map(fn($word) => strtoupper(substr($word, 0, 1)))
        ->take(2)
        ->join('');
@endphp

<div {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center {$sizeClasses} {$roundedClasses} bg-surface-200 overflow-hidden"]) }}>
    @if($src)
        <img 
            src="{{ $src }}" 
            alt="{{ $name }}" 
            class="w-full h-full object-cover"
            loading="lazy"
        >
    @else
        <span class="font-medium text-ink-secondary">{{ $initials }}</span>
    @endif
    
    @if($status)
        <span class="absolute bottom-0 right-0 {{ $statusSizeClasses }} {{ $statusColors[$status] ?? $statusColors['offline'] }} {{ $roundedClasses }} border-2 border-white"></span>
    @endif
</div>
