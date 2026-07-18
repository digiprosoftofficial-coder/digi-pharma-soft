<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Central, shared drug catalog entry (not tenant-scoped).
 * Pharmacies reference these and "activate" the ones they actually stock.
 */
class MasterProduct extends Model
{
    protected $fillable = [
        'name',
        'generic_name',
        'strength',
        'manufacturer_name',
        'product_type',
        'drug_class',
        'base_unit',
        'pieces_per_strip',
        'strips_per_box',
        'boxes_per_carton',
        'sku',
        'barcode',
        'mrp',
        'default_purchase_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pieces_per_strip' => 'decimal:4',
            'strips_per_box' => 'decimal:4',
            'boxes_per_carton' => 'decimal:4',
            'mrp' => 'decimal:4',
            'default_purchase_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(MasterProductUnit::class)->orderBy('sort_order')->orderBy('sell_unit');
    }
}
