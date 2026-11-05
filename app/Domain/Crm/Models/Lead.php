<?php

namespace App\Domain\Crm\Models;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_DEMO_SCHEDULED = 'demo_scheduled';

    public const STATUS_DEMO_COMPLETED = 'demo_completed';

    public const STATUS_TRIAL_STARTED = 'trial_started';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_LOST = 'lost';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_DEMO_SCHEDULED,
        self::STATUS_DEMO_COMPLETED,
        self::STATUS_TRIAL_STARTED,
        self::STATUS_CONVERTED,
        self::STATUS_LOST,
    ];

    public const SOURCES = [
        'website',
        'facebook',
        'whatsapp',
        'referral',
        'other',
    ];

    protected $fillable = [
        'pharmacy_name',
        'owner_name',
        'phone',
        'whatsapp',
        'email',
        'branch_count',
        'address',
        'source',
        'interested_plan_id',
        'status',
        'notes',
        'follow_up_at',
        'assigned_to',
        'converted_tenant_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'branch_count' => 'integer',
            'follow_up_at' => 'datetime',
        ];
    }

    public function interestedPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'interested_plan_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'converted_tenant_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function demoRequests(): HasMany
    {
        return $this->hasMany(DemoRequest::class);
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED && $this->converted_tenant_id !== null;
    }
}
