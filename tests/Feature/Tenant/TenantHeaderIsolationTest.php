<?php

namespace Tests\Feature\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class TenantHeaderIsolationTest extends TestCase
{
    public function test_x_tenant_slug_cannot_switch_a_user_into_another_pharmacy(): void
    {
        $this->seed();

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $other = Tenant::query()->create([
            'name' => 'Rival Pharmacy',
            'slug' => 'rival-pharmacy-iso',
            'is_active' => true,
        ]);

        Product::query()->withoutGlobalScopes()->create([
            'tenant_id' => $other->getKey(),
            'name' => 'Secret Rival Med',
            'sku' => 'RIVAL-ISO-1',
            'barcode' => null,
            'unit' => 'pcs',
            'purchase_price' => 1,
            'sale_price' => 2,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->withHeaders(['X-Tenant-Slug' => $other->slug])
            ->getJson('/catalog/product-search?q=Rival');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertFalse($names->contains('Secret Rival Med'));
    }
}
