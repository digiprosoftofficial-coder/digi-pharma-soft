<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Money\SupportedCurrencies;
use App\Support\Platform\PlatformSettings;
use App\Support\Sales\InvoiceRounding;
use App\Support\Tenant\TenantFeatures;
use App\Support\Theme\ThemeCatalog;
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

        $theme = ThemeCatalog::resolveForTenant($tenant);

        return Inertia::render('Settings/Edit', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'settings' => $tenant->settings ?? [],
                'currency' => $tenant->currency(),
            ],
            'supplierBranchLedgerEnabled' => TenantFeatures::supplierBranchLedgerEnabled($tenant),
            'packageSalesAvailable' => TenantFeatures::packageSalesAvailable($tenant),
            'themeOptions' => $theme['available_templates'],
            'allowCustomPrimary' => $theme['allow_custom_primary'],
            'resolvedTheme' => [
                'template' => $theme['template'],
                'primary' => $theme['primary'],
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
            'settings.supplier_payments.cross_branch' => ['nullable', 'boolean'],
            'settings.supplier_payments.managers_can_pay' => ['nullable', 'boolean'],
            'settings.features.package_sales' => ['nullable', 'boolean'],
            'settings.features.smart_search' => ['nullable', 'boolean'],
            'settings.theme.template' => ['nullable', 'string', Rule::in(ThemeCatalog::ids())],
            'settings.theme.primary' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tenant->name = $validated['name'];
        $settings = $tenant->settings ?? [];
        if (isset($validated['settings'])) {
            $incoming = $validated['settings'];

            if (array_key_exists('supplier_payments', $incoming)) {
                $payments = $settings['supplier_payments'] ?? [];
                if (array_key_exists('cross_branch', $incoming['supplier_payments'])) {
                    $payments['cross_branch'] = (bool) $incoming['supplier_payments']['cross_branch'];
                }
                if (array_key_exists('managers_can_pay', $incoming['supplier_payments'])) {
                    $payments['managers_can_pay'] = (bool) $incoming['supplier_payments']['managers_can_pay'];
                }
                $settings['supplier_payments'] = $payments;
                unset($incoming['supplier_payments']);
            }

            if (array_key_exists('features', $incoming)) {
                $features = $settings['features'] ?? [];
                if (array_key_exists(TenantFeatures::PACKAGE_SALES, $incoming['features'])) {
                    $features[TenantFeatures::PACKAGE_SALES] = TenantFeatures::packageSalesAvailable($tenant)
                        && (bool) $incoming['features'][TenantFeatures::PACKAGE_SALES];
                }
                if (array_key_exists(TenantFeatures::SMART_SEARCH, $incoming['features'])) {
                    $features[TenantFeatures::SMART_SEARCH] = (bool) $incoming['features'][TenantFeatures::SMART_SEARCH];
                }
                $settings['features'] = $features;
                unset($incoming['features']);
            }

            if (array_key_exists('theme', $incoming)) {
                $settings['theme'] = ThemeCatalog::normalizeTenantTheme($tenant, (array) $incoming['theme']);
                unset($incoming['theme']);
            }

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
