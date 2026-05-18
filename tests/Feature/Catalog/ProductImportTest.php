<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_import_products_from_csv(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $csv = "name,sku,barcode,product_type,base_unit,category_slug,manufacturer_name,purchase_price,sale_price,min_stock,is_active\n";
        $csv .= "Vitamin C 500mg,VIT-C-500,,tablet,strip,general,Demo Labs,10,18,5,1\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAs($user)
            ->post('/catalog/import', ['file' => $file, 'skip_duplicates' => true])
            ->assertRedirect(route('tenant.catalog.import.index'));

        $this->assertDatabaseHas('products', ['sku' => 'VIT-C-500']);
        $this->assertSame(1, Product::query()->where('sku', 'VIT-C-500')->count());
    }
}
