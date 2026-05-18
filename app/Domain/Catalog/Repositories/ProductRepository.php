<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ProductRepository
{
    /**
     * @param  array{q?:string,product_type?:string,is_active?:string}  $filters
     */
    public function paginateForTenant(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'manufacturer', 'batches', 'units'])
            ->orderByDesc('id');

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Product
    {
        return Product::query()->with(['category', 'manufacturer', 'batches', 'units'])->find($id);
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
