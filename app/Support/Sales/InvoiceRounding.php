<?php

namespace App\Support\Sales;

use App\Domain\Tenant\Models\Tenant;

final class InvoiceRounding
{
    public const NONE = 'none';

    public const NEAREST_1 = 'nearest_1';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::NONE => __('sales.rounding_none'),
            self::NEAREST_1 => __('sales.rounding_nearest_1'),
        ];
    }

    public static function resolve(?Tenant $tenant): string
    {
        $setting = $tenant?->settings['invoice_rounding'] ?? null;

        return in_array($setting, [self::NONE, self::NEAREST_1], true) ? $setting : self::NONE;
    }

    /**
     * Apply rounding to a total.
     *
     * @return array{rounded_total: float, round_adjustment: float}
     */
    public static function apply(float $total, string $mode): array
    {
        if ($mode === self::NEAREST_1) {
            $rounded = round($total);
            $adjustment = round($rounded - $total, 4);

            return [
                'rounded_total' => $rounded,
                'round_adjustment' => $adjustment,
            ];
        }

        return [
            'rounded_total' => $total,
            'round_adjustment' => 0.0,
        ];
    }
}
