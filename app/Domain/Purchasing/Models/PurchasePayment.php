<?php

namespace App\Domain\Purchasing\Models;

use App\Domain\Tenant\Models\Branch;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'paying_branch_id', 'purchase_id', 'method', 'amount', 'paid_at', 'reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'paid_at' => 'date',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function payingBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'paying_branch_id');
    }
}
