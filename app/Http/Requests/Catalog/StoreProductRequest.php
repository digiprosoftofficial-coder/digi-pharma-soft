<?php

namespace App\Http\Requests\Catalog;

use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductTypeUnitRules;
use App\Support\Tenant\TenantFeatures;
use App\Support\Tenant\TenantLimits;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('pieces_per_strip') && $this->input('pieces_per_strip') === '') {
            $this->merge(['pieces_per_strip' => null]);
        }
        if ($this->has('strips_per_box') && $this->input('strips_per_box') === '') {
            $this->merge(['strips_per_box' => null]);
        }
        if ($this->has('boxes_per_carton') && $this->input('boxes_per_carton') === '') {
            $this->merge(['boxes_per_carton' => null]);
        }
        foreach (['generic_name', 'strength', 'short_description', 'wholesale_price', 'vat_percent', 'default_markup_percent', 'sku', 'storage_location_id', 'opening_storage_location_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if (! TenantFeatures::wholesalePricingEnabled(tenant())) {
            $this->offsetUnset('wholesale_price');
        }

        if (! TenantFeatures::advancedCatalogEnabled(tenant())) {
            foreach (TenantFeatures::ADVANCED_CATALOG_FIELDS as $field) {
                $this->offsetUnset($field);
            }
        }

        $productType = (string) $this->input('product_type', 'other');
        if (! ProductTypeUnitRules::usesStripUnit($productType)) {
            $this->merge([
                'pieces_per_strip' => null,
                'strips_per_box' => null,
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Domain\Catalog\Models\Product::class) ?? false;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (TenantLimits::productLimitReached(tenant())) {
                $validator->errors()->add('name', __('catalog.product_limit_reached', [
                    'max' => TenantLimits::maxProducts(tenant()),
                ]));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        $productType = (string) $this->input('product_type', 'other');

        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'manufacturer_id' => ['nullable', 'integer', Rule::exists('manufacturers', 'id')->where('tenant_id', $tenantId)],
            'storage_location_id' => ['nullable', 'integer', Rule::exists('storage_locations', 'id')->where('tenant_id', $tenantId)],
            'opening_storage_location_id' => ['nullable', 'integer', Rule::exists('storage_locations', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:64'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)],
            'wholesale_price' => TenantFeatures::wholesalePricingEnabled(tenant())
                ? ['nullable', 'numeric', 'min:0']
                : ['prohibited'],
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_markup_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'product_type' => ['required', ProductCatalogOptions::productTypeRule()],
            'base_unit' => ['required', ProductCatalogOptions::sellUnitRuleForProductType($productType)],
            'pieces_per_strip' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'strips_per_box' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'boxes_per_carton' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'units' => ['required', 'array', 'min:1'],
            'units.*.sell_unit' => ['required', ProductCatalogOptions::sellUnitRuleForProductType($productType)],
            'units.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'units.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'units.*.sale_price' => ['required', 'numeric', 'min:0'],
            'units.*.is_default' => ['sometimes', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'opening_batch_no' => ['nullable', 'string', 'max:64'],
            'opening_expiry_date' => ['nullable', 'date'],
            'opening_quantity' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }
}
