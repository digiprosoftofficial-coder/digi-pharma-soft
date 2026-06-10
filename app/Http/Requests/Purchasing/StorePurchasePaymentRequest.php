<?php

namespace App\Http\Requests\Purchasing;

use App\Domain\Purchasing\Models\Purchase;
use App\Support\Payments\PaymentMethods;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Purchase|null $purchase */
        $purchase = $this->route('purchase');

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
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Purchase|null $purchase */
            $purchase = $this->route('purchase');
            if (! $purchase) {
                return;
            }

            $amount = (float) $this->input('amount', 0);
            if ($amount > (float) $purchase->due + 0.0001) {
                $validator->errors()->add('amount', __('purchases.payment_exceeds_due'));
            }
        });
    }
}
