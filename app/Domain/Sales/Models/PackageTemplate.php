<?php

namespace App\Domain\Sales\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageTemplate extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
        'discount_percent',
        'fixed_price',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'discount_percent' => 'decimal:2',
            'fixed_price' => 'decimal:4',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageTemplateItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
