<?php

namespace App\Domain\Sales\Models;

use App\Domain\Catalog\Models\Product;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageTemplateItem extends TenantModel
{
    protected $fillable = [
        'tenant_id',
        'package_template_id',
        'product_id',
        'sell_unit',
        'quantity',
        'unit_price_override',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price_override' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function packageTemplate(): BelongsTo
    {
        return $this->belongsTo(PackageTemplate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
