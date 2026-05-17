<?php

namespace App\Domain\Accounting\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends TenantModel
{
    protected $fillable = ['tenant_id', 'code', 'name', 'type'];

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
