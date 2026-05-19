<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Domain\Catalog\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductTypeService
{
    /**
     * @param  array{name:string,slug?:string|null,sort_order?:int}  $data
     */
    public function create(array $data): CatalogProductType
    {
        $slug = $this->resolveSlug($data['name'], $data['slug'] ?? null);

        return CatalogProductType::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    /**
     * @param  array{name?:string,slug?:string|null,sort_order?:int}  $data
     */
    public function update(CatalogProductType $type, array $data): CatalogProductType
    {
        $name = $data['name'] ?? $type->name;
        $oldSlug = $type->slug;
        $slug = isset($data['slug']) || isset($data['name'])
            ? $this->resolveSlug($name, $data['slug'] ?? $type->slug, $type->getKey())
            : $type->slug;

        $type->update([
            'name' => $name,
            'slug' => $slug,
            'sort_order' => $data['sort_order'] ?? $type->sort_order,
        ]);

        if ($slug !== $oldSlug) {
            Product::query()->where('product_type', $oldSlug)->update(['product_type' => $slug]);
        }

        return $type->fresh();
    }

    public function delete(CatalogProductType $type): void
    {
        if (Product::query()->where('product_type', $type->slug)->exists()) {
            throw ValidationException::withMessages([
                'product_type' => [__('catalog.product_type_has_products')],
            ]);
        }

        $type->delete();
    }

    private function resolveSlug(string $name, ?string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        if ($base === '') {
            $base = 'type';
        }

        $candidate = $base;
        $suffix = 1;

        while (CatalogProductType::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
