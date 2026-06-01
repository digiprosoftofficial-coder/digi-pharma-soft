<?php

namespace App\Support\Catalog;

/**
 * Column layout for product bulk CSV import (matches current products table + opening stock).
 *
 * Some columns are only usable when the tenant's plan enables the matching feature
 * (advanced_catalog, wholesale_pricing); those are filtered out of the sample and
 * column list so the import surface stays plan-aware.
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

    /** Columns gated behind the advanced_catalog feature. @var list<string> */
    public const ADVANCED_HEADERS = [
        'generic_name',
        'strength',
        'vat_percent',
        'short_description',
    ];

    /** Column gated behind the wholesale_pricing feature. */
    public const WHOLESALE_HEADER = 'wholesale_price';

    /** Available import presets. */
    public const PRESET_BASIC = 'basic';

    public const PRESET_STANDARD = 'standard';

    public const PRESET_PRO = 'pro';

    public const PRESET_CUSTOM = 'custom';

    /** @var list<string> */
    public const PRESETS = [
        self::PRESET_BASIC,
        self::PRESET_STANDARD,
        self::PRESET_PRO,
        self::PRESET_CUSTOM,
    ];

    /** Columns included in the basic preset. @var list<string> */
    public const BASIC_COLUMNS = [
        'name',
        'sku',
        'barcode',
        'product_type',
        'base_unit',
        'purchase_price',
        'sale_price',
        'min_stock',
        'is_active',
    ];

    /** Columns included in the standard preset. @var list<string> */
    public const STANDARD_COLUMNS = [
        'name',
        'generic_name',
        'strength',
        'sku',
        'barcode',
        'product_type',
        'base_unit',
        'pieces_per_strip',
        'category_slug',
        'manufacturer_name',
        'purchase_price',
        'sale_price',
        'min_stock',
        'opening_quantity',
        'opening_batch_no',
        'opening_expiry_date',
        'is_active',
    ];

    /**
     * Columns for a given preset, respecting feature flags.
     *
     * @param  list<string>|null  $customColumns  Only used when preset is 'custom'.
     * @return list<string>
     */
    public static function columnsForPreset(
        string $preset,
        bool $advancedCatalog,
        bool $wholesalePricing,
        ?array $customColumns = null,
    ): array {
        $allowed = match ($preset) {
            self::PRESET_BASIC => self::BASIC_COLUMNS,
            self::PRESET_STANDARD => self::STANDARD_COLUMNS,
            self::PRESET_PRO => self::HEADERS,
            self::PRESET_CUSTOM => $customColumns ?? self::BASIC_COLUMNS,
            default => self::BASIC_COLUMNS,
        };

        return array_values(array_filter($allowed, static function (string $col) use ($advancedCatalog, $wholesalePricing): bool {
            if (! $advancedCatalog && in_array($col, self::ADVANCED_HEADERS, true)) {
                return false;
            }
            if (! $wholesalePricing && $col === self::WHOLESALE_HEADER) {
                return false;
            }

            return in_array($col, self::HEADERS, true);
        }));
    }

    /**
     * Human-readable preset labels keyed by preset slug.
     *
     * @return array<string, string>
     */
    public static function presetLabels(): array
    {
        return [
            self::PRESET_BASIC => __('catalog.import_preset_basic'),
            self::PRESET_STANDARD => __('catalog.import_preset_standard'),
            self::PRESET_PRO => __('catalog.import_preset_pro'),
            self::PRESET_CUSTOM => __('catalog.import_preset_custom'),
        ];
    }

    /**
     * Column list for the given feature set.
     *
     * @return list<string>
     */
    public static function headersFor(bool $advanced, bool $wholesale): array
    {
        return array_values(array_filter(self::HEADERS, static function (string $header) use ($advanced, $wholesale): bool {
            if (! $advanced && in_array($header, self::ADVANCED_HEADERS, true)) {
                return false;
            }

            if (! $wholesale && $header === self::WHOLESALE_HEADER) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Sample rows keyed by header.
     *
     * @return list<array<string, string>>
     */
    public static function sampleRows(): array
    {
        return [
            [
                'name' => 'Napa Extend',
                'generic_name' => 'Paracetamol',
                'strength' => '500 mg',
                'sku' => 'NAPA-500',
                'barcode' => '8801234567890',
                'product_type' => 'tablet',
                'base_unit' => 'strip',
                'pieces_per_strip' => '10',
                'strips_per_box' => '',
                'boxes_per_carton' => '',
                'category_slug' => 'general',
                'manufacturer_name' => 'Demo Labs',
                'storage_location_code' => 'A1',
                'purchase_price' => '20',
                'sale_price' => '35',
                'wholesale_price' => '30',
                'vat_percent' => '0',
                'short_description' => 'Pain relief tablet',
                'min_stock' => '10',
                'opening_quantity' => '50',
                'opening_batch_no' => 'OPEN-NAPA',
                'opening_expiry_date' => '2027-12-31',
                'is_active' => '1',
            ],
            [
                'name' => 'Tusca Syrup 100ml',
                'generic_name' => 'Ambroxol',
                'strength' => '15 mg/5 ml',
                'sku' => 'TUS-100',
                'barcode' => '',
                'product_type' => 'syrup',
                'base_unit' => 'piece',
                'pieces_per_strip' => '',
                'strips_per_box' => '',
                'boxes_per_carton' => '',
                'category_slug' => 'general',
                'manufacturer_name' => 'Demo Labs',
                'storage_location_code' => '',
                'purchase_price' => '80',
                'sale_price' => '120',
                'wholesale_price' => '',
                'vat_percent' => '5',
                'short_description' => 'Cough syrup',
                'min_stock' => '5',
                'opening_quantity' => '',
                'opening_batch_no' => '',
                'opening_expiry_date' => '',
                'is_active' => '1',
            ],
        ];
    }

    /**
     * Sample rows reduced to the given headers.
     *
     * @param  list<string>  $headers
     * @return list<list<string>>
     */
    public static function sampleRowsFor(array $headers): array
    {
        return array_map(
            static fn (array $row): array => array_map(
                static fn (string $header): string => $row[$header] ?? '',
                $headers,
            ),
            self::sampleRows(),
        );
    }
}
