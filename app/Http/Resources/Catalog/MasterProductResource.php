<?php

namespace App\Http\Resources\Catalog;

use App\Support\Catalog\ProductStockCalculator;
use App\Support\Catalog\ProductTypeIconResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Catalog\Models\MasterProduct */
class MasterProductResource extends JsonResource
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
            'manufacturer_name' => $this->manufacturer_name,
            'product_type' => $this->product_type ?? 'other',
            'product_type_icon_url' => ProductTypeIconResolver::urlForSlug($this->product_type ?? 'other'),
            'drug_class' => $this->drug_class,
            'base_unit' => $this->base_unit ?? 'strip',
            'pieces_per_strip' => $this->pieces_per_strip !== null
                ? ProductStockCalculator::formatQuantity((float) $this->pieces_per_strip)
                : null,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'mrp' => (string) $this->mrp,
            'default_purchase_price' => (string) $this->default_purchase_price,
            'is_activated' => (bool) $this->getAttribute('is_activated'),
            'tenant_product_id' => $this->getAttribute('tenant_product_id'),
        ];
    }
}
