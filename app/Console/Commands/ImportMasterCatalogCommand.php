<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Actions\ImportMasterProductsFromCsvAction;
use App\Domain\Catalog\Actions\TransformBdMedicineCsvAction;
use App\Support\Catalog\BdMedicineDataset;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ImportMasterCatalogCommand extends Command
{
    protected $signature = 'catalog:import-master
        {file : Path to Kaggle medicine.csv or a master-catalog CSV}
        {--from=auto : auto, kaggle, or master}
        {--generics= : Optional Kaggle generic.csv (drug_class lookup)}
        {--dump= : Write the transformed master CSV to this path}
        {--no-update : Skip rows whose SKU already exists}
        {--dry-run : Validate only, do not write to the database}';

    protected $description = 'Import the shared medicine master catalog from a CSV (Kaggle/ahmedshahriar or native master format).';

    public function __construct(
        private readonly TransformBdMedicineCsvAction $transform,
        private readonly ImportMasterProductsFromCsvAction $import,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $file = $this->resolveExistingFile((string) $this->argument('file'));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $from = strtolower((string) $this->option('from'));
        if (! in_array($from, ['auto', 'kaggle', 'master'], true)) {
            $this->error('Invalid --from. Use auto, kaggle, or master.');

            return self::FAILURE;
        }

        try {
            $format = $from === 'auto' ? $this->detectFormat($file) : $from;
            $mapped = $format === 'kaggle'
                ? $this->mapKaggle($file)
                : $this->mapMaster($file);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $dump = $this->option('dump');
        if (is_string($dump) && $dump !== '') {
            $dumpPath = $this->resolveWritablePath($dump);
            $this->transform->dump($mapped, $dumpPath);
            $this->info("Wrote transformed CSV: {$dumpPath}");
        }

        $preview = $this->import->previewFromRows($mapped['headers'], $mapped['rows']);

        $this->line("Source rows: {$mapped['source_rows']}");
        if ($mapped['skipped'] > 0) {
            $this->warn("Skipped empty/invalid source rows: {$mapped['skipped']}");
        }
        $this->line("Valid: {$preview['valid_count']}");
        $this->line("Invalid: {$preview['error_count']}");

        if ($preview['error_count'] > 0) {
            $shown = 0;
            foreach ($preview['rows'] as $entry) {
                if ($entry['errors'] === []) {
                    continue;
                }
                $this->warn('Row '.$entry['row'].': '.implode('; ', $entry['errors']));
                $shown++;
                if ($shown >= 10) {
                    $remaining = $preview['error_count'] - $shown;
                    if ($remaining > 0) {
                        $this->warn("…and {$remaining} more invalid rows.");
                    }
                    break;
                }
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes.');

            return self::SUCCESS;
        }

        $updateExisting = ! $this->option('no-update');
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $chunks = array_chunk($preview['rows'], 500);
        $bar = $this->output->createProgressBar(count($preview['rows']));
        $bar->start();

        foreach ($chunks as $chunk) {
            $result = $this->import->executeFromPreview([
                'headers' => $preview['headers'],
                'rows' => $chunk,
                'valid_count' => 0,
                'error_count' => 0,
            ], $updateExisting);
            $created += $result['created'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $errors += count($result['errors']);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Import finished: {$created} created, {$updated} updated, {$skipped} skipped, {$errors} invalid.");

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     headers: list<string>,
     *     rows: list<array{row: int, raw: array<string, string>}>,
     *     skipped: int,
     *     source_rows: int
     * }
     */
    private function mapKaggle(string $file): array
    {
        $genericsOption = $this->option('generics');
        $generics = null;
        if (is_string($genericsOption) && $genericsOption !== '') {
            $generics = $this->resolveExistingFile($genericsOption);
        } else {
            $sibling = dirname($file).DIRECTORY_SEPARATOR.'generic.csv';
            if (is_file($sibling)) {
                $generics = $sibling;
            }
        }

        if ($generics !== null) {
            $this->line("Using generic lookup: {$generics}");
        }

        return $this->transform->mapFile($file, $generics);
    }

    /**
     * @return array{
     *     headers: list<string>,
     *     rows: list<array{row: int, raw: array<string, string>}>,
     *     skipped: int,
     *     source_rows: int
     * }
     */
    private function mapMaster(string $file): array
    {
        $preview = $this->import->previewFromPath($file);

        return [
            'headers' => $preview['headers'],
            'rows' => array_map(
                fn (array $entry) => ['row' => $entry['row'], 'raw' => $entry['raw']],
                $preview['rows'],
            ),
            'skipped' => 0,
            'source_rows' => count($preview['rows']),
        ];
    }

    private function detectFormat(string $file): string
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to read CSV: {$file}");
        }
        $headers = fgetcsv($handle) ?: [];
        fclose($handle);

        $normalized = array_map(function ($header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? (string) $header;

            return Str::snake(trim($header));
        }, $headers);

        if (BdMedicineDataset::isKaggleHeaders($normalized)) {
            return 'kaggle';
        }
        if (BdMedicineDataset::isMasterHeaders($normalized)) {
            return 'master';
        }

        throw new RuntimeException('Could not detect CSV format. Pass --from=kaggle or --from=master.');
    }

    private function resolveExistingFile(string $file): string
    {
        foreach ($this->candidatePaths($file) as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException("CSV not found: {$file}");
    }

    private function resolveWritablePath(string $file): string
    {
        if (str_starts_with($file, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $file) === 1) {
            return $file;
        }

        return base_path($file);
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $file): array
    {
        $paths = [$file, base_path($file), storage_path('app/'.$file)];
        if (! str_contains($file, DIRECTORY_SEPARATOR) && ! str_starts_with($file, 'bd-medicine')) {
            $paths[] = storage_path('app/bd-medicine/'.$file);
        }

        return array_values(array_unique($paths));
    }
}
