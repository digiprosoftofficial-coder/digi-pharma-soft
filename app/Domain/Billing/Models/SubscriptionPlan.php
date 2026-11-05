<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'monthly_price_cents',
        'yearly_price_cents',
        'trial_days',
        'is_active',
        'sort_order',
        'features',
        'limits',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'monthly_price_cents' => 'integer',
            'yearly_price_cents' => 'integer',
            'trial_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function priceForInterval(string $interval): int
    {
        return $interval === 'yearly'
            ? (int) ($this->yearly_price_cents ?: $this->price_cents)
            : (int) ($this->monthly_price_cents ?: $this->price_cents);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
