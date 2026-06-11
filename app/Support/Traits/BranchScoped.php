<?php

namespace App\Support\Traits;

use App\Support\Tenant\BranchContext;
use App\Support\Tenant\BranchProvisioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BranchScoped
{
    protected static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch', function (Builder $builder): void {
            $branchId = app(BranchContext::class)->id();
            if ($branchId === null) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $builder->where($table.'.branch_id', $branchId);
        });

        static::creating(function (Model $model): void {
            if (! $model->isFillable('branch_id') || $model->getAttribute('branch_id')) {
                return;
            }

            $tenantId = $model->getAttribute('tenant_id') ?? \tenant_id();
            $branchId = app(BranchContext::class)->id()
                ?? ($tenantId ? BranchProvisioner::defaultForTenant((int) $tenantId)?->getKey() : null);

            if ($branchId) {
                $model->setAttribute('branch_id', $branchId);
            }
        });
    }
}
