<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ProductRepository
{
    public function paginateForTenant(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'manufacturer', 'batches'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function find(int $id): ?Product
    {
        return Product::query()->with(['category', 'manufacturer', 'batches'])->find($id);
    }

    public function searchByTerm(string $term, int $limit = 25): Collection
    {
        $q = Product::query()->with([
            'units',
            'batches' => fn ($b) => $b->orderBy('expiry_date'),
        ]);

        $q->where(function ($w) use ($term) {
            $w->where('name', 'like', '%'.$term.'%')
                ->orWhere('sku', 'like', '%'.$term.'%')
                ->orWhere('barcode', $term);
        });

        return $q->limit($limit)->get();
    }

    public function store(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    public function update(Product $product, array $attributes): bool
    {
        return $product->update($attributes);
    }

    public function delete(Product $product): ?bool
    {
        return $product->delete();
    }
}
