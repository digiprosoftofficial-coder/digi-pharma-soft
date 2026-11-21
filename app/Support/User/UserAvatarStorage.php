<?php

namespace App\Support\User;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UserAvatarStorage
{
    public static function store(UploadedFile $file, int $userId): string
    {
        return $file->store("avatars/{$userId}", 'public');
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
