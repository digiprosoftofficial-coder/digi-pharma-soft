<?php

use App\Domain\Tenant\Models\Tenant;
use App\Support\Tenant\TenantContext;

if (! function_exists('tenant')) {
    /**
     * Current tenant model for the request, if any.
     */
    function tenant(): ?Tenant
    {
        return app(TenantContext::class)->tenant();
    }
}

if (! function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        return app(TenantContext::class)->id();
    }
}
