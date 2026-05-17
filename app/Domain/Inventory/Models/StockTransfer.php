<?php

namespace App\Domain\Inventory\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'transfer_no', 'transferred_at', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
    }
}
