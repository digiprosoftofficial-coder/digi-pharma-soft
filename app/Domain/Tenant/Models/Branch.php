<?php

namespace App\Domain\Tenant\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'name', 'code', 'address', 'phone', 'is_active', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
