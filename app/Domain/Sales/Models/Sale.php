<?php

namespace App\Domain\Sales\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'customer_id', 'invoice_no', 'sold_at',
        'subtotal', 'discount', 'tax', 'total', 'paid', 'amount_tendered', 'change_returned', 'due', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'discount' => 'decimal:4',
            'tax' => 'decimal:4',
            'total' => 'decimal:4',
            'paid' => 'decimal:4',
            'amount_tendered' => 'decimal:4',
            'change_returned' => 'decimal:4',
            'due' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }
}
