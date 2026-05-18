<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CategoryService
{
    /**
     * @param  array{name:string,slug?:string|null}  $data
     */
    public function create(array $data): Category
    {
        $slug = $this->resolveSlug($data['name'], $data['slug'] ?? null);

        return Category::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
        ]);
    }

    /**
     * @param  array{name?:string,slug?:string|null}  $data
     */
    public function update(Category $category, array $data): Category
    {
        $name = $data['name'] ?? $category->name;
        $slug = isset($data['slug']) || isset($data['name'])
            ? $this->resolveSlug($name, $data['slug'] ?? $category->slug, $category->getKey())
            : $category->slug;

        $category->update([
            'name' => $name,
            'slug' => $slug,
        ]);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => [__('catalog.category_has_products')],
            ]);
        }

        $category->delete();
    }

    private function resolveSlug(string $name, ?string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        if ($base === '') {
            $base = 'category';
        }

        $candidate = $base;
        $suffix = 1;

        while (Category::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
