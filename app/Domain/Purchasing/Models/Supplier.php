<?php

namespace App\Domain\Purchasing\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends TenantModel
{
    protected $fillable = ['tenant_id', 'name', 'phone', 'email', 'balance_due'];

    protected function casts(): array
    {
        return [
            'balance_due' => 'decimal:4',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
