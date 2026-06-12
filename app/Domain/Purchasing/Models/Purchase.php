<?php

namespace App\Domain\Purchasing\Models;

use App\Domain\Tenant\Models\Branch;
use App\Support\Models\TenantModel;
use App\Support\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends TenantModel
{
    use BranchScoped;

    protected $fillable = [
        'tenant_id', 'branch_id', 'supplier_id', 'invoice_no', 'purchased_at',
        'subtotal', 'tax', 'discount', 'total', 'paid', 'due', 'status', 'notes',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }
}
