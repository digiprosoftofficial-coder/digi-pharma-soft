<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductTypeUnitRules;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('pieces_per_strip') && $this->input('pieces_per_strip') === '') {
            $this->merge(['pieces_per_strip' => null]);
        }
        if ($this->has('strips_per_box') && $this->input('strips_per_box') === '') {
            $this->merge(['strips_per_box' => null]);
        }
        if ($this->has('pieces_per_box') && $this->input('pieces_per_box') === '') {
            $this->merge(['pieces_per_box' => null]);
        }
        if ($this->has('boxes_per_carton') && $this->input('boxes_per_carton') === '') {
            $this->merge(['boxes_per_carton' => null]);
        }
        foreach (['generic_name', 'strength', 'short_description', 'wholesale_price', 'vat_percent', 'default_markup_percent', 'storage_location_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if (! TenantFeatures::wholesalePricingEnabled(tenant())) {
            $this->offsetUnset('wholesale_price');
        }

        if (! TenantFeatures::markupPricingEnabled(tenant())) {
            $this->offsetUnset('default_markup_percent');
        }

        if (! TenantFeatures::advancedCatalogEnabled(tenant())) {
            foreach (TenantFeatures::ADVANCED_CATALOG_FIELDS as $field) {
                $this->offsetUnset($field);
            }
        }

        $productType = (string) ($this->input('product_type') ?? $this->route('product')?->product_type ?? 'other');
        $baseUnit = (string) ($this->input('base_unit') ?? $this->route('product')?->base_unit ?? 'strip');
        if (! ProductTypeUnitRules::usesStripUnit($productType)) {
            $this->merge([
                'pieces_per_strip' => null,
                'strips_per_box' => null,
            ]);
        }
        if ($baseUnit === 'strip') {
            $this->merge(['pieces_per_box' => null]);
        } elseif ($baseUnit === 'piece') {
            $this->merge(['strips_per_box' => null]);
        } elseif ($baseUnit === 'box') {
            $this->merge([
                'pieces_per_strip' => null,
                'strips_per_box' => null,
                'pieces_per_box' => null,
            ]);
        } elseif ($baseUnit === 'carton') {
            $this->merge([
                'pieces_per_strip' => null,
                'strips_per_box' => null,
                'pieces_per_box' => null,
                'boxes_per_carton' => null,
            ]);
        }
    }

    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $product && $this->user()?->can('update', $product);
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $validator) {
            /** @var Product $product */
            $product = $this->route('product');
            $product->loadMissing('units');
            $productType = (string) ($this->input('product_type') ?? $product->product_type ?? 'other');
            $allowed = ProductTypeUnitRules::sellUnitsFor($productType);

            if ($this->filled('base_unit')) {
                $base = (string) $this->input('base_unit');
                if (! in_array($base, $allowed, true) && $base !== $product->base_unit) {
                    $validator->errors()->add('base_unit', __('catalog.base_unit_not_allowed_for_type'));
                }
            }

            foreach ($this->input('units', []) as $index => $row) {
                $sellUnit = $row['sell_unit'] ?? null;
                if (! $sellUnit || in_array($sellUnit, $allowed, true)) {
                    continue;
                }
                $existing = $product->units->firstWhere('sell_unit', $sellUnit);
                if ($existing === null) {
                    $validator->errors()->add("units.{$index}.sell_unit", __('catalog.sell_unit_not_allowed_for_type'));
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        /** @var Product $product */
        $product = $this->route('product');

        $productType = (string) ($this->input('product_type') ?? $product->product_type ?? 'other');

        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'manufacturer_id' => ['nullable', 'integer', Rule::exists('manufacturers', 'id')->where('tenant_id', $tenantId)],
            'storage_location_id' => ['nullable', 'integer', Rule::exists('storage_locations', 'id')->where('tenant_id', $tenantId)],
            'batch_locations' => ['sometimes', 'array'],
            'batch_locations.*.id' => [
                'required',
                'integer',
                Rule::exists('product_batches', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $product->getKey()),
            ],
            'batch_locations.*.storage_location_id' => [
                'nullable',
                'integer',
                Rule::exists('storage_locations', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'generic_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'strength' => ['sometimes', 'nullable', 'string', 'max:64'],
            'sku' => ['sometimes', 'string', 'max:64', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)->ignore($product->getKey())],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)->ignore($product->getKey())],
            'wholesale_price' => TenantFeatures::wholesalePricingEnabled(tenant())
                ? ['sometimes', 'nullable', 'numeric', 'min:0']
                : ['prohibited'],
            'vat_percent' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'default_markup_percent' => TenantFeatures::markupPricingEnabled(tenant())
                ? ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000']
                : ['prohibited'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'product_type' => ['sometimes', ProductCatalogOptions::productTypeRule()],
            'base_unit' => ['sometimes', ProductCatalogOptions::sellUnitRuleForProductType($productType)],
            'pieces_per_strip' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'strips_per_box' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'pieces_per_box' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'boxes_per_carton' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'units' => ['sometimes', 'array', 'min:1'],
            'units.*.sell_unit' => ['required_with:units', 'distinct', ProductCatalogOptions::sellUnitRuleForProductType($productType)],
            'units.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'units.*.purchase_price' => ['required_with:units', 'numeric', 'min:0'],
            'units.*.sale_price' => ['required_with:units', 'numeric', 'min:0'],
            'units.*.is_default' => ['sometimes', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'stock_adjustment' => ['nullable', 'numeric', 'not_in:0'],
            'stock_adjust_batch_id' => [
                'nullable',
                'integer',
                Rule::exists('product_batches', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('product_id', $product->getKey()),
            ],
            'stock_adjust_batch_no' => ['nullable', 'string', 'max:64'],
        ];
    }
}
