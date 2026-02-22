<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeAssets extends Command
{
    protected $signature = 'assets:optimize';
    protected $description = 'Optimize CSS, JS, and image assets for production';

    public function handle()
    {
        $this->info('Optimizing assets...');

        // Build assets
        $this->call('npm', ['run', 'build']);

        // Optimize images
        $this->optimizeImages();

        // Generate asset manifest
        $this->generateManifest();

        $this->info('Assets optimized successfully!');
        
        return 0;
    }

    /**
     * Optimize images in public directory.
     */
    private function optimizeImages(): void
    {
        $this->info('Optimizing images...');

        $imageService = app(\App\Services\ImageOptimizationService::class);
        $publicImages = File::allFiles(public_path('images'));

        $bar = $this->output->createProgressBar(count($publicImages));
        $bar->start();

        foreach ($publicImages as $image) {
            try {
                // Only process jpg, png, webp
                if (in_array($image->getExtension(), ['jpg', 'jpeg', 'png', 'webp'])) {
                    $imageService->optimizeAndStore($image->getPathname(), 'images');
                }
            } catch (\Exception $e) {
                $this->error("Failed to optimize {$image->getFilename()}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Generate asset manifest for CDN.
     */
    private function generateManifest(): void
    {
        $this->info('Generating asset manifest...');

        $manifest = [];
        $buildPath = public_path('build');

        if (File::exists($buildPath)) {
            $files = File::allFiles($buildPath);

            foreach ($files as $file) {
                $relativePath = str_replace($buildPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $manifest[$relativePath] = [
                    'path' => $relativePath,
                    'hash' => md5_file($file->getPathname()),
                    'size' => $file->getSize(),
                ];
            }
        }

        File::put(public_path('build/manifest.json'), json_encode($manifest, JSON_PRETTY_PRINT));

        $this->info('Asset manifest generated: ' . count($manifest) . ' files');
    }
}
