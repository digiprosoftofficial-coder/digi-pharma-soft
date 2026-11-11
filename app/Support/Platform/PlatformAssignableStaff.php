<?php

namespace App\Support\Platform;

use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

final class PlatformAssignableStaff
{
    /**
     * Users the actor may assign leads/demos to.
     * Super admin: sales + super admins. Sales: sales only (never admins).
     *
     * @return list<array{id:int,name:string,email:string}>
     */
    public static function optionsFor(User $actor): array
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);

        try {
            $query = User::query()
                ->whereNull('tenant_id')
                ->orderBy('name');

            if ($actor->isPlatformSuperAdminRole()) {
                $query->where(function ($q) {
                    $q->where('is_platform_super_admin', true)
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['super admin', 'sales']));
                });
            } else {
                $query->where('is_platform_super_admin', false)
                    ->whereHas('roles', fn ($r) => $r->where('name', 'sales'));
            }

            return $query
                ->get(['id', 'name', 'email'])
                ->map(fn (User $u) => [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                ])
                ->values()
                ->all();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    /**
     * Normalize assignment for create/update.
     * Sales may keep an existing admin assignee unchanged, but cannot newly assign an admin.
     */
    public static function normalize(?User $actor, mixed $requested, ?int $currentAssignedTo = null): ?int
    {
        if ($actor === null) {
            return $currentAssignedTo;
        }

        if ($requested === null || $requested === '' || $requested === 'null') {
            return null;
        }

        $requestedId = (int) $requested;

        if ($currentAssignedTo !== null && $requestedId === $currentAssignedTo) {
            return $currentAssignedTo;
        }

        if (! self::canAssignTo($actor, $requestedId)) {
            throw ValidationException::withMessages([
                'assigned_to' => __('platform.assigned_to_sales_only'),
            ]);
        }

        return $requestedId;
    }

    public static function canAssignTo(User $actor, int $assigneeId): bool
    {
        $allowed = collect(self::optionsFor($actor))->pluck('id')->all();

        return in_array($assigneeId, $allowed, true);
    }
}
