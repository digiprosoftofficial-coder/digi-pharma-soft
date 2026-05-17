<?php

namespace App\Support\Middleware;

use App\Models\User;
use App\Support\Tenant\TenantImpersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform operators must use the platform console, not tenant-scoped pharmacy modules.
 */
final class EnsureTenantStaff
{
    public function __construct(private readonly TenantImpersonation $impersonation) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() instanceof User && $request->user()->shouldUsePlatformDashboard()) {
            if ($this->impersonation->isActive() && $this->impersonation->actingUser() !== null) {
                return $next($request);
            }

            abort(403);
        }

        return $next($request);
    }
}
