<?php

namespace App\Domain\Tenant\Models;

use App\Domain\Billing\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
        'suspended_at',
        'settings',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->suspended_at || ! $this->is_active) {
            return false;
        }

        if ($this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return false;
        }

        if ($this->trial_ends_at && $this->trial_ends_at->isPast()) {
            if (! $this->hasPaidSubscriptionBeyondTrial()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Paid access continues only when subscription end is strictly after trial end.
     */
    public function hasPaidSubscriptionBeyondTrial(): bool
    {
        if (! $this->trial_ends_at || ! $this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->isAfter($this->trial_ends_at);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class)
            ->where('status', 'active')
            ->latestOfMany('starts_at');
    }
}
