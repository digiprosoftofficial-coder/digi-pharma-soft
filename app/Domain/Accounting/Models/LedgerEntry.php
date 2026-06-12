<?php

namespace App\Domain\Accounting\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'ledger_account_id', 'reference_type', 'reference_id',
        'amount', 'direction', 'memo', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
