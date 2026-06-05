<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use App\Support\Catalog\ProductImportCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_csv_download_has_current_columns(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/catalog/import/sample');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $lines = preg_split('/\r\n|\n|\r/', $response->streamedContent());
        // Seeded plan has default 'pro' preset with advanced_catalog enabled but
        // wholesale_pricing disabled, so wholesale_price is filtered out.
        $expected = ProductImportCsv::columnsForPreset('pro', true, false, null);
        $this->assertSame($expected, str_getcsv($lines[0]));
        $this->assertNotContains('wholesale_price', str_getcsv($lines[0]));
        $this->assertNotEmpty(str_getcsv($lines[1]));
    }

    public function test_import_preview_returns_csv_columns_and_raw_values(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $csv = $this->csvHeaderLine()."\n";
        $csv .= 'Vitamin C 500mg,Ascorbic acid,500 mg,VIT-C-500,,tablet,strip,10,,,general,Demo Labs,,10,18,,,Immune support,5,,,,1'."\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($user)
            ->from(route('tenant.catalog.import.index'))
            ->post('/catalog/import/preview', ['file' => $file])
            ->assertRedirect(route('tenant.catalog.import.index'))
            ->assertSessionHas('import_preview', fn (array $preview) => $preview['headers'] === ProductImportCsv::HEADERS
                && $preview['rows'][0]['raw']['name'] === 'Vitamin C 500mg'
                && $preview['rows'][0]['raw']['generic_name'] === 'Ascorbic acid'
                && $preview['rows'][0]['raw']['sku'] === 'VIT-C-500');
    }

    public function test_owner_can_import_products_from_csv(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $csv = $this->csvHeaderLine()."\n";
        $csv .= 'Vitamin C 500mg,Ascorbic acid,500 mg,VIT-C-500,,tablet,strip,10,,,general,Demo Labs,,10,18,,,Immune support,5,,,,1'."\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($user)
            ->post('/catalog/import', ['file' => $file, 'skip_duplicates' => true])
            ->assertRedirect(route('tenant.catalog.import.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'VIT-C-500',
            'generic_name' => 'Ascorbic acid',
            'strength' => '500 mg',
        ]);
        $this->assertSame(1, Product::query()->where('sku', 'VIT-C-500')->count());
    }

    public function test_basic_preset_limits_available_columns(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = \App\Domain\Tenant\Models\Tenant::query()->firstOrFail();

        // Set plan to basic preset
        $plan = $tenant->activeSubscription?->plan;
        $plan->features = array_merge($plan->features ?? [], ['import_preset' => 'basic']);
        $plan->save();
        $tenant->refresh();

        $response = $this->actingAs($user)->get('/catalog/import/sample');
        $response->assertOk();

        $lines = preg_split('/\r\n|\n|\r/', $response->streamedContent());
        $columns = str_getcsv($lines[0]);

        // Basic preset should have limited columns
        $this->assertContains('name', $columns);
        $this->assertContains('sku', $columns);
        $this->assertContains('purchase_price', $columns);
        $this->assertContains('sale_price', $columns);
        // Advanced columns should NOT be in basic
        $this->assertNotContains('generic_name', $columns);
        $this->assertNotContains('strength', $columns);
        $this->assertNotContains('opening_quantity', $columns);
    }

    public function test_custom_preset_uses_selected_columns(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = \App\Domain\Tenant\Models\Tenant::query()->firstOrFail();

        // Set plan to custom preset with specific columns
        $plan = $tenant->activeSubscription?->plan;
        $plan->features = array_merge($plan->features ?? [], [
            'import_preset' => 'custom',
            'import_columns' => ['name', 'sku', 'product_type', 'base_unit', 'purchase_price', 'sale_price', 'generic_name'],
        ]);
        $plan->save();
        $tenant->refresh();

        $response = $this->actingAs($user)->get('/catalog/import/sample');
        $response->assertOk();

        $lines = preg_split('/\r\n|\n|\r/', $response->streamedContent());
        $columns = str_getcsv($lines[0]);

        $this->assertCount(7, $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('generic_name', $columns);
        $this->assertNotContains('strength', $columns);
    }

    public function test_import_revalidate_updates_preview_after_edit(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $headers = ProductImportCsv::HEADERS;

        $this->actingAs($user)
            ->from(route('tenant.catalog.import.index'))
            ->post('/catalog/import/revalidate', [
                'headers' => $headers,
                'rows' => [[
                    'row' => 2,
                    'raw' => [
                        'name' => '',
                        'generic_name' => 'Bad',
                        'strength' => '',
                        'sku' => 'BAD-1',
                        'barcode' => '',
                        'product_type' => 'tablet',
                        'base_unit' => 'strip',
                        'pieces_per_strip' => '',
                        'strips_per_box' => '',
                        'boxes_per_carton' => '',
                        'category_slug' => 'unknown-slug',
                        'manufacturer_name' => '',
                        'storage_location_code' => '',
                        'purchase_price' => '10',
                        'sale_price' => '12',
                        'wholesale_price' => '',
                        'vat_percent' => '',
                        'short_description' => '',
                        'min_stock' => '0',
                        'opening_quantity' => '',
                        'opening_batch_no' => '',
                        'opening_expiry_date' => '',
                        'is_active' => '1',
                    ],
                ]],
            ])
            ->assertRedirect(route('tenant.catalog.import.index'))
            ->assertSessionHas('import_preview');

        $badPreview = session('import_preview');
        $this->assertGreaterThanOrEqual(1, $badPreview['error_count']);
        $this->assertSame(0, $badPreview['valid_count']);

        $this->actingAs($user)
            ->from(route('tenant.catalog.import.index'))
            ->post('/catalog/import/revalidate', [
                'headers' => $headers,
                'rows' => [[
                    'row' => 2,
                    'raw' => [
                        'name' => 'Fixed Vitamin',
                        'generic_name' => 'Fixed Generic',
                        'strength' => '500 mg',
                        'sku' => 'FIX-1',
                        'barcode' => '',
                        'product_type' => 'tablet',
                        'base_unit' => 'strip',
                        'pieces_per_strip' => '',
                        'strips_per_box' => '',
                        'boxes_per_carton' => '',
                        'category_slug' => 'general',
                        'manufacturer_name' => '',
                        'storage_location_code' => '',
                        'purchase_price' => '10',
                        'sale_price' => '12',
                        'wholesale_price' => '',
                        'vat_percent' => '',
                        'short_description' => '',
                        'min_stock' => '0',
                        'opening_quantity' => '',
                        'opening_batch_no' => '',
                        'opening_expiry_date' => '',
                        'is_active' => '1',
                    ],
                ]],
            ])
            ->assertRedirect(route('tenant.catalog.import.index'))
            ->assertSessionHas('import_preview', fn (array $preview) => $preview['valid_count'] === 1
                && $preview['error_count'] === 0);
    }

    public function test_import_from_edited_rows_without_file(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $headers = ProductImportCsv::HEADERS;
        $raw = [
            'name' => 'Edited Import Product',
            'generic_name' => 'Edited Generic',
            'strength' => '250 mg',
            'sku' => 'EDIT-IMP-1',
            'barcode' => '',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'pieces_per_strip' => '',
            'strips_per_box' => '',
            'boxes_per_carton' => '',
            'category_slug' => 'general',
            'manufacturer_name' => '',
            'storage_location_code' => '',
            'purchase_price' => '8',
            'sale_price' => '10',
            'wholesale_price' => '',
            'vat_percent' => '',
            'short_description' => '',
            'min_stock' => '0',
            'opening_quantity' => '',
            'opening_batch_no' => '',
            'opening_expiry_date' => '',
            'is_active' => '1',
        ];

        $this->actingAs($user)
            ->post('/catalog/import', [
                'headers' => $headers,
                'rows' => [['row' => 2, 'raw' => $raw]],
                'skip_duplicates' => true,
            ])
            ->assertRedirect(route('tenant.catalog.import.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'EDIT-IMP-1',
            'name' => 'Edited Import Product',
        ]);
    }

    private function csvHeaderLine(): string
    {
        return implode(',', ProductImportCsv::HEADERS);
    }
}
