<?php

namespace App\Support\Catalog;

/**
 * Column layout for Superadmin master-medicine CSV import.
 */
final class MasterProductImportCsv
{
    /** @var list<string> */
    public const HEADERS = [
        'name',
        'generic_name',
        'strength',
        'manufacturer_name',
        'product_type',
        'drug_class',
        'base_unit',
        'pieces_per_strip',
        'strips_per_box',
        'boxes_per_carton',
        'sku',
        'barcode',
        'mrp',
        'default_purchase_price',
        'is_active',
    ];

    /**
     * @return list<list<string>>
     */
    public static function sampleRows(): array
    {
        return [
            [
                'Napa 500mg',
                'Paracetamol',
                '500 mg',
                'Beximco Pharmaceuticals',
                'tablet',
                'Analgesic & antipyretic',
                'strip',
                '10',
                '12',
                '',
                'MSTR-NAPA-500',
                '',
                '12',
                '10',
                '1',
            ],
            [
                'Seclo 20mg',
                'Omeprazole',
                '20 mg',
                'Square Pharmaceuticals',
                'capsule',
                'Gastrointestinal system drugs',
                'strip',
                '10',
                '10',
                '',
                'MSTR-SECLO-20',
                '',
                '70',
                '60',
                '1',
            ],
        ];
    }
}
