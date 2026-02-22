<?php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Get asset URL with CDN support
     *
     * @param string $path
     * @return string
     */
    public static function cdn(string $path): string
    {
        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        // Get CDN URL from config
        $cdnUrl = config('app.cdn_url');
        
        // If CDN is configured and we're in production, use CDN
        if ($cdnUrl && app()->environment('production')) {
            return rtrim($cdnUrl, '/') . '/' . $path;
        }
        
        // Otherwise, use local asset
        return asset($path);
    }

    /**
     * Get versioned asset URL with CDN support
     *
     * @param string $path
     * @return string
     */
    public static function versionedCdn(string $path): string
    {
        $cdnUrl = config('app.cdn_url');
        
        if ($cdnUrl && app()->environment('production')) {
            // Get file modification time for cache busting
            $publicPath = public_path($path);
            $version = file_exists($publicPath) ? filemtime($publicPath) : time();
            
            return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/') . '?v=' . $version;
        }
        
        return asset($path);
    }

    /**
     * Generate responsive image srcset
     *
     * @param string $basePath
     * @param array $sizes [width => suffix]
     * @return string
     */
    public static function responsiveSrcset(string $basePath, array $sizes = [640 => 'sm', 1024 => 'md', 1920 => 'lg']): string
    {
        $srcset = [];
        $pathInfo = pathinfo($basePath);
        
        foreach ($sizes as $width => $suffix) {
            $imagePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $suffix . '.' . $pathInfo['extension'];
            $srcset[] = self::cdn($imagePath) . ' ' . $width . 'w';
        }
        
        return implode(', ', $srcset);
    }

    /**
     * Get optimized image URL
     *
     * @param string $path
     * @param int|null $width
     * @param int|null $height
     * @param string $format
     * @return string
     */
    public static function optimizedImage(string $path, ?int $width = null, ?int $height = null, string $format = 'webp'): string
    {
        // If using a CDN with image optimization (like Cloudinary, Imgix, etc.)
        $cdnUrl = config('app.cdn_url');
        
        if ($cdnUrl && config('app.cdn_image_optimization')) {
            $params = [];
            if ($width) $params[] = 'w_' . $width;
            if ($height) $params[] = 'h_' . $height;
            if ($format) $params[] = 'f_' . $format;
            
            $transformation = !empty($params) ? implode(',', $params) . '/' : '';
            return rtrim($cdnUrl, '/') . '/' . $transformation . ltrim($path, '/');
        }
        
        return self::cdn($path);
    }

    /**
     * Preload critical assets
     *
     * @param array $assets
     * @return string
     */
    public static function preloadLinks(array $assets): string
    {
        $links = [];
        
        foreach ($assets as $asset) {
            $url = self::cdn($asset['path']);
            $type = $asset['type'] ?? 'script';
            $as = $asset['as'] ?? ($type === 'style' ? 'style' : 'script');
            
            $links[] = sprintf(
                '<link rel="preload" href="%s" as="%s"%s>',
                $url,
                $as,
                isset($asset['crossorigin']) ? ' crossorigin' : ''
            );
        }
        
        return implode("\n    ", $links);
    }

    /**
     * Generate inline critical CSS
     *
     * @param string $cssFile
     * @return string
     */
    public static function inlineCriticalCss(string $cssFile): string
    {
        $path = public_path('css/' . $cssFile);
        
        if (file_exists($path)) {
            $css = file_get_contents($path);
            return '<style>' . $css . '</style>';
        }
        
        return '';
    }

    /**
     * Defer non-critical JavaScript
     *
     * @param string $path
     * @return string
     */
    public static function deferScript(string $path): string
    {
        $url = self::cdn($path);
        return sprintf('<script src="%s" defer></script>', $url);
    }

    /**
     * Load script asynchronously
     *
     * @param string $path
     * @return string
     */
    public static function asyncScript(string $path): string
    {
        $url = self::cdn($path);
        return sprintf('<script src="%s" async></script>', $url);
    }
}
