<?php

namespace App\Support\Catalog;

use Illuminate\Support\Str;

/**
 * Maps the published ahmedshahriar / Kaggle Bangladesh medicine CSV
 * onto this app's master-catalog columns.
 */
final class BdMedicineDataset
{
    public const SKU_PREFIX = 'MSTR-BD-';

    /**
     * @param  list<string>  $headers  Already snake_cased
     */
    public static function isKaggleHeaders(array $headers): bool
    {
        return in_array('brand_name', $headers, true)
            && (in_array('brand_id', $headers, true) || in_array('dosage_form', $headers, true));
    }

    /**
     * @param  list<string>  $headers  Already snake_cased
     */
    public static function isMasterHeaders(array $headers): bool
    {
        return in_array('name', $headers, true) && ! self::isKaggleHeaders($headers);
    }

    public static function skuFromBrandId(string $brandId): string
    {
        $digits = preg_replace('/\D+/', '', $brandId) ?? '';

        return self::SKU_PREFIX.($digits !== '' ? $digits : Str::upper(Str::slug($brandId, '')));
    }

    public static function displayName(string $brand, string $strength): string
    {
        $brand = trim($brand);
        $strength = trim($strength);
        if ($strength === '') {
            return $brand;
        }

        if (str_contains(mb_strtolower($brand), mb_strtolower($strength))) {
            return $brand;
        }

        return $brand.' '.$strength;
    }

    public static function productType(string $dosageForm): string
    {
        $form = mb_strtolower(trim($dosageForm));
        if ($form === '') {
            return ProductType::Other->value;
        }

        if (self::contains($form, 'sachet')) {
            return ProductType::Sachet->value;
        }

        if (self::contains($form, 'capsule')) {
            return ProductType::Capsule->value;
        }

        if (self::contains($form, 'tablet', 'mups')) {
            return ProductType::Tablet->value;
        }

        if (self::contains(
            $form,
            'drop',
            'ophthalmic solution',
            'ophthalmic suspension',
            'ophthalmic emulsion',
            'ophthalmic gel',
            'nasal spray',
            'ocular spray',
            'viscous eye',
        )) {
            return ProductType::Drops->value;
        }

        if (self::contains($form, 'injection', 'infusion', 'dialysis')) {
            return ProductType::Injection->value;
        }

        if (self::contains(
            $form,
            'cream',
            'ointment',
            'gel',
            'lotion',
            'paste',
            'rub',
            'shampoo',
            'patch',
            'topical spray',
            'topical powder',
            'topical suspension',
            'topical solution',
            'scalp',
            'medicated bar',
            'nail lacquer',
            'hand rub',
        )) {
            return ProductType::Cream->value;
        }

        if (self::contains(
            $form,
            'syrup',
            'oral suspension',
            'powder for suspension',
            'oral solution',
            'oral emulsion',
            'oral powder',
            'elixir',
            'granules',
            'microgranules',
        )) {
            return ProductType::Syrup->value;
        }

        if (self::contains($form, 'mouthwash', 'liquid', 'solution')) {
            return ProductType::Bottle->value;
        }

        if (self::contains($form, 'inhaler', 'nebuliser', 'nebulizer', 'respirator', 'inhalation')) {
            return ProductType::Pack->value;
        }

        if (self::contains($form, 'vial')) {
            return ProductType::Vial->value;
        }

        if (self::contains($form, 'tube')) {
            return ProductType::Tube->value;
        }

        if (self::contains($form, 'bottle')) {
            return ProductType::Bottle->value;
        }

        return ProductType::Other->value;
    }

    public static function baseUnit(string $productType): string
    {
        return ProductTypeUnitRules::defaultBaseUnit($productType);
    }

    public static function parseUnitPrice(string $package): ?float
    {
        $package = trim($package);
        if ($package === '') {
            return null;
        }

        if (preg_match('/৳\s*([0-9]+(?:,[0-9]{3})*(?:\.[0-9]+)?)/u', $package, $match) === 1) {
            return (float) str_replace(',', '', $match[1]);
        }

        if (preg_match('/(?:tk|bdt)\.?\s*([0-9]+(?:,[0-9]{3})*(?:\.[0-9]+)?)/i', $package, $match) === 1) {
            return (float) str_replace(',', '', $match[1]);
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row  Snake_cased Kaggle columns
     * @param  array<string, string>  $drugClassByGeneric  lowercase generic name => class
     * @return array<string, string>|null
     */
    public static function toMasterRaw(array $row, array $drugClassByGeneric = []): ?array
    {
        $brand = trim($row['brand_name'] ?? '');
        if ($brand === '') {
            return null;
        }

        $strength = trim($row['strength'] ?? '');
        $generic = trim($row['generic'] ?? '');
        $productType = self::productType($row['dosage_form'] ?? '');
        $package = trim($row['package_container'] ?? '');
        $packageSize = trim($row['package_size'] ?? '');
        $mrp = self::parseUnitPrice($package) ?? self::parseUnitPrice($packageSize);
        $brandId = trim($row['brand_id'] ?? '');
        $genericKey = mb_strtolower($generic);

        return [
            'name' => self::displayName($brand, $strength),
            'generic_name' => $generic,
            'strength' => $strength,
            'manufacturer_name' => trim($row['manufacturer'] ?? ''),
            'product_type' => $productType,
            'drug_class' => $drugClassByGeneric[$genericKey] ?? '',
            'base_unit' => self::baseUnit($productType),
            'pieces_per_strip' => '',
            'strips_per_box' => '',
            'boxes_per_carton' => '',
            'sku' => $brandId !== '' ? self::skuFromBrandId($brandId) : '',
            'barcode' => '',
            'mrp' => $mrp !== null ? (string) $mrp : '',
            'default_purchase_price' => '',
            'is_active' => '1',
        ];
    }

    private static function contains(string $haystack, string ...$needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
