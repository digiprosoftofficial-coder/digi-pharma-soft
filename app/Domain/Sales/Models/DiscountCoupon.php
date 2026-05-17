<?php

namespace App\Domain\Sales\Models;

use App\Support\Models\TenantModel;

class DiscountCoupon extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'code', 'percent_off', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percent_off' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
