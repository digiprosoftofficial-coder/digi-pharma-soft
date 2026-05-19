<?php

namespace App\Support\Catalog;

enum SellUnit: string
{
    case Piece = 'piece';
    case Strip = 'strip';
    case Box = 'box';
    case Carton = 'carton';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
