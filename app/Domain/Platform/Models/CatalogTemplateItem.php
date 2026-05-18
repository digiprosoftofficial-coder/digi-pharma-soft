<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogTemplateItem extends Model
{
    protected $fillable = [
        'catalog_template_id',
        'name',
        'sku',
        'barcode',
        'unit',
        'product_type',
        'base_unit',
        'generic_name',
        'manufacturer_name',
        'purchase_price',
        'sale_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CatalogTemplate::class, 'catalog_template_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(CatalogTemplateItemUnit::class)->orderBy('sort_order')->orderBy('sell_unit');
    }
}
