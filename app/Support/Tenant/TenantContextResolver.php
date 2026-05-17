<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

final class TenantContextResolver
{
    public function __construct(private readonly TenantImpersonation $impersonation) {}

    public function resolveFromRequest(Request $request): ?Tenant
    {
        if ($request->routeIs('central.*', 'platform.*')) {
            return null;
        }

        $user = $request->user();
        if ($user === null) {
            return null;
        }

        if ($user instanceof User && $user->shouldUsePlatformDashboard()) {
            if ($this->impersonation->isActive()) {
                return $this->impersonation->tenant();
            }

            return null;
        }

        $slug = $request->header('X-Tenant-Slug')
            ?? $request->route('tenant');

        if (is_string($slug) && $slug !== '') {
            return Tenant::query()->where('slug', $slug)->where('is_active', true)->first();
        }

        if ($user->tenant_id) {
            return Tenant::query()->whereKey($user->tenant_id)->where('is_active', true)->first();
        }

        return null;
    }
}
