<?php

namespace App\Http\Requests\Catalog;

use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Tenant\TenantFeatures;
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
        foreach (['generic_name', 'short_description', 'wholesale_price', 'vat_percent', 'sku'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if (! TenantFeatures::wholesalePricingEnabled(tenant())) {
            $this->offsetUnset('wholesale_price');
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Domain\Catalog\Models\Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();

        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'manufacturer_id' => ['nullable', 'integer', Rule::exists('manufacturers', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)],
            'wholesale_price' => TenantFeatures::wholesalePricingEnabled(tenant())
                ? ['nullable', 'numeric', 'min:0']
                : ['prohibited'],
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'product_type' => ['required', ProductCatalogOptions::productTypeRule()],
            'base_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
            'pieces_per_strip' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'strips_per_box' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'boxes_per_carton' => ['sometimes', 'nullable', 'numeric', 'min:0.0001'],
            'units' => ['required', 'array', 'min:1'],
            'units.*.sell_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
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
