<?php

namespace App\Domain\Purchasing\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'purchase_id', 'method', 'amount', 'paid_at', 'reference', 'notes',
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
}
