<?php

use App\Domain\Tenant\Models\Branch;
use App\Support\Tenant\BranchContext;

if (! function_exists('branch')) {
    function branch(): ?Branch
    {
        return app(BranchContext::class)->branch();
    }
}

if (! function_exists('branch_id')) {
    function branch_id(): ?int
    {
        return app(BranchContext::class)->id();
    }
}
