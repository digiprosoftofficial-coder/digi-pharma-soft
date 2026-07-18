<?php

namespace App\Http\Requests\Sales;

use App\Support\Catalog\ProductCatalogOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.access') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = tenant_id();

        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'new_customer' => ['nullable', 'array'],
            'new_customer.name' => ['required_with:new_customer', 'string', 'max:255'],
            'new_customer.phone' => ['nullable', 'string', 'max:64'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_batch_id' => ['required', 'integer', Rule::exists('product_batches', 'id')->where('tenant_id', $tenantId)],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.sell_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'max:32'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:32'],
            'offline_client_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
