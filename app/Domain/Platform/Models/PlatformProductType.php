<?php

namespace App\Domain\Platform\Models;

use App\Support\Catalog\PlatformProductTypeIconStorage;
use Illuminate\Database\Eloquent\Model;

class PlatformProductType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getIconUrlAttribute(): ?string
    {
        return PlatformProductTypeIconStorage::url($this->icon_path);
    }
}
