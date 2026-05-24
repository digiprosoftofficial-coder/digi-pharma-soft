<?php

namespace App\Support\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PlatformProductTypeIconStorage
{
    public static function store(UploadedFile $file, string $slug): string
    {
        $extension = $file->guessExtension() ?: 'png';
        $filename = Str::slug($slug).'.'.$extension;

        return $file->storeAs('platform/product-type-icons', $filename, 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
