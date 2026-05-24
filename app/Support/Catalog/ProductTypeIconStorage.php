<?php

namespace App\Support\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProductTypeIconStorage
{
    public static function store(UploadedFile $file, ?int $tenantId = null, ?string $slug = null): string
    {
        $tenantId ??= tenant_id();
        $extension = $file->guessExtension() ?: 'png';
        $basename = $slug ? Str::slug($slug) : Str::uuid()->toString();

        return $file->storeAs("product-types/{$tenantId}", "{$basename}.{$extension}", 'public');
    }

    public static function copyFromPath(string $sourcePath, int $tenantId, string $slug): ?string
    {
        if (! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'png';
        $dest = "product-types/{$tenantId}/".Str::slug($slug).'.'.$extension;

        if (Storage::disk('public')->exists($dest)) {
            Storage::disk('public')->delete($dest);
        }

        Storage::disk('public')->copy($sourcePath, $dest);

        return $dest;
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
