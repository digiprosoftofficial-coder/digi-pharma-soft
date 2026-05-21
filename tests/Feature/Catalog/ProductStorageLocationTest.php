<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Catalog\Services\ProductService;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\EffectiveStorageLocation;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStorageLocationTest extends TestCase
{
    use RefreshDatabase;

    private function asTenantOwner(): User
    {
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        app(TenantContext::class)->set(Tenant::query()->findOrFail($owner->tenant_id));
        $this->actingAs($owner);

        return $owner;
    }

    public function test_product_default_and_batch_override_resolve_effective_location(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->asTenantOwner();

        $default = StorageLocation::query()->create([
            'name' => 'Rack A',
            'code' => 'A',
            'is_active' => true,
        ]);

        $override = StorageLocation::query()->create([
            'name' => 'Rack B',
            'code' => 'B',
            'is_active' => true,
        ]);

        $product = Product::query()->firstOrFail();
        $product->update(['storage_location_id' => $default->getKey()]);

        $batchDefault = ProductBatch::query()->create([
            'product_id' => $product->getKey(),
            'batch_no' => 'LOC-DEFAULT',
            'quantity_on_hand' => 5,
            'purchase_unit_cost' => 1,
        ]);

        $batchOverride = ProductBatch::query()->create([
            'product_id' => $product->getKey(),
            'batch_no' => 'LOC-OVERRIDE',
            'quantity_on_hand' => 3,
            'purchase_unit_cost' => 1,
            'storage_location_id' => $override->getKey(),
        ]);

        $batchDefault->load('product.storageLocation', 'storageLocation');
        $batchOverride->load('product.storageLocation', 'storageLocation');

        $this->assertSame('A', EffectiveStorageLocation::forBatch($batchDefault)['code']);
        $this->assertSame('B', EffectiveStorageLocation::forBatch($batchOverride)['code']);
    }

    public function test_opening_batch_inherits_product_default_location(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->asTenantOwner();

        $location = StorageLocation::query()->create([
            'name' => 'Shelf 1',
            'is_active' => true,
        ]);

        $product = app(ProductService::class)->createProduct([
            'name' => 'Located Medicine',
            'sku' => 'LOC-TEST-001',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'storage_location_id' => $location->getKey(),
            'units' => [
                [
                    'sell_unit' => 'strip',
                    'conversion_factor' => 1,
                    'purchase_price' => 10,
                    'sale_price' => 12,
                    'is_default' => true,
                ],
            ],
            'opening_quantity' => 20,
        ], null);

        $batch = ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->where('batch_no', 'OPEN-LOC-TEST-001')
            ->first();

        $this->assertNotNull($batch);
        $this->assertSame($location->getKey(), $batch->storage_location_id);
    }

    public function test_owner_can_sync_batch_locations_on_update(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = $this->asTenantOwner();

        $location = StorageLocation::query()->create([
            'name' => 'Cold storage',
            'is_active' => true,
        ]);

        $product = Product::query()->firstOrFail();
        $batch = ProductBatch::query()->where('product_id', $product->getKey())->firstOrFail();

        $this->actingAs($owner)
            ->put(route('tenant.products.update', $product), [
                'name' => $product->name,
                'storage_location_id' => null,
                'batch_locations' => [
                    ['id' => $batch->getKey(), 'storage_location_id' => $location->getKey()],
                ],
            ])
            ->assertRedirect();

        $this->assertSame($location->getKey(), $batch->fresh()->storage_location_id);
    }
}
