<?php

namespace App\Http\Requests\Purchasing;

use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Payments\PaymentMethods;
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
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)],
            'new_supplier' => ['nullable', 'array'],
            'new_supplier.name' => ['required_with:new_supplier', 'string', 'max:255'],
            'new_supplier.phone' => ['nullable', 'string', 'max:64'],
            'new_supplier.email' => ['nullable', 'email', 'max:255'],
            'invoice_no' => ['required', 'string', 'max:64', Rule::unique('purchases', 'invoice_no')->where('tenant_id', $tenantId)],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', Rule::in(['amount', 'percent'])],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => [
                Rule::requiredIf(fn () => (float) $this->input('paid', 0) > 0),
                'nullable',
                'string',
                Rule::in(PaymentMethods::values()),
            ],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'lines.*.batch_no' => ['required', 'string', 'max:64'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.manufactured_at' => ['nullable', 'date'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.sell_unit' => ['required', ProductCatalogOptions::sellUnitRule()],
            'lines.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.storage_location_id' => [
                'nullable',
                'integer',
                Rule::exists('storage_locations', 'id')->where('tenant_id', $tenantId),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('supplier_id') && ! filled($this->input('new_supplier.name'))) {
                $validator->errors()->add('supplier_id', __('purchases.supplier_required'));
            }

            if ($this->input('discount_type') === 'percent' && (float) $this->input('discount', 0) > 100) {
                $validator->errors()->add('discount', __('purchases.discount_percent_invalid'));
            }
        });
    }
}
