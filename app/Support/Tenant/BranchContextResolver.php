<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

final class BranchContextResolver
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function resolveFromRequest(Request $request): ?Branch
    {
        $tenantId = $this->tenantContext->id();
        if ($tenantId === null) {
            return null;
        }

        $user = $request->user();
        if ($user instanceof User && $user->isLockedToAssignedBranch()) {
            $assigned = Branch::query()
                ->whereKey($user->assignedBranchId())
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if ($assigned !== null) {
                $request->session()->put('active_branch_id', $assigned->getKey());

                return $assigned;
            }
        }

        $sessionId = $request->session()->get('active_branch_id');
        if ($sessionId) {
            $branch = Branch::query()->whereKey((int) $sessionId)->where('is_active', true)->first();
            if ($branch) {
                return $branch;
            }
        }

        return BranchProvisioner::defaultForTenant($tenantId);
    }
}
