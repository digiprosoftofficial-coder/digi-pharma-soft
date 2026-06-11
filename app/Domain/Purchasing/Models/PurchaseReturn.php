<?php

namespace App\Domain\Purchasing\Models;

use App\Support\Models\TenantModel;
use App\Support\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends TenantModel
{
    use BranchScoped;

    protected $fillable = [
        'tenant_id', 'branch_id', 'purchase_id', 'supplier_id', 'reference_no', 'returned_at',
        'total_credit', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'total_credit' => 'decimal:4',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class);
    }
}
