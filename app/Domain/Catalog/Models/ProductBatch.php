<?php

namespace App\Domain\Catalog\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'product_id', 'storage_location_id', 'batch_no', 'expiry_date',
        'quantity_on_hand', 'purchase_unit_cost',
        'pack_sell_unit', 'pack_conversion_factor',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'quantity_on_hand' => 'decimal:4',
            'purchase_unit_cost' => 'decimal:4',
            'pack_conversion_factor' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }
}
