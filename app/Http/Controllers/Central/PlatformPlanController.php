<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Support\Catalog\ProductImportCsv;
use App\Support\Platform\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformPlanController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        return Inertia::render('Platform/Plans/Index', [
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(),
            'currency' => PlatformSettings::defaultCurrency(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SubscriptionPlan::class);

        return Inertia::render('Platform/Plans/Form', [
            'plan' => null,
            'currency' => PlatformSettings::defaultCurrency(),
            'importPresets' => ProductImportCsv::PRESETS,
            'importColumns' => ProductImportCsv::HEADERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SubscriptionPlan::class);

        $validated = $this->validated($request);

        SubscriptionPlan::query()->create($validated);

        return redirect()->route('platform.plans.index')->with('success', __('platform.plan_created'));
    }

    public function edit(SubscriptionPlan $plan): Response
    {
        $this->authorize('update', $plan);

        return Inertia::render('Platform/Plans/Form', [
            'plan' => $plan,
            'currency' => PlatformSettings::defaultCurrency(),
            'importPresets' => ProductImportCsv::PRESETS,
            'importColumns' => ProductImportCsv::HEADERS,
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->update($this->validated($request, $plan));

        return redirect()->route('platform.plans.index')->with('success', __('platform.plan_updated'));
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()->route('platform.plans.index')->with('success', __('platform.plan_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:64', 'alpha_dash',
                Rule::unique('subscription_plans', 'slug')->ignore($plan?->getKey()),
            ],
            'price_cents' => ['required', 'integer', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'array'],
            'features.pos' => ['boolean'],
            'features.reports' => ['boolean'],
            'features.wholesale_pricing' => ['boolean'],
            'features.markup_pricing' => ['boolean'],
            'features.bulk_import' => ['boolean'],
            'features.advanced_catalog' => ['boolean'],
            'features.multi_branch' => ['boolean'],
            'features.supplier_branch_ledger' => ['boolean'],
            'features.employee_management' => ['boolean'],
            'features.attendance' => ['boolean'],
            'features.hr_payroll' => ['boolean'],
            'features.barcode_camera_scan' => ['boolean'],
            'features.import_preset' => ['nullable', 'string', Rule::in(ProductImportCsv::PRESETS)],
            'features.import_columns' => ['nullable', 'array'],
            'features.import_columns.*' => ['string', Rule::in(ProductImportCsv::HEADERS)],
            'limits' => ['nullable', 'array'],
            'limits.max_products' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'limits.max_import_rows' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'limits.max_branches' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $importPreset = $request->input('features.import_preset', ProductImportCsv::PRESET_PRO);
        $importColumns = $importPreset === ProductImportCsv::PRESET_CUSTOM
            ? array_values(array_filter((array) $request->input('features.import_columns', [])))
            : null;

        $validated['features'] = [
            'pos' => $request->boolean('features.pos', true),
            'reports' => $request->boolean('features.reports', true),
            'wholesale_pricing' => $request->boolean('features.wholesale_pricing', false),
            'markup_pricing' => $request->boolean('features.markup_pricing', false),
            'bulk_import' => $request->boolean('features.bulk_import', true),
            'advanced_catalog' => $request->boolean('features.advanced_catalog', true),
            'multi_branch' => $request->boolean('features.multi_branch', false),
            'supplier_branch_ledger' => $request->boolean('features.supplier_branch_ledger', false),
            'employee_management' => $request->boolean('features.employee_management', true),
            'attendance' => $request->boolean('features.attendance', false),
            'hr_payroll' => $request->boolean('features.hr_payroll', false),
            'barcode_camera_scan' => $request->boolean('features.barcode_camera_scan', false),
            'import_preset' => $importPreset,
            'import_columns' => $importColumns,
        ];

        $validated['limits'] = [
            'max_products' => self::nullableLimit($request->input('limits.max_products')),
            'max_import_rows' => self::nullableLimit($request->input('limits.max_import_rows')),
            'max_branches' => self::nullableLimit($request->input('limits.max_branches')) ?? 1,
        ];

        return $validated;
    }

    private static function nullableLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
