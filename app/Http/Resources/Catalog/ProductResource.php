<?php

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Catalog\Models\Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'product_type' => $this->product_type ?? 'other',
            'base_unit' => $this->base_unit ?? 'strip',
            'unit' => $this->unit,
            'purchase_price' => (string) $this->purchase_price,
            'sale_price' => (string) $this->sale_price,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
            'units' => $this->whenLoaded('units', fn () => $this->units->map(fn ($u) => [
                'sell_unit' => $u->sell_unit,
                'conversion_factor' => (string) $u->conversion_factor,
                'purchase_price' => (string) $u->purchase_price,
                'sale_price' => (string) $u->sale_price,
                'is_default' => (bool) $u->is_default,
            ])->values()->all()),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'manufacturer' => $this->whenLoaded('manufacturer', fn () => ['id' => $this->manufacturer->id, 'name' => $this->manufacturer->name]),
            'batches' => $this->whenLoaded('batches', fn () => ProductBatchResource::collection($this->batches)),
        ];
    }
}
