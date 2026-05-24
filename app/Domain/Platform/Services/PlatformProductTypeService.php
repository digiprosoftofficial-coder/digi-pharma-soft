<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Models\PlatformProductType;
use App\Support\Catalog\PlatformProductTypeIconStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class PlatformProductTypeService
{
    /**
     * @param  array{name:string,slug?:string|null,sort_order?:int,is_active?:bool}  $data
     */
    public function create(array $data, ?UploadedFile $icon = null): PlatformProductType
    {
        $slug = $this->normalizeSlug($data['slug'] ?? $data['name']);
        $iconPath = $icon ? PlatformProductTypeIconStorage::store($icon, $slug) : null;

        return PlatformProductType::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'icon_path' => $iconPath,
        ]);
    }

    /**
     * @param  array{name?:string,slug?:string|null,sort_order?:int,is_active?:bool,remove_icon?:bool}  $data
     */
    public function update(PlatformProductType $type, array $data, ?UploadedFile $icon = null): PlatformProductType
    {
        $slug = isset($data['slug']) || isset($data['name'])
            ? $this->normalizeSlug($data['slug'] ?? $data['name'] ?? $type->name)
            : $type->slug;

        $iconPath = $type->icon_path;
        if (! empty($data['remove_icon'])) {
            PlatformProductTypeIconStorage::delete($iconPath);
            $iconPath = null;
        }
        if ($icon) {
            PlatformProductTypeIconStorage::delete($iconPath);
            $iconPath = PlatformProductTypeIconStorage::store($icon, $slug);
        }

        $type->update([
            'name' => $data['name'] ?? $type->name,
            'slug' => $slug,
            'sort_order' => $data['sort_order'] ?? $type->sort_order,
            'is_active' => $data['is_active'] ?? $type->is_active,
            'icon_path' => $iconPath,
        ]);

        return $type->fresh();
    }

    public function delete(PlatformProductType $type): void
    {
        PlatformProductTypeIconStorage::delete($type->icon_path);
        $type->delete();
    }

    private function normalizeSlug(string $value): string
    {
        $slug = Str::slug($value);

        return $slug !== '' ? $slug : 'type';
    }
}
