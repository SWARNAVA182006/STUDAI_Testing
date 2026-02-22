@props([
    'type' => 'text', // text, image, card, circular
    'class' => '',
    'rows' => 3,
    'animated' => true,
])

@php
    $baseClass = 'bg-gray-200 rounded';
    $animationClass = $animated ? 'animate-pulse' : '';
@endphp

@if($type === 'text')
    <div class="space-y-3 {{ $class }}">
        @for($i = 0; $i < $rows; $i++)
            <div class="{{ $baseClass }} {{ $animationClass }} h-4 {{ $i === $rows - 1 ? 'w-2/3' : 'w-full' }}"></div>
        @endfor
    </div>

@elseif($type === 'image')
    <div class="{{ $baseClass }} {{ $animationClass }} {{ $class }}" style="aspect-ratio: 16/9;"></div>

@elseif($type === 'circular')
    <div class="{{ $baseClass }} rounded-full {{ $animationClass }} {{ $class }}" style="aspect-ratio: 1/1;"></div>

@elseif($type === 'card')
    <div class="border border-gray-200 rounded-2xl p-6 {{ $class }}">
        <div class="flex items-center space-x-4 mb-4">
            <div class="{{ $baseClass }} rounded-full {{ $animationClass }} w-12 h-12"></div>
            <div class="flex-1">
                <div class="{{ $baseClass }} {{ $animationClass }} h-4 w-3/4 mb-2"></div>
                <div class="{{ $baseClass }} {{ $animationClass }} h-3 w-1/2"></div>
            </div>
        </div>
        <div class="space-y-2">
            @for($i = 0; $i < $rows; $i++)
                <div class="{{ $baseClass }} {{ $animationClass }} h-3 {{ $i === $rows - 1 ? 'w-2/3' : 'w-full' }}"></div>
            @endfor
        </div>
    </div>

@else
    <div class="{{ $baseClass }} {{ $animationClass }} {{ $class }}"></div>
@endif
