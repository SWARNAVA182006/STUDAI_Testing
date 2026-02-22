{{--
    StudAI Input Component
    
    Usage:
    <x-studai.input name="email" label="Email" placeholder="you@example.com" />
    <x-studai.input name="search" type="search" :icon-left="true" />
--}}

@props([
    'name' => '',
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => '',
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'iconLeft' => null,
    'iconRight' => null,
    'size' => 'md', // sm, md, lg
])

@php
    $inputId = $name ? $name : uniqid('input-');
    
    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-5 py-3 text-base',
        default => 'px-4 py-2.5 text-sm',
    };
    
    $baseClasses = 'w-full bg-white border rounded-input placeholder:text-ink-tertiary focus:outline-none focus:border-google-blue-600 transition-all duration-150';
    
    $stateClasses = $error 
        ? 'border-google-red-500 focus:border-google-red-500' 
        : 'border-surface-300';
    
    $iconPadding = '';
    if ($iconLeft) $iconPadding .= ' pl-10';
    if ($iconRight) $iconPadding .= ' pr-10';
    
    $inputClasses = implode(' ', [$baseClasses, $sizeClasses, $stateClasses, $iconPadding]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-ink-primary mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-google-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($iconLeft)
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-tertiary pointer-events-none">
                @if(is_string($iconLeft))
                    <x-dynamic-component :component="$iconLeft" class="w-5 h-5" />
                @else
                    {{ $iconLeft }}
                @endif
            </div>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->except('class')->merge(['class' => $inputClasses]) }}
            @if($error) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
        >

        @if($iconRight)
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-tertiary">
                @if(is_string($iconRight))
                    <x-dynamic-component :component="$iconRight" class="w-5 h-5" />
                @else
                    {{ $iconRight }}
                @endif
            </div>
        @endif
    </div>

    @if($error)
        <p id="{{ $inputId }}-error" class="mt-1.5 text-sm text-google-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="mt-1.5 text-sm text-ink-tertiary">{{ $hint }}</p>
    @endif
</div>
