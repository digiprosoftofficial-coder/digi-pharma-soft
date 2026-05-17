<?php

namespace App\Domain\Sales\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends TenantModel
{
    protected $fillable = ['tenant_id', 'sale_id', 'method', 'amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
