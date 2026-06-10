<?php

namespace App\Domain\Purchasing\Models;

use App\Domain\Catalog\Models\Product;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLine extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'purchase_id', 'product_id', 'batch_no', 'expiry_date', 'manufactured_at',
        'quantity', 'sell_unit', 'conversion_factor', 'quantity_base',
        'unit_cost', 'sale_price', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufactured_at' => 'date',
            'sale_price' => 'decimal:4',
            'quantity' => 'decimal:4',
            'conversion_factor' => 'decimal:4',
            'quantity_base' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
