<?php

namespace App\Http\Controllers\Central;

use App\Domain\Catalog\Actions\ApplyCatalogTemplateToTenantAction;
use App\Domain\Platform\Models\CatalogTemplate;
use App\Domain\Platform\Models\CatalogTemplateItem;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformCatalogTemplateController extends Controller
{
    public function __construct(
        private readonly ApplyCatalogTemplateToTenantAction $applyTemplate,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', CatalogTemplate::class);

        $templates = CatalogTemplate::query()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return Inertia::render('Platform/Catalog/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CatalogTemplate::class);

        return Inertia::render('Platform/Catalog/Form', ['template' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CatalogTemplate::class);

        $template = CatalogTemplate::query()->create($this->validatedTemplate($request));

        return redirect()
            ->route('platform.catalog-templates.show', $template)
            ->with('success', __('platform.catalog_created'));
    }

    public function show(CatalogTemplate $catalogTemplate): Response
    {
        $this->authorize('view', $catalogTemplate);

        $catalogTemplate->load('items');

        return Inertia::render('Platform/Catalog/Show', [
            'template' => $catalogTemplate,
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function edit(CatalogTemplate $catalogTemplate): Response
    {
        $this->authorize('update', $catalogTemplate);

        return Inertia::render('Platform/Catalog/Form', ['template' => $catalogTemplate]);
    }

    public function update(Request $request, CatalogTemplate $catalogTemplate): RedirectResponse
    {
        $this->authorize('update', $catalogTemplate);

        $catalogTemplate->update($this->validatedTemplate($request, $catalogTemplate));

        return redirect()
            ->route('platform.catalog-templates.show', $catalogTemplate)
            ->with('success', __('platform.catalog_updated'));
    }

    public function destroy(CatalogTemplate $catalogTemplate): RedirectResponse
    {
        $this->authorize('delete', $catalogTemplate);

        $catalogTemplate->delete();

        return redirect()->route('platform.catalog-templates.index')->with('success', __('platform.catalog_deleted'));
    }

    public function storeItem(Request $request, CatalogTemplate $catalogTemplate): RedirectResponse
    {
        $this->authorize('update', $catalogTemplate);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required', 'string', 'max:64',
                Rule::unique('catalog_template_items', 'sku')->where('catalog_template_id', $catalogTemplate->getKey()),
            ],
            'barcode' => ['nullable', 'string', 'max:64'],
            'unit' => ['nullable', 'string', 'max:32'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
        ]);

        $catalogTemplate->items()->create([
            ...$validated,
            'unit' => $validated['unit'] ?? 'pcs',
            'sort_order' => (int) $catalogTemplate->items()->max('sort_order') + 1,
        ]);

        return back()->with('success', __('platform.catalog_item_added'));
    }

    public function destroyItem(CatalogTemplate $catalogTemplate, CatalogTemplateItem $item): RedirectResponse
    {
        $this->authorize('update', $catalogTemplate);

        if ($item->catalog_template_id !== $catalogTemplate->getKey()) {
            abort(404);
        }

        $item->delete();

        return back()->with('success', __('platform.catalog_item_removed'));
    }

    public function apply(Request $request, CatalogTemplate $catalogTemplate): RedirectResponse
    {
        $this->authorize('apply', $catalogTemplate);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $created = $this->applyTemplate->execute($catalogTemplate, $tenant, $request->user());

        return back()->with('success', __('platform.catalog_applied', ['count' => $created]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTemplate(Request $request, ?CatalogTemplate $template = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:64', 'alpha_dash',
                Rule::unique('catalog_templates', 'slug')->ignore($template?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['boolean'],
        ]);

        $validated['slug'] = Str::lower($validated['slug']);
        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }
}
