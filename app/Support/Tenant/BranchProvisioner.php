<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Branch;

final class BranchProvisioner
{
    public static function provisionForTenant(int $tenantId): Branch
    {
        $existing = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Branch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public static function defaultForTenant(?int $tenantId): ?Branch
    {
        if ($tenantId === null) {
            return null;
        }

        return Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first()
            ?? self::provisionForTenant($tenantId);
    }
}
