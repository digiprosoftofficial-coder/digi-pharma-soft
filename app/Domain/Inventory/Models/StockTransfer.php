<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Tenant\Models\Branch;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'from_branch_id', 'to_branch_id', 'transfer_no', 'transferred_at', 'notes', 'status',
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

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }
}
