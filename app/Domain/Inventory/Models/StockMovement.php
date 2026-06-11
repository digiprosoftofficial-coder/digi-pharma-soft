<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Catalog\Models\ProductBatch;
use App\Support\Models\TenantModel;
use App\Support\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends TenantModel
{
    use BranchScoped;

    protected $fillable = [
        'tenant_id', 'branch_id', 'product_batch_id', 'type', 'reference_type', 'reference_id',
        'quantity_delta', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }
}
