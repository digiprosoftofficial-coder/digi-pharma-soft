<?php

namespace App\Http\Requests\Purchasing;

use App\Support\Catalog\ProductCatalogOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();

        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)],
            'invoice_no' => ['required', 'string', 'max:64', Rule::unique('purchases', 'invoice_no')->where('tenant_id', $tenantId)],
            'purchased_at' => ['required', 'date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'lines.*.batch_no' => ['required', 'string', 'max:64'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.sell_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
            'lines.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.storage_location_id' => [
                'nullable',
                'integer',
                Rule::exists('storage_locations', 'id')->where('tenant_id', $tenantId),
            ],
        ];
    }
}
