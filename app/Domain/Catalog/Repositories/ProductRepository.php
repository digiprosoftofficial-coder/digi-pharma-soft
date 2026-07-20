<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ProductRepository
{
    /**
     * @param  array{q?:string,product_type?:string,category_id?:string,is_active?:string,storage_location_id?:string,per_page?:int}  $filters
     */
    public function paginateForTenant(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'manufacturer', 'storageLocation', 'units'])
            ->withSum('batches as stock_on_hand', 'quantity_on_hand')
            ->withSum('purchaseLines as purchased_quantity', 'quantity_base')
            ->orderByDesc('id');

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', '%'.$term.'%')
                    ->orWhere('generic_name', 'like', '%'.$term.'%')
                    ->orWhere('strength', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['storage_location_id'])) {
            $query->where('storage_location_id', (int) $filters['storage_location_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Product
    {
        return Product::query()->with(['category', 'manufacturer', 'storageLocation', 'batches.storageLocation', 'units'])->find($id);
    }

    public function searchByTerm(string $term, int $limit = 25): Collection
    {
        $q = Product::query()->with([
            'units',
            'storageLocation',
            'batches' => fn ($b) => $b->with('storageLocation')
                ->where('quantity_on_hand', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', now()->toDateString());
                })
                ->orderByRaw('expiry_date IS NULL')
                ->orderBy('expiry_date')
                ->orderBy('id'),
        ]);

        $q->where(function ($w) use ($term) {
            $w->where('name', 'like', '%'.$term.'%')
                ->orWhere('generic_name', 'like', '%'.$term.'%')
                ->orWhere('strength', 'like', '%'.$term.'%')
                ->orWhere('sku', 'like', '%'.$term.'%')
                ->orWhere('barcode', $term);
        });

        return $q->limit($limit)->get();
    }

    /**
     * @param  list<int>  $ids
     */
    public function findManyForPurchase(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0,
        )));

        if ($ids === []) {
            return new Collection;
        }

        $products = Product::query()
            ->with([
                'units',
                'storageLocation',
                'batches' => fn ($b) => $b->with('storageLocation')
                    ->where('quantity_on_hand', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>=', now()->toDateString());
                    })
                    ->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('id'),
            ])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = [];
        foreach ($ids as $id) {
            $product = $products->get($id);
            if ($product !== null) {
                $ordered[] = $product;
            }
        }

        return new Collection($ordered);
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
