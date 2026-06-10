<?php

namespace App\Support\Purchasing;

use App\Domain\Purchasing\Models\PurchaseLine;
use Illuminate\Support\Collection;

final class LastPurchasePriceLookup
{
    /**
     * @param  list<int>  $productIds
     * @return array<int, array{unit_cost: string, sell_unit: string, purchased_at: ?string}>
     */
    public function latestForProducts(array $productIds, ?string $sellUnit = null): array
    {
        if ($productIds === []) {
            return [];
        }

        $query = PurchaseLine::query()
            ->select('purchase_lines.*')
            ->joinSub(
                PurchaseLine::query()
                    ->selectRaw('product_id, MAX(id) as latest_id')
                    ->whereIn('product_id', $productIds)
                    ->when($sellUnit !== null, fn ($q) => $q->where('sell_unit', $sellUnit))
                    ->groupBy('product_id'),
                'latest',
                fn ($join) => $join->on('purchase_lines.id', '=', 'latest.latest_id'),
            )
            ->with('purchase:id,purchased_at');

        return $query->get()
            ->mapWithKeys(fn (PurchaseLine $line) => [
                $line->product_id => [
                    'unit_cost' => (string) $line->unit_cost,
                    'sell_unit' => (string) $line->sell_unit,
                    'purchased_at' => $line->purchase?->purchased_at?->toDateString(),
                ],
            ])
            ->all();
    }

    /**
     * @return array{unit_cost: string, sell_unit: string, purchased_at: ?string}|null
     */
    public function latestForProduct(int $productId, ?string $sellUnit = null): ?array
    {
        $line = PurchaseLine::query()
            ->where('product_id', $productId)
            ->when($sellUnit !== null, fn ($q) => $q->where('sell_unit', $sellUnit))
            ->with('purchase:id,purchased_at')
            ->orderByDesc('id')
            ->first();

        if (! $line) {
            return null;
        }

        return [
            'unit_cost' => (string) $line->unit_cost,
            'sell_unit' => (string) $line->sell_unit,
            'purchased_at' => $line->purchase?->purchased_at?->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, \App\Domain\Catalog\Models\Product>  $products
     */
    public function attachToProducts(Collection $products, ?string $sellUnit = null): void
    {
        $lookup = $this->latestForProducts($products->pluck('id')->all(), $sellUnit);

        $products->each(function ($product) use ($lookup) {
            $product->setAttribute('last_purchase', $lookup[$product->id] ?? null);
        });
    }
}
