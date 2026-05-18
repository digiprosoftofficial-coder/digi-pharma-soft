<?php

namespace App\Support\Platform;

use App\Domain\Platform\Models\PlatformAnnouncement;

final class PlatformAnnouncementService
{
    /**
     * @return array{id: int, title: string, body: string, severity: string}|null
     */
    public static function activeBanner(): ?array
    {
        $row = PlatformAnnouncement::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('starts_at')
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => $row->getKey(),
            'title' => $row->title,
            'body' => $row->body,
            'severity' => $row->severity,
        ];
    }
}
