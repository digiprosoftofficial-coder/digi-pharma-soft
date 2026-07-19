<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterProductSuggestion extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_MERGED = 'merged';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'suggested_by_user_id',
        'name',
        'generic_name',
        'strength',
        'manufacturer_name',
        'product_type',
        'drug_class',
        'base_unit',
        'pieces_per_strip',
        'strips_per_box',
        'boxes_per_carton',
        'sku',
        'barcode',
        'mrp',
        'default_purchase_price',
        'status',
        'master_product_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'pieces_per_strip' => 'decimal:4',
            'strips_per_box' => 'decimal:4',
            'boxes_per_carton' => 'decimal:4',
            'mrp' => 'decimal:4',
            'default_purchase_price' => 'decimal:4',
            'reviewed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function suggestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_by_user_id');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
