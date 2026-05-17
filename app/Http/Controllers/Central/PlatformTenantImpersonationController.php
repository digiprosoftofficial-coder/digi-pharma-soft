<?php

namespace App\Http\Controllers\Central;

use App\Domain\Tenant\Actions\StartTenantImpersonationAction;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PlatformTenantImpersonationController extends Controller
{
    public function __construct(
        private readonly StartTenantImpersonationAction $start,
        private readonly TenantImpersonation $impersonation,
    ) {}

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('impersonate', $tenant);

        $this->start->execute($tenant, $request->user());

        return redirect()
            ->route('tenant.dashboard')
            ->with('success', __('platform.impersonation_started', ['name' => $tenant->name]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenant = $this->impersonation->tenant();
        $wasActive = $this->impersonation->isActive();

        if ($wasActive && $tenant !== null) {
            activity()
                ->causedBy($request->user())
                ->performedOn($tenant)
                ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
                ->event('tenant.impersonation_stopped')
                ->log('Platform admin stopped tenant impersonation');
        }

        $this->impersonation->clear();

        if ($tenant !== null) {
            return redirect()
                ->route('platform.tenants.show', $tenant)
                ->with('success', __('platform.impersonation_stopped'));
        }

        return redirect()
            ->route('platform.dashboard')
            ->with('success', __('platform.impersonation_stopped'));
    }
}
