<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAutoSkuTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_sku_is_auto_generated_when_omitted(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)->post('/products', [
            'name' => 'Auto SKU Product',
            'product_type' => 'syrup',
            'base_unit' => 'piece',
            'units' => [
                ['sell_unit' => 'piece', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('name', 'Auto SKU Product')->firstOrFail();
        $this->assertMatchesRegularExpression('/^PRD-\d{6}$/', $product->sku);
    }

    public function test_product_can_store_optional_detail_fields(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = $user->tenant;
        $plan = $tenant?->activeSubscription?->plan;
        if ($plan) {
            $plan->features = array_merge($plan->features ?? [], ['wholesale_pricing' => true]);
            $plan->save();
        }

        $this->actingAs($user)->post('/products', [
            'name' => 'Detail Product',
            'generic_name' => 'Paracetamol BP',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'wholesale_price' => 45,
            'vat_percent' => 5,
            'short_description' => 'Pain relief tablet',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('name', 'Detail Product')->firstOrFail();
        $this->assertSame('Paracetamol BP', $product->generic_name);
        $this->assertSame('45.0000', (string) $product->wholesale_price);
        $this->assertSame('5.0000', (string) $product->vat_percent);
        $this->assertSame('Pain relief tablet', $product->short_description);
    }
}
