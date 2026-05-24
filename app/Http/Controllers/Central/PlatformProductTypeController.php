<?php

namespace App\Http\Controllers\Central;

use App\Domain\Platform\Models\PlatformProductType;
use App\Domain\Platform\Services\PlatformProductTypeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformProductTypeController extends Controller
{
    public function __construct(
        private readonly PlatformProductTypeService $productTypes,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PlatformProductType::class);

        $productTypes = PlatformProductType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PlatformProductType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'sort_order' => $type->sort_order,
                'is_active' => $type->is_active,
                'icon_url' => $type->icon_url,
            ]);

        return Inertia::render('Platform/ProductTypes/Index', [
            'productTypes' => $productTypes,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PlatformProductType::class);

        return Inertia::render('Platform/ProductTypes/Form', ['productType' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PlatformProductType::class);

        $this->productTypes->create(
            $this->validated($request),
            $request->file('icon'),
        );

        return redirect()
            ->route('platform.product-types.index')
            ->with('success', __('platform.product_type_created'));
    }

    public function edit(PlatformProductType $productType): Response
    {
        $this->authorize('update', $productType);

        return Inertia::render('Platform/ProductTypes/Form', [
            'productType' => [
                'id' => $productType->id,
                'name' => $productType->name,
                'slug' => $productType->slug,
                'sort_order' => $productType->sort_order,
                'is_active' => $productType->is_active,
                'icon_url' => $productType->icon_url,
            ],
        ]);
    }

    public function update(Request $request, PlatformProductType $productType): RedirectResponse
    {
        $this->authorize('update', $productType);

        $this->productTypes->update(
            $productType,
            $this->validated($request, $productType),
            $request->file('icon'),
        );

        return redirect()
            ->route('platform.product-types.index')
            ->with('success', __('platform.product_type_updated'));
    }

    public function destroy(PlatformProductType $productType): RedirectResponse
    {
        $this->authorize('delete', $productType);

        $this->productTypes->delete($productType);

        return redirect()
            ->route('platform.product-types.index')
            ->with('success', __('platform.product_type_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PlatformProductType $productType = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('platform_product_types', 'slug')->ignore($productType?->getKey()),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:512'],
            'remove_icon' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['remove_icon'] = $request->boolean('remove_icon');

        return $validated;
    }
}
