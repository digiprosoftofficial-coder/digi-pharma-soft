<?php

namespace App\Support\Middleware;

use App\Models\User;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantImpersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantSubscriptionActive
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantImpersonation $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user() instanceof User
            && $request->user()->shouldUsePlatformDashboard()
            && $this->impersonation->isActive()
        ) {
            return $next($request);
        }

        $tenant = $this->tenantContext->tenant();

        if ($tenant !== null && ! $tenant->isSubscriptionActive()) {
            abort(403, __('platform.subscription_inactive'));
        }

        return $next($request);
    }
}
