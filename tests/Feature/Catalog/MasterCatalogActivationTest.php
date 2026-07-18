<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterCatalogActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_catalog_search_returns_results(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        MasterProduct::query()->create([
            'name' => 'Napa 500mg',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'manufacturer_name' => 'Beximco Pharmaceuticals',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'pieces_per_strip' => 10,
            'sku' => 'MSTR-NAPA-500',
            'mrp' => 12,
            'default_purchase_price' => 10,
        ]);

        $response = $this->actingAs($user)->getJson('/catalog/master/search?q=Paracetamol');

        $response->assertOk();
        $item = collect($response->json('data'))->firstWhere('sku', 'MSTR-NAPA-500');
        $this->assertNotNull($item);
        $this->assertFalse($item['is_activated']);
    }

    public function test_activation_creates_tenant_product_with_units_and_is_idempotent(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $master = MasterProduct::query()->create([
            'name' => 'Seclo 20mg',
            'generic_name' => 'Omeprazole',
            'strength' => '20 mg',
            'manufacturer_name' => 'Square Pharmaceuticals',
            'product_type' => 'capsule',
            'base_unit' => 'strip',
            'pieces_per_strip' => 10,
            'sku' => 'MSTR-SECLO-20',
            'barcode' => '8901111111111',
            'mrp' => 70,
            'default_purchase_price' => 60,
        ]);

        $first = $this->actingAs($user)->postJson("/catalog/master/{$master->getKey()}/activate");
        $first->assertOk()->assertJson(['ok' => true]);

        $productId = $first->json('product_id');
        $this->assertNotNull($productId);

        $product = Product::query()->findOrFail($productId);
        $this->assertSame($master->getKey(), (int) $product->master_product_id);
        $this->assertSame('Seclo 20mg', $product->name);
        $this->assertSame('70.0000', (string) $product->sale_price);
        $this->assertTrue($product->units()->where('sell_unit', 'strip')->exists());

        // Activating again returns the same product (no duplicate).
        $second = $this->actingAs($user)->postJson("/catalog/master/{$master->getKey()}/activate");
        $second->assertOk()->assertJson(['ok' => true, 'product_id' => $productId]);

        $this->assertSame(1, Product::query()->where('master_product_id', $master->getKey())->count());
    }

    public function test_activation_links_existing_product_with_same_barcode(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenantId = (int) $user->tenant_id;

        $master = MasterProduct::query()->create([
            'name' => 'Maxpro 20mg',
            'generic_name' => 'Esomeprazole',
            'strength' => '20 mg',
            'manufacturer_name' => 'Renata Limited',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'sku' => 'MSTR-MAXPRO-20',
            'barcode' => '8902222222222',
            'mrp' => 98,
            'default_purchase_price' => 83,
        ]);

        $existing = Product::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Maxpro (manual)',
            'sku' => 'MANUAL-MAXPRO',
            'barcode' => '8902222222222',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'purchase_price' => 80,
            'sale_price' => 98,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson("/catalog/master/{$master->getKey()}/activate");
        $response->assertOk()->assertJson(['ok' => true, 'product_id' => $existing->getKey()]);

        $existing->refresh();
        $this->assertSame($master->getKey(), (int) $existing->master_product_id);
        $this->assertSame(1, Product::query()->where('tenant_id', $tenantId)->where('barcode', '8902222222222')->count());
    }
}
