<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OnboardingController extends Controller
{
    public function show(TenantContext $tenantContext): Response
    {
        $tenant = $tenantContext->tenant();
        abort_unless($tenant !== null, 404);

        return Inertia::render('Tenant/Onboarding', [
            'progress' => TenantOnboarding::progress($tenant),
            'steps' => TenantOnboarding::STEPS,
        ]);
    }

    public function completeStep(Request $request, string $step, TenantContext $tenantContext): RedirectResponse
    {
        $tenant = $tenantContext->tenant();
        abort_unless($tenant !== null, 404);
        abort_unless(in_array($step, TenantOnboarding::STEPS, true), 404);

        TenantOnboarding::markStep($tenant, $step, true);

        return back()->with('success', __('platform.onboarding_step_saved'));
    }
}
