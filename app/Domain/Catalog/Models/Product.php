<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Purchasing\Models\PurchaseLine;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'category_id', 'manufacturer_id', 'storage_location_id', 'name', 'generic_name', 'sku', 'barcode',
        'product_type', 'base_unit', 'pieces_per_strip', 'strips_per_box', 'boxes_per_carton', 'unit',
        'purchase_price', 'sale_price', 'wholesale_price', 'vat_percent', 'short_description', 'image_path',
        'min_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pieces_per_strip' => 'decimal:4',
            'strips_per_box' => 'decimal:4',
            'boxes_per_carton' => 'decimal:4',
            'purchase_price' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'wholesale_price' => 'decimal:4',
            'vat_percent' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class)->orderBy('sort_order')->orderBy('sell_unit');
    }

    public function purchaseLines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function defaultUnit(): ?ProductUnit
    {
        return $this->units->firstWhere('is_default', true)
            ?? $this->units->first();
    }
}
