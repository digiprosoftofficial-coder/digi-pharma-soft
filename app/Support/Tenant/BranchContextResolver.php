<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Branch;
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
