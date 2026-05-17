<?php

namespace App\Support\Traits;

use App\Support\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->id();
            if ($tenantId === null) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $builder->where($table.'.tenant_id', $tenantId);
        });

        static::creating(function (Model $model): void {
            if (! $model->getAttribute('tenant_id') && app(TenantContext::class)->id()) {
                $model->setAttribute('tenant_id', app(TenantContext::class)->id());
            }
        });
    }
}
