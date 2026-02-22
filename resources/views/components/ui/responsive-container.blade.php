@props([
    'size' => 'lg',
    'glass' => false,
    'gradient' => null,
    'padding' => 'md',
    'centered' => true,
])

@php
    // Max-width classes for different container sizes
    $sizeClasses = [
        'sm' => 'max-w-2xl',      // 672px
        'md' => 'max-w-4xl',      // 896px
        'lg' => 'max-w-6xl',      // 1152px
        'xl' => 'max-w-7xl',      // 1280px
        'full' => 'max-w-full',   // 100%
    ];

    // Padding classes for different sizes
    $paddingClasses = [
        'none' => '',
        'sm' => 'px-3 py-2 sm:px-4 sm:py-3',
        'md' => 'px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8',
        'lg' => 'px-6 py-6 sm:px-8 sm:py-8 lg:px-12 lg:py-12',
    ];

    // Glass-morphism effect classes
    $glassClasses = $glass
        ? 'bg-white/10 dark:bg-gray-900/20 backdrop-blur-xl backdrop-saturate-150 border border-white/20 dark:border-gray-700/30 shadow-xl shadow-black/5 dark:shadow-black/20 rounded-2xl'
        : '';

    // Build the complete class string
    $containerClasses = collect([
        'w-full',
        $sizeClasses[$size] ?? $sizeClasses['lg'],
        $paddingClasses[$padding] ?? $paddingClasses['md'],
        $centered ? 'mx-auto' : '',
        $glassClasses,
        $gradient,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    {{ $slot }}
</div>
