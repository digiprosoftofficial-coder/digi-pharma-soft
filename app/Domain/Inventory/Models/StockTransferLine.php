<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Catalog\Models\ProductBatch;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferLine extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'stock_transfer_id', 'from_batch_id', 'to_batch_id', 'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function fromBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'from_batch_id');
    }

    public function toBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'to_batch_id');
    }
}
