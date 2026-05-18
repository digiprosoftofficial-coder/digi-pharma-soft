<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

final class PurgeTenantDataAction
{
    public function execute(Tenant $tenant, User $causer, string $reason): void
    {
        if ($tenant->data_purged_at !== null) {
            throw new \InvalidArgumentException('This pharmacy has already been purged.');
        }

        if ($tenant->suspended_at === null) {
            throw new \InvalidArgumentException('Suspend the pharmacy before permanent deletion.');
        }

        $tenantId = $tenant->getKey();

        DB::transaction(function () use ($tenant, $tenantId, $causer, $reason): void {
            $teamKey = config('permission.column_names.team_foreign_key', 'tenant_id');

            $userIds = User::query()
                ->where('tenant_id', $tenantId)
                ->pluck('id');

            if ($userIds->isNotEmpty()) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();

                DB::table(config('permission.table_names.model_has_roles'))
                    ->where('model_type', User::class)
                    ->whereIn(config('permission.column_names.model_morph_key', 'model_id'), $userIds)
                    ->delete();

                DB::table(config('permission.table_names.model_has_permissions'))
                    ->where('model_type', User::class)
                    ->whereIn(config('permission.column_names.model_morph_key', 'model_id'), $userIds)
                    ->delete();

                User::query()->whereIn('id', $userIds)->delete();
            }

            Role::query()
                ->where($teamKey, $tenantId)
                ->delete();

            $slug = $tenant->slug;
            $name = $tenant->name;

            Activity::query()
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                        ->orWhere(fn ($q2) => $q2
                            ->where('subject_type', Tenant::class)
                            ->where('subject_id', $tenantId));
                })
                ->delete();

            activity()
                ->causedBy($causer)
                ->event('tenant.data_purged')
                ->withProperties([
                    'tenant_id' => $tenantId,
                    'tenant_slug' => $slug,
                    'tenant_name' => $name,
                    'reason' => $reason,
                ])
                ->log('Pharmacy permanently deleted (compliance purge)');

            $tenant->delete();
        });
    }
}
