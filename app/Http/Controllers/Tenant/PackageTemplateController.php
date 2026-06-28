<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Sales\Models\PackageTemplate;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class PackageTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->guard($request);

        $templates = PackageTemplate::query()
            ->withCount('items')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Sales/PackageTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->guard($request);

        return Inertia::render('Sales/PackageTemplates/Form', [
            'template' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard($request);
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated) {
            $template = PackageTemplate::query()->create($this->templatePayload($validated));
            $this->syncItems($template, $validated['items']);
        });

        return redirect()->route('tenant.sales.packages.index')->with('success', __('Package template created.'));
    }

    public function edit(Request $request, PackageTemplate $packageTemplate): Response
    {
        $this->guard($request);

        return Inertia::render('Sales/PackageTemplates/Form', [
            'template' => $this->serializeTemplate($packageTemplate->load('items.product.units')),
        ]);
    }

    public function update(Request $request, PackageTemplate $packageTemplate): RedirectResponse
    {
        $this->guard($request);
        $validated = $this->validated($request);

        DB::transaction(function () use ($packageTemplate, $validated) {
            $packageTemplate->update($this->templatePayload($validated));
            $this->syncItems($packageTemplate, $validated['items']);
        });

        return redirect()->route('tenant.sales.packages.index')->with('success', __('Package template updated.'));
    }

    public function destroy(Request $request, PackageTemplate $packageTemplate): RedirectResponse
    {
        $this->guard($request);
        $packageTemplate->delete();

        return redirect()->route('tenant.sales.packages.index')->with('success', __('Package template removed.'));
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()?->can('pos.access'), 403);
        abort_unless(TenantFeatures::packageSalesEnabled(tenant()), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $tenantId = tenant_id();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'items.*.sell_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price_override' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->validateProductUnits($validated['items']);

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function validateProductUnits(array $items): void
    {
        $products = Product::query()
            ->with('units')
            ->whereIn('id', collect($items)->pluck('product_id')->unique()->values()->all())
            ->get()
            ->keyBy('id');

        $errors = [];
        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id']);
            $allowed = $product?->units->pluck('sell_unit')->push($product?->base_unit)->push($product?->unit)->filter()->unique()->all() ?? [];
            if (! in_array($item['sell_unit'], $allowed, true)) {
                $errors["items.$index.sell_unit"] = __('The selected unit is not configured for this product.');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function templatePayload(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'discount_percent' => $validated['discount_percent'] ?? null,
            'fixed_price' => $validated['fixed_price'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(PackageTemplate $template, array $items): void
    {
        $template->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $template->items()->create([
                'product_id' => (int) $item['product_id'],
                'sell_unit' => (string) $item['sell_unit'],
                'quantity' => (float) $item['quantity'],
                'unit_price_override' => $item['unit_price_override'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTemplate(PackageTemplate $template): array
    {
        return [
            'id' => $template->getKey(),
            'name' => $template->name,
            'description' => $template->description,
            'is_active' => (bool) $template->is_active,
            'discount_percent' => $template->discount_percent !== null ? (string) $template->discount_percent : null,
            'fixed_price' => $template->fixed_price !== null ? (string) $template->fixed_price : null,
            'items' => $template->items->map(fn ($item) => [
                'id' => $item->getKey(),
                'product_id' => $item->product_id,
                'product' => ProductResource::make($item->product)->resolve(),
                'sell_unit' => $item->sell_unit,
                'quantity' => (string) $item->quantity,
                'unit_price_override' => $item->unit_price_override !== null ? (string) $item->unit_price_override : null,
            ])->values()->all(),
        ];
    }
}
