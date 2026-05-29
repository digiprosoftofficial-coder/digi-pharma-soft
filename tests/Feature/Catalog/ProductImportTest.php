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
        $this->assertSame(ProductImportCsv::HEADERS, str_getcsv($lines[0]));
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

    private function csvHeaderLine(): string
    {
        return implode(',', ProductImportCsv::HEADERS);
    }
}
