<?php

namespace App\Support\Catalog;

enum ProductType: string
{
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Syrup = 'syrup';
    case Injection = 'injection';
    case Cream = 'cream';
    case Drops = 'drops';
    case Bottle = 'bottle';
    case Tube = 'tube';
    case Vial = 'vial';
    case Pack = 'pack';
    case Sachet = 'sachet';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
