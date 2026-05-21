<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\StorageLocation;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StorageLocationService
{
    /**
     * @param  array{name: string, code?: string|null, sort_order?: int, is_active?: bool, notes?: string|null}  $data
     */
    public function create(array $data): StorageLocation
    {
        return StorageLocation::query()->create([
            'name' => $data['name'],
            'code' => $this->normalizeCode($data['code'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $data['is_active'] ?? true,
            'notes' => $this->normalizeNotes($data['notes'] ?? null),
        ]);
    }

    /**
     * @param  array{name?: string, code?: string|null, sort_order?: int, is_active?: bool, notes?: string|null}  $data
     */
    public function update(StorageLocation $location, array $data): StorageLocation
    {
        $location->update([
            'name' => $data['name'] ?? $location->name,
            'code' => array_key_exists('code', $data)
                ? $this->normalizeCode($data['code'])
                : $location->code,
            'sort_order' => array_key_exists('sort_order', $data)
                ? (int) $data['sort_order']
                : $location->sort_order,
            'is_active' => $data['is_active'] ?? $location->is_active,
            'notes' => array_key_exists('notes', $data)
                ? $this->normalizeNotes($data['notes'])
                : $location->notes,
        ]);

        return $location->fresh();
    }

    public function delete(StorageLocation $location): void
    {
        if ($location->products()->exists() || $location->batches()->exists()) {
            throw ValidationException::withMessages([
                'storage_location' => [__('catalog.storage_location_in_use')],
            ]);
        }

        $location->delete();
    }

    private function normalizeCode(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        return Str::upper(trim($code));
    }

    private function normalizeNotes(?string $notes): ?string
    {
        if ($notes === null || trim($notes) === '') {
            return null;
        }

        return trim($notes);
    }
}
