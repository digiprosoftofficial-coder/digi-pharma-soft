<?php

namespace App\Domain\Billing\Models;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_MANUAL = 'manual';

    public const METHODS = [
        self::METHOD_BKASH,
        self::METHOD_NAGAD,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_MANUAL,
    ];

    protected $fillable = [
        'tenant_id',
        'platform_invoice_id',
        'method',
        'amount_cents',
        'currency',
        'transaction_reference',
        'status',
        'refund_status',
        'idempotency_key',
        'submitted_by',
        'verified_by',
        'verified_at',
        'rejected_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
