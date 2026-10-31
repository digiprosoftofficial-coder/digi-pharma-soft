<?php

namespace App\Domain\Catalog\Actions;

use App\Support\Catalog\BdMedicineDataset;
use App\Support\Catalog\MasterProductImportCsv;
use Illuminate\Support\Str;
use RuntimeException;

final class TransformBdMedicineCsvAction
{
    /**
     * @return array{
     *     headers: list<string>,
     *     rows: list<array{row: int, raw: array<string, string>}>,
     *     skipped: int,
     *     source_rows: int
     * }
     */
    public function mapFile(string $medicinePath, ?string $genericPath = null): array
    {
        $handle = fopen($medicinePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to read medicine CSV: {$medicinePath}");
        }

        $headers = $this->readHeaders($handle);
        if (! BdMedicineDataset::isKaggleHeaders($headers)) {
            fclose($handle);
            throw new RuntimeException('CSV is not the Kaggle/ahmedshahriar medicine format (expected brand name, dosage form).');
        }

        $drugClasses = $genericPath !== null && is_file($genericPath)
            ? $this->loadDrugClasses($genericPath)
            : [];

        $rows = [];
        $skipped = 0;
        $sourceRows = 0;
        $rowNum = 1;

        while (($cols = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($this->rowIsEmpty($cols)) {
                continue;
            }

            $sourceRows++;
            $raw = [];
            foreach ($headers as $i => $key) {
                $raw[$key] = trim((string) ($cols[$i] ?? ''));
            }

            $mapped = BdMedicineDataset::toMasterRaw($raw, $drugClasses);
            if ($mapped === null) {
                $skipped++;

                continue;
            }

            $rows[] = [
                'row' => $rowNum,
                'raw' => $mapped,
            ];
        }

        fclose($handle);

        return [
            'headers' => MasterProductImportCsv::HEADERS,
            'rows' => $rows,
            'skipped' => $skipped,
            'source_rows' => $sourceRows,
        ];
    }

    /**
     * @param  array{headers: list<string>, rows: list<array{row: int, raw: array<string, string>}>}  $mapped
     */
    public function dump(array $mapped, string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Unable to create directory: {$dir}");
        }

        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new RuntimeException("Unable to write CSV: {$path}");
        }

        fputcsv($handle, $mapped['headers']);
        foreach ($mapped['rows'] as $entry) {
            $line = [];
            foreach ($mapped['headers'] as $header) {
                $line[] = $entry['raw'][$header] ?? '';
            }
            fputcsv($handle, $line);
        }
        fclose($handle);
    }

    /**
     * @return array<string, string> lowercase generic name => drug class
     */
    public function loadDrugClasses(string $genericPath): array
    {
        $handle = fopen($genericPath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to read generic CSV: {$genericPath}");
        }

        $headers = $this->readHeaders($handle);
        $nameIdx = array_search('generic_name', $headers, true);
        $classIdx = array_search('drug_class', $headers, true);
        if ($nameIdx === false || $classIdx === false) {
            fclose($handle);

            return [];
        }

        $map = [];
        while (($cols = fgetcsv($handle)) !== false) {
            $name = mb_strtolower(trim((string) ($cols[$nameIdx] ?? '')));
            $class = trim((string) ($cols[$classIdx] ?? ''));
            if ($name === '' || $class === '' || isset($map[$name])) {
                continue;
            }
            $map[$name] = $class;
        }
        fclose($handle);

        return $map;
    }

    /**
     * @param  resource  $handle
     * @return list<string>
     */
    private function readHeaders($handle): array
    {
        $headers = fgetcsv($handle) ?: [];

        return array_map(function ($header) {
            $header = (string) $header;
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

            return Str::snake(trim($header));
        }, $headers);
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
