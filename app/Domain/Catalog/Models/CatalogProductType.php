<?php

namespace App\Domain\Catalog\Models;

use App\Support\Catalog\ProductTypeIconResolver;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogProductType extends TenantModel
{
    protected $table = 'product_types';

    protected $fillable = ['tenant_id', 'name', 'slug', 'sort_order', 'icon_path'];

    protected $appends = ['icon_url', 'uses_custom_icon'];

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

    public function getIconUrlAttribute(): ?string
    {
        return ProductTypeIconResolver::urlForTenantType($this);
    }

    public function getUsesCustomIconAttribute(): bool
    {
        return $this->icon_path !== null && $this->icon_path !== '';
    }
}
