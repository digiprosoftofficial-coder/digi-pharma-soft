<?php

namespace App\Support\Middleware;

use App\Models\User;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantContextResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->set($this->resolver->resolveFromRequest($request));

        if ($request->routeIs('tenant.*') && ! $this->tenantContext->hasTenant()) {
            if ($request->user() instanceof User && $request->user()->shouldUsePlatformDashboard()) {
                return redirect()->route('platform.dashboard');
            }

            abort(403, 'Tenant context missing or inactive.');
        }

        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);

        if ($this->tenantContext->hasTenant()) {
            $registrar->setPermissionsTeamId($this->tenantContext->id());
        } elseif ($request->user() instanceof User && $request->user()->shouldUsePlatformDashboard()) {
            $registrar->setPermissionsTeamId(\App\Support\Permission\PlatformTeam::ID);
        } elseif ($request->user()) {
            $registrar->setPermissionsTeamId($request->user()->getPermissionTeamId());
        }

        return $next($request);
    }
}
