@props([
    'src',
    'alt' => '',
    'class' => '',
    'width' => null,
    'height' => null,
    'lazy' => true,
    'sizes' => null,
    'srcset' => null,
    'objectFit' => 'cover',
    'priority' => false,
])

@php
    // Generate responsive srcset if sizes provided
    $responsiveSrcset = null;
    if ($sizes && is_array($sizes)) {
        $responsiveSrcset = responsive_srcset($src, $sizes);
    } elseif ($srcset) {
        $responsiveSrcset = $srcset;
    }

    // Use CDN for assets
    $imageSrc = cdn_asset($src);

    // Determine loading strategy
    $loading = $priority ? 'eager' : ($lazy ? 'lazy' : 'auto');
    
    // Default sizes attribute for responsive images
    $sizesAttr = $sizes ? '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw' : null;
@endphp

<img
    src="{{ $imageSrc }}"
    alt="{{ $alt }}"
    @if($class) class="{{ $class }}" @endif
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    @if($responsiveSrcset) srcset="{{ $responsiveSrcset }}" @endif
    @if($sizesAttr) sizes="{{ $sizesAttr }}" @endif
    loading="{{ $loading }}"
    @if($priority) fetchpriority="high" @endif
    style="object-fit: {{ $objectFit }};"
    {{ $attributes }}
/>
