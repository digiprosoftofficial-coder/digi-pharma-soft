<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogTemplateItem::class)->orderBy('sort_order')->orderBy('name');
    }
}
