<?php

namespace App\Support\Catalog;

/**
 * Column layout for product bulk CSV import (matches current products table + opening stock).
 */
final class ProductImportCsv
{
    /** @var list<string> */
    public const HEADERS = [
        'name',
        'generic_name',
        'strength',
        'sku',
        'barcode',
        'product_type',
        'base_unit',
        'pieces_per_strip',
        'strips_per_box',
        'boxes_per_carton',
        'category_slug',
        'manufacturer_name',
        'storage_location_code',
        'purchase_price',
        'sale_price',
        'wholesale_price',
        'vat_percent',
        'short_description',
        'min_stock',
        'opening_quantity',
        'opening_batch_no',
        'opening_expiry_date',
        'is_active',
    ];

    /**
     * @return list<list<string>>
     */
    public static function sampleRows(): array
    {
        return [
            [
                'Napa Extend',
                'Paracetamol',
                '500 mg',
                'NAPA-500',
                '8801234567890',
                'tablet',
                'strip',
                '10',
                '',
                '',
                'general',
                'Demo Labs',
                'A1',
                '20',
                '35',
                '30',
                '0',
                'Pain relief tablet',
                '10',
                '50',
                'OPEN-NAPA',
                '2027-12-31',
                '1',
            ],
            [
                'Tusca Syrup 100ml',
                'Ambroxol',
                '15 mg/5 ml',
                'TUS-100',
                '',
                'syrup',
                'piece',
                '',
                '',
                '',
                'general',
                'Demo Labs',
                '',
                '80',
                '120',
                '',
                '5',
                'Cough syrup',
                '5',
                '',
                '',
                '',
                '1',
            ],
        ];
    }
}
