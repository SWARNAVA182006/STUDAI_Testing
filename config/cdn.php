<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('CDN_ENABLED', false),
    'url' => env('CDN_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Asset Types
    |--------------------------------------------------------------------------
    */

    'assets' => [
        // Static assets (CSS, JS, fonts)
        'static' => [
            'enabled' => env('CDN_STATIC_ENABLED', true),
            'path' => 'build',
        ],

        // User uploads (resumes, avatars, logos)
        'uploads' => [
            'enabled' => env('CDN_UPLOADS_ENABLED', true),
            'path' => 'storage',
        ],

        // Images
        'images' => [
            'enabled' => env('CDN_IMAGES_ENABLED', true),
            'path' => 'images',
            'formats' => ['webp', 'jpg', 'png'],
            'quality' => [
                'webp' => 85,
                'jpg' => 85,
                'png' => 9, // compression level 0-9
            ],
            'sizes' => [
                'thumbnail' => ['width' => 150, 'height' => 150],
                'small' => ['width' => 300, 'height' => 300],
                'medium' => ['width' => 600, 'height' => 600],
                'large' => ['width' => 1200, 'height' => 1200],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Control Headers
    |--------------------------------------------------------------------------
    */

    'cache_control' => [
        // Static assets (versioned) - 1 year
        'static' => 'public, max-age=31536000, immutable',

        // User uploads - 1 month
        'uploads' => 'public, max-age=2592000',

        // Images - 1 week
        'images' => 'public, max-age=604800',

        // HTML pages - no cache
        'html' => 'no-cache, no-store, must-revalidate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    */

    'optimization' => [
        'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
        'driver' => env('IMAGE_DRIVER', 'gd'), // gd or imagick
        
        // Automatic WebP conversion
        'webp_conversion' => true,
        
        // Strip EXIF data
        'strip_exif' => true,
        
        // Progressive JPEG
        'progressive_jpeg' => true,
        
        // Lazy loading
        'lazy_loading' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression
    |--------------------------------------------------------------------------
    */

    'compression' => [
        'enabled' => env('COMPRESSION_ENABLED', true),
        
        // Brotli compression (better than gzip)
        'brotli' => [
            'enabled' => true,
            'level' => 11, // 0-11
        ],
        
        // Gzip fallback
        'gzip' => [
            'enabled' => true,
            'level' => 9, // 0-9
        ],
    ],

];
