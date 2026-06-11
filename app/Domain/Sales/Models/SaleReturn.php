<?php

namespace App\Domain\Sales\Models;

use App\Support\Models\TenantModel;
use App\Support\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends TenantModel
{
    use BranchScoped;

    protected $fillable = [
        'tenant_id', 'branch_id', 'sale_id', 'reference_no', 'returned_at',
        'total_refund', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'total_refund' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleReturnLine::class);
    }
}
