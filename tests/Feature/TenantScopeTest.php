<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Support\Tenant\TenantContext;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    public function test_product_query_respects_tenant_context(): void
    {
        $t1 = Tenant::query()->create([
            'name' => 'A', 'slug' => 'tenant-a', 'is_active' => true,
        ]);
        $t2 = Tenant::query()->create([
            'name' => 'B', 'slug' => 'tenant-b', 'is_active' => true,
        ]);

        Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $t1->getKey(),
            'name' => 'Med A', 'sku' => 'A-1', 'barcode' => null,
            'unit' => 'pcs', 'purchase_price' => 1, 'sale_price' => 2, 'min_stock' => 0, 'is_active' => true,
        ]);
        Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $t2->getKey(),
            'name' => 'Med B', 'sku' => 'B-1', 'barcode' => null,
            'unit' => 'pcs', 'purchase_price' => 1, 'sale_price' => 2, 'min_stock' => 0, 'is_active' => true,
        ]);

        $ctx = app(TenantContext::class);
        $ctx->set($t1);
        $this->assertCount(1, Product::query()->get());
        $this->assertSame('Med A', Product::query()->first()->name);

        $ctx->set($t2);
        $this->assertCount(1, Product::query()->get());
        $this->assertSame('Med B', Product::query()->first()->name);
    }
}
