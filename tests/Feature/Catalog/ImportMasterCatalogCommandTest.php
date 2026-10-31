<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\MasterProduct;
use Tests\TestCase;

class ImportMasterCatalogCommandTest extends TestCase
{
    public function test_artisan_import_transforms_kaggle_csv_and_upserts_by_sku(): void
    {
        $medicine = base_path('tests/fixtures/bd-medicine/medicine.csv');
        $generics = base_path('tests/fixtures/bd-medicine/generic.csv');
        $dump = storage_path('app/testing-bd-medicines.csv');

        $this->artisan('catalog:import-master', [
            'file' => $medicine,
            '--generics' => $generics,
            '--dump' => $dump,
        ])
            ->assertSuccessful();

        $this->assertFileExists($dump);
        $this->assertStringContainsString('Napa 500 mg', (string) file_get_contents($dump));

        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-BD-10452',
            'name' => 'Napa 500 mg',
            'generic_name' => 'Paracetamol',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'drug_class' => 'Analgesic & antipyretic',
            'mrp' => '1.2000',
        ]);
        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-BD-4077',
            'name' => 'A-Cold 4 mg/5 ml',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
        ]);
        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-BD-610',
            'product_type' => 'injection',
            'base_unit' => 'piece',
        ]);

        $napa = MasterProduct::query()->where('sku', 'MSTR-BD-10452')->firstOrFail();
        $this->assertEqualsWithDelta(1.02, (float) $napa->default_purchase_price, 0.001);

        MasterProduct::query()->where('sku', 'MSTR-BD-10452')->update(['name' => 'Old Napa']);

        $this->artisan('catalog:import-master', [
            'file' => $medicine,
            '--generics' => $generics,
        ])->assertSuccessful();

        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-BD-10452',
            'name' => 'Napa 500 mg',
        ]);
        $this->assertSame(4, MasterProduct::query()->count());
    }

    public function test_dry_run_does_not_write_rows(): void
    {
        $this->artisan('catalog:import-master', [
            'file' => base_path('tests/fixtures/bd-medicine/medicine.csv'),
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('master_products', 0);
    }

    public function test_missing_file_fails(): void
    {
        $this->artisan('catalog:import-master', [
            'file' => 'storage/app/bd-medicine/does-not-exist.csv',
        ])->assertFailed();
    }
}
