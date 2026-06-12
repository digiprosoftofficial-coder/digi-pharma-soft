<?php

namespace App\Domain\Sales\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantModel
{
    protected $fillable = ['tenant_id', 'name', 'phone', 'email', 'address', 'loyalty_points', 'balance_due'];

    protected function casts(): array
    {
        return [
            'loyalty_points' => 'decimal:4',
            'balance_due' => 'decimal:4',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
