<?php

namespace App\Support\Platform;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

final class TenantComplianceExporter
{
    /** @var list<string> */
    private const USER_EXCLUDE = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function datasets(Tenant $tenant): array
    {
        $tenantId = $tenant->getKey();

        return [
            'tenant' => [$this->tenantRecord($tenant)],
            'users' => $this->rows('users', $tenantId, self::USER_EXCLUDE),
            'categories' => $this->rows('categories', $tenantId),
            'manufacturers' => $this->rows('manufacturers', $tenantId),
            'products' => $this->rows('products', $tenantId),
            'product_batches' => $this->rows('product_batches', $tenantId),
            'suppliers' => $this->rows('suppliers', $tenantId),
            'customers' => $this->rows('customers', $tenantId),
            'purchases' => $this->rows('purchases', $tenantId),
            'purchase_lines' => $this->rows('purchase_lines', $tenantId),
            'sales' => $this->rows('sales', $tenantId),
            'sale_lines' => $this->rows('sale_lines', $tenantId),
            'sale_payments' => $this->rows('sale_payments', $tenantId),
            'sale_returns' => $this->rows('sale_returns', $tenantId),
            'sale_return_lines' => $this->rows('sale_return_lines', $tenantId),
            'stock_transfers' => $this->rows('stock_transfers', $tenantId),
            'stock_transfer_lines' => $this->rows('stock_transfer_lines', $tenantId),
            'stock_movements' => $this->rows('stock_movements', $tenantId),
            'discount_coupons' => $this->rows('discount_coupons', $tenantId),
            'employees' => $this->rows('employees', $tenantId),
            'attendances' => $this->rows('attendances', $tenantId),
            'ledger_accounts' => $this->rows('ledger_accounts', $tenantId),
            'ledger_entries' => $this->rows('ledger_entries', $tenantId),
            'tenant_subscriptions' => $this->rows('tenant_subscriptions', $tenantId),
            'activity_log' => $this->activityRows($tenant),
        ];
    }

    public function writeZip(Tenant $tenant): string
    {
        $workDir = storage_path('app/compliance-exports/'.Str::uuid());
        if (! is_dir($workDir) && ! mkdir($workDir, 0755, true) && ! is_dir($workDir)) {
            throw new \RuntimeException('Unable to create export directory.');
        }

        $manifest = [
            'schema_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'tenant_id' => $tenant->getKey(),
            'tenant_slug' => $tenant->slug,
            'tenant_name' => $tenant->name,
            'files' => [],
        ];

        foreach ($this->datasets($tenant) as $name => $rows) {
            $filename = "{$name}.json";
            file_put_contents(
                "{$workDir}/{$filename}",
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]'
            );
            $manifest['files'][] = $filename;
        }

        file_put_contents(
            "{$workDir}/manifest.json",
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}'
        );

        $zipPath = "{$workDir}.zip";
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create export archive.');
        }

        foreach (glob("{$workDir}/*.json") ?: [] as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        foreach (glob("{$workDir}/*.json") ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($workDir);

        return $zipPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantRecord(Tenant $tenant): array
    {
        return [
            'id' => $tenant->getKey(),
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'is_active' => $tenant->is_active,
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
            'suspended_at' => $tenant->suspended_at?->toIso8601String(),
            'deletion_requested_at' => $tenant->deletion_requested_at?->toIso8601String(),
            'data_purged_at' => $tenant->data_purged_at?->toIso8601String(),
            'settings' => $tenant->settings,
            'internal_notes' => $tenant->internal_notes,
            'created_at' => $tenant->created_at?->toIso8601String(),
            'updated_at' => $tenant->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<string>  $excludeColumns
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, int $tenantId, array $excludeColumns = []): array
    {
        if (! $this->tableHasTenantId($table)) {
            return [];
        }

        return DB::table($table)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($excludeColumns) {
                $data = (array) $row;
                foreach ($excludeColumns as $column) {
                    unset($data[$column]);
                }

                return $data;
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activityRows(Tenant $tenant): array
    {
        return DB::table('activity_log')
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->getKey())
                    ->orWhere(fn ($q2) => $q2
                        ->where('subject_type', Tenant::class)
                        ->where('subject_id', $tenant->getKey()));
            })
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function tableHasTenantId(string $table): bool
    {
        return in_array('tenant_id', DB::getSchemaBuilder()->getColumnListing($table), true);
    }
}
