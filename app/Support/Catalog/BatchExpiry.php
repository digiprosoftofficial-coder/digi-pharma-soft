<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\ProductBatch;
use Carbon\CarbonInterface;

final class BatchExpiry
{
    public static function isExpired(ProductBatch|string|null $expiryDate): bool
    {
        if ($expiryDate instanceof ProductBatch) {
            $expiryDate = $expiryDate->expiry_date;
        }

        if ($expiryDate === null || $expiryDate === '') {
            return false;
        }

        $date = $expiryDate instanceof CarbonInterface
            ? $expiryDate->toDateString()
            : (string) $expiryDate;

        return $date < now()->toDateString();
    }

    public static function isSellable(ProductBatch $batch): bool
    {
        return ! self::isExpired($batch) && (float) $batch->quantity_on_hand > 0;
    }
}
