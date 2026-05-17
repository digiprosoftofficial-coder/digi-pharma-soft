<?php

namespace App\Domain\Catalog\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends TenantModel
{
    protected $fillable = ['tenant_id', 'name', 'slug'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
