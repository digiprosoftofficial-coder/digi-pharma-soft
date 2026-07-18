<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\MasterProduct;
use App\Support\Catalog\MasterProductImportCsv;
use App\Support\Catalog\ProductCatalogOptions;
use App\Support\Catalog\ProductType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class ImportMasterProductsFromCsvAction
{
    public const MAX_ROWS = 5000;

    /**
     * @return array{headers:list<string>,rows:list<array{row:int,data:array<string,mixed>,raw:array<string,string>,errors:list<string>}>,valid_count:int,error_count:int}
     */
    public function preview(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['headers' => [], 'rows' => [], 'valid_count' => 0, 'error_count' => 0];
        }

        $headers = fgetcsv($handle) ?: [];
        $headers = array_map(fn ($h) => Str::snake(trim((string) $h)), $headers);

        $rawRows = [];
        $rowNum = 1;
        while (($cols = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($this->rowIsEmpty($cols)) {
                continue;
            }
            $raw = [];
            foreach ($headers as $i => $key) {
                $raw[$key] = trim((string) ($cols[$i] ?? ''));
            }
            $rawRows[] = ['row' => $rowNum, 'raw' => $raw];
        }
        fclose($handle);

        return $this->previewFromRows($headers, $rawRows);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array{row:int,raw:array<string,string>}>  $rows
     * @return array{headers:list<string>,rows:list<array{row:int,data:array<string,mixed>,raw:array<string,string>,errors:list<string>}>,valid_count:int,error_count:int}
     */
    public function previewFromRows(array $headers, array $rows): array
    {
        $known = array_flip(MasterProductImportCsv::HEADERS);
        $normalizedHeaders = array_values(array_filter(
            array_map(fn ($h) => Str::snake(trim((string) $h)), $headers),
            fn ($h) => isset($known[$h]),
        ));

        $out = [];
        $valid = 0;
        $errors = 0;

        foreach ($rows as $entry) {
            $raw = [];
            foreach ($normalizedHeaders as $h) {
                $raw[$h] = trim((string) ($entry['raw'][$h] ?? ''));
            }

            [$data, $rowErrors] = $this->validateRow($raw);
            if ($rowErrors === []) {
                $valid++;
            } else {
                $errors++;
            }

            $out[] = [
                'row' => (int) $entry['row'],
                'data' => $data,
                'raw' => $raw,
                'errors' => $rowErrors,
            ];
        }

        return [
            'headers' => $normalizedHeaders !== [] ? $normalizedHeaders : MasterProductImportCsv::HEADERS,
            'rows' => $out,
            'valid_count' => $valid,
            'error_count' => $errors,
        ];
    }

    /**
     * @param  array{headers:list<string>,rows:list<array{row:int,data:array<string,mixed>,raw:array<string,string>,errors:list<string>}>,valid_count:int,error_count:int}  $preview
     * @return array{created:int,updated:int,skipped:int,errors:list<array{row:int,messages:list<string>}>}
     */
    public function executeFromPreview(array $preview, bool $updateExisting = true): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($preview['rows'] as $entry) {
            if (! empty($entry['errors'])) {
                $errors[] = ['row' => $entry['row'], 'messages' => $entry['errors']];

                continue;
            }

            $data = $entry['data'];
            $existing = $this->findExisting($data);

            if ($existing) {
                if (! $updateExisting) {
                    $skipped++;

                    continue;
                }

                $existing->fill($data)->save();
                $updated++;

                continue;
            }

            if (blank($data['sku'] ?? null)) {
                $data['sku'] = $this->uniqueSku($data['name']);
            }

            MasterProduct::query()->create($data);
            $created++;
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @param  array<string, string>  $raw
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function validateRow(array $raw): array
    {
        $errors = [];

        $name = trim((string) ($raw['name'] ?? ''));
        if ($name === '') {
            $errors[] = __('platform.master_import_name_required');
        }

        $productType = strtolower(trim((string) ($raw['product_type'] ?? 'other'))) ?: 'other';
        if (! in_array($productType, ProductType::values(), true)) {
            $errors[] = __('platform.master_import_invalid_type');
        }

        $baseUnit = strtolower(trim((string) ($raw['base_unit'] ?? 'strip'))) ?: 'strip';
        if (! in_array($baseUnit, ProductCatalogOptions::sellUnits(), true)) {
            $errors[] = __('platform.master_import_invalid_unit');
        }

        $mrp = $this->decimal($raw['mrp'] ?? '0');
        $purchase = $this->decimal($raw['default_purchase_price'] ?? '');
        if ($purchase === null) {
            $purchase = round(($mrp ?? 0) * 0.85, 4);
        }

        $isActiveRaw = strtolower(trim((string) ($raw['is_active'] ?? '1')));
        $isActive = ! in_array($isActiveRaw, ['0', 'false', 'no', 'inactive'], true);

        $data = [
            'name' => $name,
            'generic_name' => $this->nullableString($raw['generic_name'] ?? null),
            'strength' => $this->nullableString($raw['strength'] ?? null),
            'manufacturer_name' => $this->nullableString($raw['manufacturer_name'] ?? null),
            'product_type' => $productType,
            'drug_class' => $this->nullableString($raw['drug_class'] ?? null),
            'base_unit' => $baseUnit,
            'pieces_per_strip' => $this->decimal($raw['pieces_per_strip'] ?? null),
            'strips_per_box' => $this->decimal($raw['strips_per_box'] ?? null),
            'boxes_per_carton' => $this->decimal($raw['boxes_per_carton'] ?? null),
            'sku' => $this->nullableString($raw['sku'] ?? null),
            'barcode' => $this->nullableString($raw['barcode'] ?? null),
            'mrp' => $mrp ?? 0,
            'default_purchase_price' => $purchase,
            'is_active' => $isActive,
        ];

        return [$data, $errors];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findExisting(array $data): ?MasterProduct
    {
        if (filled($data['sku'] ?? null)) {
            $bySku = MasterProduct::query()->where('sku', $data['sku'])->first();
            if ($bySku) {
                return $bySku;
            }
        }

        if (filled($data['barcode'] ?? null)) {
            return MasterProduct::query()->where('barcode', $data['barcode'])->first();
        }

        return null;
    }

    private function uniqueSku(string $name): string
    {
        $base = 'MSTR-'.Str::upper(Str::slug(Str::limit($name, 40, ''), ''));
        $candidate = $base !== 'MSTR-' ? $base : 'MSTR-'.Str::upper(Str::random(8));
        $suffix = 1;

        while (MasterProduct::query()->where('sku', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function nullableString(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? null : $v;
    }

    private function decimal(mixed $value): ?float
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '') {
            return null;
        }
        if (! is_numeric($v)) {
            return null;
        }

        return (float) $v;
    }

    /**
     * @param  list<mixed>  $cols
     */
    private function rowIsEmpty(array $cols): bool
    {
        foreach ($cols as $col) {
            if (trim((string) $col) !== '') {
                return false;
            }
        }

        return true;
    }
}
