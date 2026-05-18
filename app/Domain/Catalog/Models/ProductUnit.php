<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'sell_unit',
        'conversion_factor',
        'purchase_price',
        'sale_price',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'purchase_price' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
