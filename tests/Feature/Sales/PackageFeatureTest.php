<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Sales\Models\Sale;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_sale_is_blocked_when_platform_feature_is_disabled(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $this->enablePlanPackageFeature();
        $this->enableTenantPackageFeature();

        $this->actingAs($user)
            ->get(route('tenant.sales.package'))
            ->assertForbidden();
    }

    public function test_package_sale_is_blocked_when_owner_toggle_is_disabled(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $this->enablePlatformPackageFeature();
        $this->enablePlanPackageFeature();

        $this->actingAs($user)
            ->get(route('tenant.sales.package'))
            ->assertForbidden();
    }

    public function test_package_sale_is_blocked_when_plan_feature_is_disabled(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $this->enablePlatformPackageFeature();
        $this->enableTenantPackageFeature();

        $this->actingAs($user)
            ->get(route('tenant.sales.package'))
            ->assertForbidden();
    }

    public function test_owner_can_create_package_template_when_feature_is_enabled(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $this->enablePackageFeature();

        $this->actingAs($user)
            ->post(route('tenant.sales.packages.store'), [
                'name' => 'Fever pack',
                'description' => 'Common fever medicines',
                'is_active' => true,
                'discount_percent' => 5,
                'fixed_price' => null,
                'items' => [[
                    'product_id' => $product->getKey(),
                    'sell_unit' => 'strip',
                    'quantity' => 2,
                    'unit_price_override' => 12,
                ]],
            ])
            ->assertRedirect(route('tenant.sales.packages.index'));

        $this->assertDatabaseHas('package_templates', ['name' => 'Fever pack', 'is_active' => true]);
        $this->assertDatabaseHas('package_template_items', [
            'product_id' => $product->getKey(),
            'sell_unit' => 'strip',
            'quantity' => 2,
        ]);
    }

    public function test_package_sale_redirects_back_to_package_page(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $this->enablePackageFeature();

        ProductBatch::query()->where('product_id', $product->getKey())->delete();
        $batch = ProductBatch::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'batch_no' => 'PACKAGE-SALE',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity_on_hand' => 10,
            'purchase_unit_cost' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('tenant.sales.package.store'), [
                'lines' => [[
                    'product_batch_id' => $batch->getKey(),
                    'quantity' => 1,
                    'sell_unit' => 'strip',
                    'unit_price' => 10,
                ]],
                'payments' => [['method' => 'cash', 'amount' => 10]],
            ])
            ->assertRedirect(route('tenant.sales.package'))
            ->assertSessionHas('last_sale_id');

        $this->assertSame(1, Sale::query()->count());
    }

    private function enablePackageFeature(): void
    {
        $this->enablePlatformPackageFeature();
        $this->enablePlanPackageFeature();
        $this->enableTenantPackageFeature();
    }

    private function enablePlatformPackageFeature(): void
    {
        $flags = PlatformSettings::defaultFeatureFlags();
        $flags['package_sales'] = true;
        PlatformSettings::update(['feature_flags' => $flags]);
    }

    private function enableTenantPackageFeature(): void
    {
        $tenant = Tenant::query()->firstOrFail();
        $settings = $tenant->settings ?? [];
        $settings['features']['package_sales'] = true;
        $tenant->settings = $settings;
        $tenant->save();
    }

    private function enablePlanPackageFeature(): void
    {
        $tenant = Tenant::query()->firstOrFail();
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $features = $plan->features ?? [];
        $features['package_sales'] = true;
        $plan->features = $features;
        $plan->save();
    }
}
