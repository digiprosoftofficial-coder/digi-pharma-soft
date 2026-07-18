<?php

namespace Tests\Feature\Platform;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlatformMasterCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_create_master_medicine(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('platform.master-catalog.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/MasterCatalog/Index'));

        $this->actingAs($admin)
            ->post(route('platform.master-catalog.store'), [
                'name' => 'Napa Extra Test',
                'generic_name' => 'Paracetamol + Caffeine',
                'strength' => '500 mg + 65 mg',
                'manufacturer_name' => 'Beximco Pharmaceuticals',
                'product_type' => 'tablet',
                'drug_class' => 'Analgesic & antipyretic',
                'base_unit' => 'strip',
                'pieces_per_strip' => 10,
                'sku' => 'MSTR-NAPA-EXTRA-TEST',
                'mrp' => 15,
                'default_purchase_price' => 12,
                'is_active' => true,
            ])
            ->assertRedirect(route('platform.master-catalog.index'));

        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-NAPA-EXTRA-TEST',
            'name' => 'Napa Extra Test',
        ]);
    }

    public function test_super_admin_can_import_csv_and_upsert_by_sku(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        MasterProduct::query()->create([
            'name' => 'Old Name',
            'sku' => 'MSTR-UPSERT-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 10,
            'default_purchase_price' => 8,
            'is_active' => true,
        ]);

        $csv = implode("\n", [
            'name,generic_name,strength,manufacturer_name,product_type,drug_class,base_unit,pieces_per_strip,strips_per_box,boxes_per_carton,sku,barcode,mrp,default_purchase_price,is_active',
            'Updated Name,Paracetamol,500 mg,Square,tablet,Analgesic,strip,10,,,MSTR-UPSERT-1,,20,17,1',
            'Brand New Med,Omeprazole,20 mg,Square,capsule,GI,strip,10,,,MSTR-NEW-99,,70,60,1',
        ]);

        $file = UploadedFile::fake()->createWithContent('master.csv', $csv);

        $this->actingAs($admin)
            ->post(route('platform.master-catalog.preview'), ['file' => $file])
            ->assertRedirect();

        $preview = session('master_import_preview');
        $this->assertNotNull($preview);
        $this->assertSame(2, $preview['valid_count']);

        $this->actingAs($admin)
            ->post(route('platform.master-catalog.import.store'), [
                'headers' => $preview['headers'],
                'rows' => collect($preview['rows'])->map(fn ($r) => [
                    'row' => $r['row'],
                    'raw' => $r['raw'],
                ])->all(),
                'update_existing' => true,
            ])
            ->assertRedirect(route('platform.master-catalog.index'));

        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-UPSERT-1',
            'name' => 'Updated Name',
            'mrp' => '20.0000',
        ]);
        $this->assertDatabaseHas('master_products', [
            'sku' => 'MSTR-NEW-99',
            'name' => 'Brand New Med',
        ]);
    }

    public function test_delete_deactivates_when_activated_by_pharmacy(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $master = MasterProduct::query()->create([
            'name' => 'Linked Med',
            'sku' => 'MSTR-LINKED-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'mrp' => 10,
            'default_purchase_price' => 8,
            'is_active' => true,
        ]);

        Product::query()->create([
            'tenant_id' => $owner->tenant_id,
            'master_product_id' => $master->getKey(),
            'name' => 'Linked Med',
            'sku' => 'TENANT-LINKED-1',
            'barcode' => 'BC-LINKED-1',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'unit' => 'strip',
            'purchase_price' => 8,
            'sale_price' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('platform.master-catalog.destroy', $master))
            ->assertRedirect(route('platform.master-catalog.index'));

        $master->refresh();
        $this->assertFalse($master->is_active);
        $this->assertDatabaseHas('master_products', ['id' => $master->getKey()]);
    }

    public function test_pharmacy_owner_cannot_access_platform_master_catalog(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.master-catalog.index'))
            ->assertForbidden();
    }
}
