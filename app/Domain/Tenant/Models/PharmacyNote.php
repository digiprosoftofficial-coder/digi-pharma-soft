<?php

namespace App\Domain\Tenant\Models;

use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyNote extends TenantModel
{
    public const TYPE_BUY = 'buy';

    public const TYPE_CONTACT = 'contact';

    public const TYPE_REMINDER = 'reminder';

    public const TYPE_GENERAL = 'general';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_BUY,
        self::TYPE_CONTACT,
        self::TYPE_REMINDER,
        self::TYPE_GENERAL,
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'body',
        'type',
        'is_pinned',
        'is_done',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
