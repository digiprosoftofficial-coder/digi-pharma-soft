<?php

namespace Tests\Feature\Platform;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\MasterProductSuggestion;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformMasterSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_tenant_product_without_master_creates_pending_suggestion(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)->post('/products', [
            'name' => 'Brand New Local Syrup',
            'sku' => 'LOCAL-SYRUP-1',
            'barcode' => '8909999111111',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
            'generic_name' => 'Local Mix',
            'units' => [
                ['sell_unit' => 'piece', 'conversion_factor' => 1, 'purchase_price' => 40, 'sale_price' => 60, 'is_default' => true],
            ],
            'is_active' => true,
        ])->assertRedirect();

        $product = Product::query()->where('sku', 'LOCAL-SYRUP-1')->firstOrFail();
        $this->assertNull($product->master_product_id);

        $this->assertDatabaseHas('master_product_suggestions', [
            'product_id' => $product->getKey(),
            'tenant_id' => $owner->tenant_id,
            'name' => 'Brand New Local Syrup',
            'status' => MasterProductSuggestion::STATUS_PENDING,
        ]);
    }

    public function test_super_admin_can_approve_suggestion_into_master_catalog(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $product = Product::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Crowd Med 10mg',
            'sku' => 'CROWD-10',
            'barcode' => '8908888222222',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'purchase_price' => 20,
            'sale_price' => 30,
            'is_active' => true,
        ]);

        $suggestion = MasterProductSuggestion::query()->create([
            'tenant_id' => $owner->tenant_id,
            'product_id' => $product->getKey(),
            'suggested_by_user_id' => $owner->getKey(),
            'name' => 'Crowd Med 10mg',
            'generic_name' => 'Crowdamol',
            'strength' => '10 mg',
            'manufacturer_name' => 'Crowd Pharma',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'sku' => 'CROWD-10',
            'barcode' => '8908888222222',
            'mrp' => 30,
            'default_purchase_price' => 20,
            'status' => MasterProductSuggestion::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.master-catalog.suggestions.approve', $suggestion), [
                'review_note' => 'Looks good',
            ])
            ->assertRedirect();

        $suggestion->refresh();
        $product->refresh();

        $this->assertSame(MasterProductSuggestion::STATUS_APPROVED, $suggestion->status);
        $this->assertNotNull($suggestion->master_product_id);
        $this->assertSame($suggestion->master_product_id, $product->master_product_id);
        $this->assertDatabaseHas('master_products', [
            'id' => $suggestion->master_product_id,
            'name' => 'Crowd Med 10mg',
            'barcode' => '8908888222222',
        ]);
    }

    public function test_super_admin_can_merge_suggestion_with_existing_master(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $master = MasterProduct::query()->create([
            'name' => 'Known Master Tab',
            'sku' => 'MSTR-KNOWN-1',
            'barcode' => '8907777333333',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 25,
            'default_purchase_price' => 18,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Known Master Tab (typo)',
            'sku' => 'TENANT-KNOWN-1',
            'barcode' => '8907777333333',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'purchase_price' => 18,
            'sale_price' => 25,
            'is_active' => true,
        ]);

        $suggestion = MasterProductSuggestion::query()->create([
            'tenant_id' => $owner->tenant_id,
            'product_id' => $product->getKey(),
            'suggested_by_user_id' => $owner->getKey(),
            'name' => 'Known Master Tab (typo)',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'sku' => 'TENANT-KNOWN-1',
            'barcode' => '8907777333333',
            'mrp' => 25,
            'default_purchase_price' => 18,
            'status' => MasterProductSuggestion::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.master-catalog.suggestions.merge', $suggestion), [
                'master_product_id' => $master->getKey(),
            ])
            ->assertRedirect();

        $suggestion->refresh();
        $product->refresh();

        $this->assertSame(MasterProductSuggestion::STATUS_MERGED, $suggestion->status);
        $this->assertSame($master->getKey(), (int) $product->master_product_id);
        $this->assertSame(1, MasterProduct::query()->where('barcode', '8907777333333')->count());
    }

    public function test_activating_from_master_does_not_create_suggestion(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $master = MasterProduct::query()->create([
            'name' => 'Activate Only Med',
            'sku' => 'MSTR-ACT-ONLY',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 12,
            'default_purchase_price' => 10,
            'is_active' => true,
        ]);

        $before = MasterProductSuggestion::query()->count();

        $this->actingAs($owner)
            ->postJson(route('tenant.catalog.master.activate', $master))
            ->assertOk();

        $this->assertSame($before, MasterProductSuggestion::query()->count());
    }
}
