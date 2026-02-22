{{--
    Marketing Layout Component Alias
    
    This component acts as an alias for layouts.marketing which already supports $slot.
    
    Usage:
    <x-marketing-layout title="Page Title" description="Page description">
        Your page content here...
    </x-marketing-layout>
--}}

@props([
    'title' => null,
    'description' => null,
    'ogTitle' => null,
    'ogDescription' => null,
    'ogImage' => null,
    'twitterTitle' => null,
    'twitterDescription' => null,
    'twitterImage' => null,
])

<x-layouts.marketing 
    :title="$title" 
    :description="$description"
    :og-title="$ogTitle"
    :og-description="$ogDescription"
    :og-image="$ogImage"
    :twitter-title="$twitterTitle"
    :twitter-description="$twitterDescription"
    :twitter-image="$twitterImage"
>
    {{ $slot }}
</x-layouts.marketing>
