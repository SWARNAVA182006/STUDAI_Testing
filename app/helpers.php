<?php

use App\Helpers\AssetHelper;

if (!function_exists('cdn_asset')) {
    /**
     * Generate a CDN URL for an asset.
     *
     * @param string $path
     * @return string
     */
    function cdn_asset(string $path): string
    {
        return AssetHelper::cdn($path);
    }
}

if (!function_exists('versioned_cdn')) {
    /**
     * Generate a versioned CDN URL for an asset.
     *
     * @param string $path
     * @return string
     */
    function versioned_cdn(string $path): string
    {
        return AssetHelper::versionedCdn($path);
    }
}

if (!function_exists('responsive_srcset')) {
    /**
     * Generate responsive image srcset.
     *
     * @param string $basePath
     * @param array $sizes
     * @return string
     */
    function responsive_srcset(string $basePath, array $sizes = []): string
    {
        return AssetHelper::responsiveSrcset($basePath, $sizes);
    }
}

if (!function_exists('optimized_image')) {
    /**
     * Get optimized image URL.
     *
     * @param string $path
     * @param int|null $width
     * @param int|null $height
     * @param string $format
     * @return string
     */
    function optimized_image(string $path, ?int $width = null, ?int $height = null, string $format = 'webp'): string
    {
        return AssetHelper::optimizedImage($path, $width, $height, $format);
    }
}

if (!function_exists('preload_assets')) {
    /**
     * Generate preload link tags.
     *
     * @param array $assets
     * @return string
     */
    function preload_assets(array $assets): string
    {
        return AssetHelper::preloadLinks($assets);
    }
}

if (!function_exists('defer_script')) {
    /**
     * Generate deferred script tag.
     *
     * @param string $path
     * @return string
     */
    function defer_script(string $path): string
    {
        return AssetHelper::deferScript($path);
    }
}

if (!function_exists('async_script')) {
    /**
     * Generate async script tag.
     *
     * @param string $path
     * @return string
     */
    function async_script(string $path): string
    {
        return AssetHelper::asyncScript($path);
    }
}
