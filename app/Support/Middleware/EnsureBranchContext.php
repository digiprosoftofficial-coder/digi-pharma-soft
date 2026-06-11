<?php

namespace App\Support\Middleware;

use App\Support\Tenant\BranchContext;
use App\Support\Tenant\BranchContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBranchContext
{
    public function __construct(
        private readonly BranchContext $branchContext,
        private readonly BranchContextResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('tenant.*')) {
            $this->branchContext->set($this->resolver->resolveFromRequest($request));
        }

        return $next($request);
    }
}
