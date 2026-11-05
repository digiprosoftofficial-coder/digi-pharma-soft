<?php

namespace App\Domain\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public const TYPE_NOTE = 'note';

    public const TYPE_STATUS_CHANGE = 'status_change';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_FOLLOW_UP = 'follow_up';

    public const TYPE_CALL = 'call';

    public const TYPE_CONVERTED = 'converted';

    public const TYPE_DEMO = 'demo';

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'from_status',
        'to_status',
        'body',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
