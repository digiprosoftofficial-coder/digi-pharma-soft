<?php

namespace App\Domain\Hr\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends TenantModel
{
    protected $fillable = ['tenant_id', 'period', 'status', 'total_amount', 'finalized_at'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:4',
            'finalized_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }
}
