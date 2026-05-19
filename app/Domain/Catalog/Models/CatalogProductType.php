<?php

namespace App\Domain\Catalog\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogProductType extends TenantModel
{
    protected $table = 'product_types';

    protected $fillable = ['tenant_id', 'name', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type', 'slug');
    }
}
