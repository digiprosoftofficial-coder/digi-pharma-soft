<?php

namespace App\Support\Money;

use Illuminate\Validation\Rule;

final class SupportedCurrencies
{
    /** @var list<string> */
    public const CODES = ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'SAR'];

    public static function validationRule(): \Illuminate\Validation\Rules\In
    {
        return Rule::in(self::CODES);
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return self::CODES;
    }
}
