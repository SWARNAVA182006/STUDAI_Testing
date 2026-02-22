<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageOptimizationService
{
    /**
     * Optimize and store an image with multiple sizes.
     */
    public function optimizeAndStore($file, string $path): array
    {
        $sizes = config('cdn.assets.images.sizes');
        $formats = config('cdn.assets.images.formats');
        $urls = [];

        // Get original image
        $image = Image::make($file);

        // Strip EXIF data
        if (config('cdn.optimization.strip_exif')) {
            $image->orientate();
        }

        // Generate sizes
        foreach ($sizes as $sizeName => $dimensions) {
            foreach ($formats as $format) {
                $filename = $this->generateFilename($path, $sizeName, $format);
                
                // Resize
                $resized = clone $image;
                $resized->fit($dimensions['width'], $dimensions['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Convert and save
                $this->saveImage($resized, $filename, $format);
                
                $urls[$sizeName][$format] = Storage::url($filename);
            }
        }

        // Save original
        $originalFilename = $this->generateFilename($path, 'original', 'jpg');
        $this->saveImage($image, $originalFilename, 'jpg');
        $urls['original']['jpg'] = Storage::url($originalFilename);

        return $urls;
    }

    /**
     * Save image in specified format.
     */
    private function saveImage($image, string $filename, string $format): void
    {
        $quality = config("cdn.assets.images.quality.{$format}", 85);

        switch ($format) {
            case 'webp':
                $encoded = $image->encode('webp', $quality);
                break;
            case 'png':
                $encoded = $image->encode('png', $quality);
                break;
            case 'jpg':
            default:
                if (config('cdn.optimization.progressive_jpeg')) {
                    $image->interlace(true);
                }
                $encoded = $image->encode('jpg', $quality);
                break;
        }

        Storage::put($filename, $encoded);
    }

    /**
     * Generate filename for image.
     */
    private function generateFilename(string $path, string $size, string $format): string
    {
        $hash = substr(md5(uniqid()), 0, 8);
        return "{$path}/{$size}-{$hash}.{$format}";
    }

    /**
     * Get responsive image HTML.
     */
    public function getResponsiveImageHtml(array $urls, string $alt = ''): string
    {
        $webpSources = '';
        $jpgSources = '';

        foreach (['thumbnail', 'small', 'medium', 'large'] as $size) {
            if (isset($urls[$size]['webp'])) {
                $webpSources .= '<source media="(max-width: ' . $this->getMaxWidth($size) . 'px)" srcset="' . $urls[$size]['webp'] . '" type="image/webp">';
            }
            if (isset($urls[$size]['jpg'])) {
                $jpgSources .= '<source media="(max-width: ' . $this->getMaxWidth($size) . 'px)" srcset="' . $urls[$size]['jpg'] . '" type="image/jpeg">';
            }
        }

        $defaultSrc = $urls['medium']['jpg'] ?? $urls['original']['jpg'] ?? '';
        
        $lazyLoading = config('cdn.optimization.lazy_loading') ? 'loading="lazy"' : '';

        return <<<HTML
        <picture>
            {$webpSources}
            {$jpgSources}
            <img src="{$defaultSrc}" alt="{$alt}" {$lazyLoading}>
        </picture>
        HTML;
    }

    /**
     * Get max width for size.
     */
    private function getMaxWidth(string $size): int
    {
        $widths = [
            'thumbnail' => 300,
            'small' => 600,
            'medium' => 1024,
            'large' => 1920,
        ];

        return $widths[$size] ?? 1024;
    }

    /**
     * Delete all versions of an image.
     */
    public function deleteImage(array $urls): void
    {
        foreach ($urls as $sizeUrls) {
            foreach ($sizeUrls as $url) {
                $path = str_replace(Storage::url(''), '', $url);
                Storage::delete($path);
            }
        }
    }
}
