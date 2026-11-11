<?php

$source = $argv[1] ?? null;
$outDir = $argv[2] ?? __DIR__.'/../public/icons';
$publicDir = dirname($outDir);

if (! $source || ! is_file($source)) {
    fwrite(STDERR, "Usage: php make-pwa-icons.php <source.png> [outDir]\n");
    exit(1);
}

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required\n");
    exit(1);
}

if (! is_dir($outDir) && ! mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
    fwrite(STDERR, "Cannot create {$outDir}\n");
    exit(1);
}

$src = imagecreatefrompng($source);
if ($src === false) {
    fwrite(STDERR, "Cannot read {$source}\n");
    exit(1);
}

imagealphablending($src, true);
imagesavealpha($src, true);

$fit = static function ($src, int $size, array $bg, float $padRatio) {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $color = imagecolorallocatealpha($canvas, $bg[0], $bg[1], $bg[2], $bg[3]);
    imagefilledrectangle($canvas, 0, 0, $size, $size, $color);
    imagealphablending($canvas, true);

    $inner = (int) round($size * (1 - (2 * $padRatio)));
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    $scale = min($inner / $srcW, $inner / $srcH);
    $dstW = max(1, (int) round($srcW * $scale));
    $dstH = max(1, (int) round($srcH * $scale));
    $x = (int) floor(($size - $dstW) / 2);
    $y = (int) floor(($size - $dstH) / 2);
    imagecopyresampled($canvas, $src, $x, $y, 0, 0, $dstW, $dstH, $srcW, $srcH);

    return $canvas;
};

$targets = [
    [$outDir.'/icon-512.png', 512, [248, 248, 244, 0], 0.14],
    [$outDir.'/icon-192.png', 192, [248, 248, 244, 0], 0.14],
    [$outDir.'/apple-touch-icon.png', 180, [248, 248, 244, 0], 0.14],
    [$outDir.'/icon-512-maskable.png', 512, [18, 61, 50, 0], 0.18],
    [$publicDir.'/favicon-16x16.png', 16, [0, 0, 0, 127], 0.08],
    [$publicDir.'/favicon-32x32.png', 32, [0, 0, 0, 127], 0.08],
    [$publicDir.'/favicon.png', 48, [0, 0, 0, 127], 0.08],
];

foreach ($targets as [$path, $size, $bg, $pad]) {
    $img = $fit($src, $size, $bg, $pad);
    imagepng($img, $path);
    imagedestroy($img);
}

imagedestroy($src);
echo "Wrote icons to {$outDir} and favicons to {$publicDir}\n";
