<?php

namespace App\Http\Resources\Catalog;

use App\Support\Catalog\EffectiveStorageLocation;
use App\Support\Catalog\ProductImageStorage;
use App\Support\Catalog\ProductStockCalculator;
use App\Support\Catalog\ProductTypeIconResolver;
use App\Support\Tenant\TenantFeatures;
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
            'generic_name' => $this->generic_name,
            'strength' => $this->strength,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'product_type' => $this->product_type ?? 'other',
            'product_type_icon_url' => ProductTypeIconResolver::urlForSlug($this->product_type ?? 'other'),
            'base_unit' => $this->base_unit ?? 'strip',
            'pieces_per_strip' => $this->pieces_per_strip !== null
                ? ProductStockCalculator::formatQuantity((float) $this->pieces_per_strip)
                : null,
            'strips_per_box' => $this->strips_per_box !== null
                ? ProductStockCalculator::formatQuantity((float) $this->strips_per_box)
                : null,
            'boxes_per_carton' => $this->boxes_per_carton !== null
                ? ProductStockCalculator::formatQuantity((float) $this->boxes_per_carton)
                : null,
            'unit' => $this->unit,
            'stock_pieces' => $this->when(
                $this->resolveBaseStockForResource() !== null,
                fn () => $this->formatStockPieces(),
            ),
            'purchase_price' => (string) $this->purchase_price,
            'sale_price' => (string) $this->sale_price,
            'default_markup_percent' => TenantFeatures::markupPricingEnabled(tenant()) && $this->default_markup_percent !== null
                ? (string) $this->default_markup_percent
                : null,
            'wholesale_price' => $this->wholesale_price !== null ? (string) $this->wholesale_price : null,
            'vat_percent' => $this->vat_percent !== null ? (string) $this->vat_percent : null,
            'short_description' => $this->short_description,
            'image_url' => ProductImageStorage::url($this->image_path),
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
            'stock_on_hand' => ProductStockCalculator::formatQuantity(
                (float) ($this->stock_on_hand ?? $this->stockOnHandFromBatches()),
            ),
            'purchased_quantity' => ProductStockCalculator::formatQuantity((float) ($this->purchased_quantity ?? 0)),
            'units' => $this->whenLoaded('units', fn () => $this->units->map(fn ($u) => [
                'sell_unit' => $u->sell_unit,
                'conversion_factor' => ProductStockCalculator::formatQuantity((float) $u->conversion_factor),
                'purchase_price' => (string) $u->purchase_price,
                'sale_price' => (string) $u->sale_price,
                'is_default' => (bool) $u->is_default,
            ])->values()->all()),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'manufacturer' => $this->whenLoaded('manufacturer', fn () => ['id' => $this->manufacturer->id, 'name' => $this->manufacturer->name]),
            'storage_location_id' => $this->storage_location_id,
            'storage_location' => $this->whenLoaded('storageLocation', fn () => $this->storageLocation ? [
                'id' => $this->storageLocation->id,
                'name' => $this->storageLocation->name,
                'code' => $this->storageLocation->code,
            ] : null),
            'effective_storage_location' => EffectiveStorageLocation::forProduct($this->resource),
            'batches' => $this->whenLoaded('batches', fn () => ProductBatchResource::collection($this->batches)),
            'last_purchase' => $this->when(
                $this->getAttribute('last_purchase') !== null,
                fn () => $this->getAttribute('last_purchase'),
            ),
        ];
    }

    private function stockOnHandFromBatches(): string
    {
        if (! $this->relationLoaded('batches')) {
            return '0';
        }

        $total = $this->batches->sum(fn ($batch) => (float) $batch->quantity_on_hand);

        return (string) $total;
    }

    private function resolveBaseStockForResource(): ?float
    {
        if (isset($this->stock_on_hand)) {
            return (float) $this->stock_on_hand;
        }

        if ($this->relationLoaded('batches')) {
            return (float) $this->batches->sum(fn ($batch) => (float) $batch->quantity_on_hand);
        }

        return null;
    }

    private function formatStockPieces(): ?string
    {
        $baseStock = $this->resolveBaseStockForResource();
        if ($baseStock === null) {
            return null;
        }

        $pieces = ProductStockCalculator::totalPieces($this->resource, $baseStock);
        if ($pieces === null) {
            return null;
        }

        return ProductStockCalculator::formatQuantity($pieces);
    }
}
