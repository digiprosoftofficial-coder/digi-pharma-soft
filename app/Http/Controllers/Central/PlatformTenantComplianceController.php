<?php

namespace App\Http\Controllers\Central;

use App\Domain\Tenant\Actions\ExportTenantDataAction;
use App\Domain\Tenant\Actions\PurgeTenantDataAction;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PlatformTenantComplianceController extends Controller
{
    public function export(Tenant $tenant, ExportTenantDataAction $export): BinaryFileResponse
    {
        $this->authorize('exportData', $tenant);

        $path = $export->execute($tenant, request()->user());
        $filename = sprintf('pharmacy-%s-%s.zip', $tenant->slug, now()->format('Y-m-d'));

        return response()->download($path, $filename)->deleteFileAfterSend();
    }

    public function purge(Request $request, Tenant $tenant, PurgeTenantDataAction $purge): RedirectResponse
    {
        $this->authorize('purgeData', $tenant);

        $validated = $request->validate([
            'confirm_slug' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($validated['confirm_slug'] !== $tenant->slug) {
            return back()->withErrors([
                'confirm_slug' => __('platform.compliance_slug_mismatch'),
            ]);
        }

        try {
            $purge->execute($tenant, $request->user(), $validated['reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['purge' => $e->getMessage()]);
        }

        return redirect()
            ->route('platform.tenants.index')
            ->with('success', __('platform.compliance_purged'));
    }
}
