<?php

namespace App\Http\Requests\Purchasing;

use App\Domain\Purchasing\Models\Purchase;
use App\Support\Payments\PaymentMethods;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('amount') && is_numeric($this->input('amount'))) {
            $this->merge(['amount' => round((float) $this->input('amount'), 2)]);
        }
    }

    public function authorize(): bool
    {
        $purchase = $this->resolvePurchase();

        return $purchase
            && $this->user()?->can('purchases.manage')
            && (int) $this->user()->tenant_id === (int) $purchase->tenant_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method' => PaymentMethods::rule(),
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:500'],
            'redirect' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $purchase = $this->resolvePurchase();
            if (! $purchase) {
                return;
            }

            $amount = round((float) $this->input('amount', 0), 2);
            $due = round((float) $purchase->due, 2);

            if ($amount > $due + 0.0001) {
                $validator->errors()->add('amount', __('purchases.payment_exceeds_due'));
            }
        });
    }

    private function resolvePurchase(): ?Purchase
    {
        $purchase = $this->route('purchase');

        if ($purchase instanceof Purchase) {
            return $purchase;
        }

        if (is_numeric($purchase)) {
            return Purchase::query()
                ->withoutGlobalScope('branch')
                ->whereKey((int) $purchase)
                ->first();
        }

        return null;
    }
}
