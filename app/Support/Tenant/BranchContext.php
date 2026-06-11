<?php

namespace App\Support\Tenant;

use App\Domain\Tenant\Models\Branch;

final class BranchContext
{
    private ?Branch $branch = null;

    public function set(?Branch $branch): void
    {
        $this->branch = $branch;
    }

    public function id(): ?int
    {
        return $this->branch?->getKey();
    }

    public function branch(): ?Branch
    {
        return $this->branch;
    }

    public function hasBranch(): bool
    {
        return $this->branch !== null;
    }
}
