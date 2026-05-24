<?php

namespace App\Support\Catalog;

final class ProductListPagination
{
    public const DEFAULT = 25;

    /** @var list<int> */
    public const ALLOWED = [15, 25, 50, 100];

    public static function resolve(?int $requested): int
    {
        if ($requested !== null && in_array($requested, self::ALLOWED, true)) {
            return $requested;
        }

        return self::DEFAULT;
    }

    /**
     * @return list<int>
     */
    public static function options(): array
    {
        return self::ALLOWED;
    }
}
