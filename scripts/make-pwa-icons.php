<?php

$source = $argv[1] ?? null;
$outDir = $argv[2] ?? __DIR__.'/../public/icons';

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

$sizes = [
    'icon-512.png' => 512,
    'icon-192.png' => 192,
    'apple-touch-icon.png' => 180,
];

foreach ($sizes as $name => $size) {
    $scaled = imagescale($src, $size, $size);
    imagepng($scaled, $outDir.DIRECTORY_SEPARATOR.$name);
    imagedestroy($scaled);
}

imagedestroy($src);
echo "Wrote icons to {$outDir}\n";
