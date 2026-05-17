<?php

namespace App\Domain\Catalog\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends TenantModel
{
    protected $fillable = ['tenant_id', 'name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
