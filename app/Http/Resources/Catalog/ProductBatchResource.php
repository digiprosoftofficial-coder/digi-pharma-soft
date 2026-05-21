<?php

namespace App\Http\Resources\Catalog;

use App\Support\Catalog\EffectiveStorageLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Catalog\Models\ProductBatch */
class ProductBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_no' => $this->batch_no,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'quantity_on_hand' => (string) $this->quantity_on_hand,
            'purchase_unit_cost' => (string) $this->purchase_unit_cost,
            'pack_sell_unit' => $this->pack_sell_unit,
            'pack_conversion_factor' => $this->pack_conversion_factor !== null
                ? (string) $this->pack_conversion_factor
                : null,
            'storage_location_id' => $this->storage_location_id,
            'storage_location' => $this->whenLoaded('storageLocation', fn () => $this->storageLocation ? [
                'id' => $this->storageLocation->id,
                'name' => $this->storageLocation->name,
                'code' => $this->storageLocation->code,
            ] : null),
            'effective_storage_location' => EffectiveStorageLocation::forBatch($this->resource),
        ];
    }
}
