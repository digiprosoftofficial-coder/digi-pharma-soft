<?php

namespace App\Http\Requests\Sales;

use App\Domain\Sales\Models\Sale;
use App\Support\Payments\PaymentMethods;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sale = $this->resolveSale();

        return $sale
            && $this->user()?->can('customers.manage')
            && (int) $this->user()->tenant_id === (int) $sale->tenant_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method' => PaymentMethods::rule(),
            'amount' => ['required', 'numeric', 'min:0.01'],
            'redirect' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sale = $this->resolveSale();
            if (! $sale) {
                return;
            }

            $amount = (float) $this->input('amount', 0);
            if ($amount > (float) $sale->due + 0.0001) {
                $validator->errors()->add('amount', __('sales.payment_exceeds_due'));
            }
        });
    }

    private function resolveSale(): ?Sale
    {
        $sale = $this->route('sale');

        if ($sale instanceof Sale) {
            return $sale;
        }

        if (is_numeric($sale)) {
            return Sale::query()
                ->withoutGlobalScope('branch')
                ->whereKey((int) $sale)
                ->first();
        }

        return null;
    }
}
