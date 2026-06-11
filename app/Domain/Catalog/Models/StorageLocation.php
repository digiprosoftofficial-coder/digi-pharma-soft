<?php

namespace App\Domain\Catalog\Models;

use App\Support\Models\TenantModel;
use App\Support\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends TenantModel
{
    use BranchScoped;

    protected $fillable = [
        'tenant_id', 'branch_id',
        'name',
        'code',
        'sort_order',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }
}
