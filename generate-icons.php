<?php

/**
 * PWA Icon Generator for StudAI Career
 *
 * Generates all required PWA icons as simple branded PNG files.
 * Run: php generate-icons.php
 *
 * Requires: GD extension (included in most PHP installations)
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$shortcuts = ['shortcut-search', 'shortcut-applications', 'shortcut-messages'];
$shortcutSize = 96;
$outputDir = __DIR__ . '/public/icons';

// Brand colors
$brandBlue = '#1A73E8';

if (!extension_loaded('gd')) {
    echo "GD extension not available. Creating SVG fallbacks...\n";
    // Create SVG-based favicon as fallback
    foreach ($sizes as $size) {
        $svg = generateSvg($size, $brandBlue);
        file_put_contents("{$outputDir}/icon-{$size}x{$size}.svg", $svg);
        echo "  Created icon-{$size}x{$size}.svg\n";
    }
    echo "\nNote: For production, convert SVGs to PNGs using a tool like Inkscape or an online converter.\n";
    exit(0);
}

// Generate main icons
foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // Background: brand blue
    $bg = imagecolorallocate($img, 0x1A, 0x73, 0xE8);
    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);

    // Round corners (approximate with filled arcs)
    $radius = (int)($size * 0.15);
    $white = imagecolorallocate($img, 255, 255, 255);

    // Draw "S" letter centered
    $fontSize = (int)($size * 0.5);
    $textColor = $white;

    // Use built-in font (scale based on size)
    $fontScale = max(1, (int)($size / 40));
    $charWidth = imagefontwidth($fontScale);
    $charHeight = imagefontheight($fontScale);

    // For larger sizes, draw a bigger S using imagestring with scaling
    if ($size >= 128) {
        // Draw a styled "S" using basic shapes
        $cx = $size / 2;
        $cy = $size / 2;
        $r = $size * 0.28;

        imagesetthickness($img, max(2, (int)($size * 0.06)));
        // Top arc of S
        imagearc($img, (int)$cx, (int)($cy - $r * 0.45), (int)($r * 1.6), (int)($r * 1.2), 180, 360, $white);
        // Bottom arc of S
        imagearc($img, (int)$cx, (int)($cy + $r * 0.45), (int)($r * 1.6), (int)($r * 1.2), 0, 180, $white);

        // Fill with thicker lines
        for ($t = 0; $t < max(2, (int)($size * 0.04)); $t++) {
            imagearc($img, (int)$cx, (int)($cy - $r * 0.45 + $t), (int)($r * 1.6), (int)($r * 1.2), 180, 360, $white);
            imagearc($img, (int)$cx, (int)($cy + $r * 0.45 - $t), (int)($r * 1.6), (int)($r * 1.2), 0, 180, $white);
        }
    } else {
        // Small icon — just use built-in font
        $x = (int)(($size - $charWidth) / 2);
        $y = (int)(($size - $charHeight) / 2);
        imagestring($img, $fontScale, $x, $y, 'S', $white);
    }

    imagepng($img, "{$outputDir}/icon-{$size}x{$size}.png");
    imagedestroy($img);
    echo "Created icon-{$size}x{$size}.png\n";
}

// Generate shortcut icons
foreach ($shortcuts as $name) {
    $img = imagecreatetruecolor($shortcutSize, $shortcutSize);
    $bg = imagecolorallocate($img, 0x1A, 0x73, 0xE8);
    imagefilledrectangle($img, 0, 0, $shortcutSize - 1, $shortcutSize - 1, $bg);
    $white = imagecolorallocate($img, 255, 255, 255);

    $label = match($name) {
        'shortcut-search' => '?',
        'shortcut-applications' => 'A',
        'shortcut-messages' => 'M',
        default => 'S',
    };

    $fontScale = 3;
    $x = (int)(($shortcutSize - imagefontwidth($fontScale)) / 2);
    $y = (int)(($shortcutSize - imagefontheight($fontScale)) / 2);
    imagestring($img, $fontScale, $x, $y, $label, $white);

    imagepng($img, "{$outputDir}/{$name}.png");
    imagedestroy($img);
    echo "Created {$name}.png\n";
}

echo "\nAll PWA icons generated successfully!\n";

function generateSvg(int $size, string $color): string
{
    $fontSize = $size * 0.5;
    return <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
      <rect width="{$size}" height="{$size}" rx="{$size}.15}" fill="{$color}"/>
      <text x="50%" y="55%" text-anchor="middle" dominant-baseline="central" fill="white"
            font-family="Inter, system-ui, sans-serif" font-weight="700" font-size="{$fontSize}">S</text>
    </svg>
    SVG;
}
