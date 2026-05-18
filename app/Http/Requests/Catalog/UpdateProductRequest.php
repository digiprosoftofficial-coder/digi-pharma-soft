<?php

namespace App\Http\Requests\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Support\Catalog\ProductCatalogOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $product && $this->user()?->can('update', $product);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'manufacturer_id' => ['nullable', 'integer', Rule::exists('manufacturers', 'id')->where('tenant_id', $tenantId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['sometimes', 'string', 'max:64', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)->ignore($product->getKey())],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)->ignore($product->getKey())],
            'product_type' => ['sometimes', ProductCatalogOptions::productTypeRule()],
            'base_unit' => ['sometimes', ProductCatalogOptions::sellUnitRule()],
            'units' => ['sometimes', 'array', 'min:1'],
            'units.*.sell_unit' => ['required_with:units', ProductCatalogOptions::sellUnitRule()],
            'units.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'units.*.purchase_price' => ['required_with:units', 'numeric', 'min:0'],
            'units.*.sale_price' => ['required_with:units', 'numeric', 'min:0'],
            'units.*.is_default' => ['sometimes', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
