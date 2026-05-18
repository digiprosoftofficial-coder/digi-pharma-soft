<?php

namespace App\Support\Money;

use NumberFormatter;

final class MoneyFormatter
{
    public static function localeFor(string $currency, ?string $appLocale = null): string
    {
        $currency = strtoupper($currency);

        if ($currency === 'BDT') {
            return ($appLocale ?? app()->getLocale()) === 'bn' ? 'bn-BD' : 'en-BD';
        }

        return match ($currency) {
            'USD' => 'en-US',
            'EUR' => 'de-DE',
            'GBP' => 'en-GB',
            'INR' => 'en-IN',
            'SAR' => 'ar-SA',
            default => 'en-US',
        };
    }

    public static function format(float|string|null $amount, string $currency, ?string $locale = null): string
    {
        $currency = strtoupper($currency);
        $locale = $locale ?? self::localeFor($currency);
        $value = (float) ($amount ?? 0);

        if (! class_exists(NumberFormatter::class)) {
            return sprintf('%s %s', $currency, number_format($value, 2));
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($value, $currency);

        if ($formatted === false) {
            return sprintf('%s %s', $currency, number_format($value, 2));
        }

        return $formatted;
    }

    public static function formatCents(int|null $cents, string $currency, ?string $locale = null): string
    {
        return self::format(((int) ($cents ?? 0)) / 100, $currency, $locale);
    }

    public static function symbol(string $currency, ?string $locale = null): string
    {
        $currency = strtoupper($currency);
        $locale = $locale ?? self::localeFor($currency);

        if (! class_exists(NumberFormatter::class)) {
            return $currency;
        }

        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency);

        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return is_string($symbol) && $symbol !== '' ? $symbol : $currency;
    }
}
