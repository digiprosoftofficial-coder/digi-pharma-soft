<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Money\SupportedCurrencies;
use App\Support\Platform\PlatformSettings;
use App\Support\Sales\InvoiceRounding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class TenantSettingsController extends Controller
{
    public function edit(): Response
    {
        abort_unless(auth()->user()?->can('settings.view'), 403);

        $tenant = tenant();
        abort_unless($tenant !== null, 404);

        return Inertia::render('Settings/Edit', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'settings' => $tenant->settings ?? [],
                'currency' => $tenant->currency(),
            ],
            'currencies' => SupportedCurrencies::codes(),
            'platformDefaultCurrency' => PlatformSettings::defaultCurrency(),
            'roundingOptions' => [
                ['value' => InvoiceRounding::NONE, 'label' => __('sales.rounding_none')],
                ['value' => InvoiceRounding::NEAREST_1, 'label' => __('sales.rounding_nearest_1')],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('settings.manage'), 403);

        $tenant = tenant();
        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'settings.phone' => ['nullable', 'string', 'max:64'],
            'settings.address' => ['nullable', 'string', 'max:500'],
            'settings.currency' => ['nullable', SupportedCurrencies::validationRule()],
            'settings.invoice_rounding' => ['nullable', Rule::in([InvoiceRounding::NONE, InvoiceRounding::NEAREST_1])],
        ]);

        $tenant->name = $validated['name'];
        $settings = $tenant->settings ?? [];
        if (isset($validated['settings'])) {
            $incoming = $validated['settings'];
            foreach ($incoming as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $settings[$key] = $key === 'currency' ? strtoupper((string) $value) : $value;
            }
        }
        $tenant->settings = $settings;
        $tenant->save();

        return redirect()->route('tenant.settings.edit')->with('success', __('Settings saved.'));
    }
}
