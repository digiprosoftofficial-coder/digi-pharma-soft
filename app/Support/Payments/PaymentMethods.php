<?php

namespace App\Support\Payments;

use Illuminate\Validation\Rule;

final class PaymentMethods
{
    /** @var list<string> */
    public const VALUES = ['cash', 'bank', 'card', 'bkash', 'nagad', 'mobile'];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return self::VALUES;
    }

    /**
     * @return array<int, string|\Illuminate\Validation\Rules\In>
     */
    public static function rule(): array
    {
        return ['required', 'string', Rule::in(self::VALUES)];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => 'cash', 'label' => __('purchases.payment_cash')],
            ['value' => 'bank', 'label' => __('purchases.payment_bank')],
            ['value' => 'card', 'label' => __('purchases.payment_card')],
            ['value' => 'bkash', 'label' => __('purchases.payment_bkash')],
            ['value' => 'nagad', 'label' => __('purchases.payment_nagad')],
            ['value' => 'mobile', 'label' => __('purchases.payment_mobile')],
        ];
    }

    public static function label(string $method): string
    {
        foreach (self::options() as $option) {
            if ($option['value'] === $method) {
                return $option['label'];
            }
        }

        return $method;
    }
}
