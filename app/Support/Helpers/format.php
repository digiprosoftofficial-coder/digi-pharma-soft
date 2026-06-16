<?php

use App\Support\Catalog\ProductStockCalculator;
use App\Support\Money\MoneyFormatter;

if (! function_exists('display_qty')) {
    function display_qty(float|string|null $value): string
    {
        return ProductStockCalculator::formatQuantity((float) ($value ?? 0));
    }
}

if (! function_exists('display_money')) {
    function display_money(float|string|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $currency ?? tenant()?->currency() ?? 'BDT';

        return MoneyFormatter::format($amount, $currency, $locale);
    }
}
