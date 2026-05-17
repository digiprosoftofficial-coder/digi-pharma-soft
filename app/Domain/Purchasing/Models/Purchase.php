<?php

namespace App\Domain\Purchasing\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'supplier_id', 'invoice_no', 'purchased_at',
        'subtotal', 'tax', 'discount', 'total', 'paid', 'due', 'status',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'subtotal' => 'decimal:4',
            'tax' => 'decimal:4',
            'discount' => 'decimal:4',
            'total' => 'decimal:4',
            'paid' => 'decimal:4',
            'due' => 'decimal:4',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }
}
