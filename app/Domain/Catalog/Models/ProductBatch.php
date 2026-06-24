<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Tenant\Models\Branch;
use App\Support\Models\TenantModel;
use App\Support\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends TenantModel
{
    use BranchScoped;

    protected $fillable = [
        'tenant_id', 'branch_id', 'product_id', 'storage_location_id', 'batch_no', 'expiry_date', 'manufactured_at',
        'quantity_on_hand', 'purchase_unit_cost', 'sale_price', 'markup_percent',
        'pack_sell_unit', 'pack_conversion_factor',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufactured_at' => 'date',
            'quantity_on_hand' => 'decimal:4',
            'purchase_unit_cost' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'markup_percent' => 'decimal:2',
            'pack_conversion_factor' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }
}
