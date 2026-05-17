<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Tenant;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
