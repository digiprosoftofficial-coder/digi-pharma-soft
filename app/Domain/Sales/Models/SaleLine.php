<?php

namespace App\Domain\Sales\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLine extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'sale_id', 'product_id', 'product_batch_id',
        'quantity', 'sell_unit', 'conversion_factor', 'quantity_base',
        'unit_price', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'conversion_factor' => 'decimal:4',
            'quantity_base' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }
}
