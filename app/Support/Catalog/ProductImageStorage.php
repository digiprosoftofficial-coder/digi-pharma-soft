<?php

namespace App\Support\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ProductImageStorage
{
    public static function store(UploadedFile $file, ?int $tenantId = null): string
    {
        $tenantId ??= tenant_id();

        return $file->store("products/{$tenantId}", 'public');
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
