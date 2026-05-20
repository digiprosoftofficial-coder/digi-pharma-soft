<?php

namespace Tests\Feature\Tenant;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantWholesaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_wholesale_enabled_from_plan_by_default(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = ['pos' => true, 'reports' => true, 'wholesale_pricing' => true];
        $plan->save();

        $tenant->refresh();

        $this->assertTrue(TenantFeatures::wholesalePricingEnabled($tenant));
    }

    public function test_platform_override_can_force_wholesale_off_despite_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = ['pos' => true, 'reports' => true, 'wholesale_pricing' => true];
        $plan->save();

        $settings = $tenant->settings ?? [];
        $settings['features'] = ['wholesale_pricing' => false];
        $tenant->settings = $settings;
        $tenant->save();
        $tenant->refresh();

        $this->assertFalse(TenantFeatures::wholesalePricingEnabled($tenant));
    }

    public function test_platform_override_can_force_wholesale_on_despite_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = ['pos' => true, 'reports' => true, 'wholesale_pricing' => false];
        $plan->save();

        $settings = $tenant->settings ?? [];
        $settings['features'] = ['wholesale_pricing' => true];
        $tenant->settings = $settings;
        $tenant->save();
        $tenant->refresh();

        $this->assertTrue(TenantFeatures::wholesalePricingEnabled($tenant));
    }

    public function test_wholesale_price_ignored_when_feature_disabled(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'wholesale_pricing' => false];
        $plan->save();
        $tenant->refresh();

        $this->actingAs($user)->post('/products', [
            'name' => 'No Wholesale Product',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'wholesale_price' => 99,
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('name', 'No Wholesale Product')->firstOrFail();
        $this->assertNull($product->wholesale_price);
    }

    public function test_wholesale_price_saved_when_feature_enabled_via_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'wholesale_pricing' => true];
        $plan->save();
        $tenant->refresh();

        $this->actingAs($user)->post('/products', [
            'name' => 'Wholesale Enabled Product',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'wholesale_price' => 45,
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 50, 'sale_price' => 70, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('name', 'Wholesale Enabled Product')->firstOrFail();
        $this->assertSame('45.0000', (string) $product->wholesale_price);
    }

    public function test_platform_admin_can_set_wholesale_override_on_tenant(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => $tenant->name,
                'is_active' => true,
                'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d'),
                'subscription_ends_at' => $tenant->subscription_ends_at?->format('Y-m-d'),
                'subscription_plan_id' => $tenant->activeSubscription?->subscription_plan_id,
                'reseller_id' => null,
                'internal_notes' => null,
                'wholesale_pricing_override' => 'on',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertTrue(TenantFeatures::wholesalePricingEnabled($tenant));
        $this->assertTrue($tenant->settings['features']['wholesale_pricing']);
    }

    public function test_platform_admin_can_clear_override_to_inherit_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $settings = $tenant->settings ?? [];
        $settings['features'] = ['wholesale_pricing' => true];
        $tenant->settings = $settings;
        $tenant->save();

        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'wholesale_pricing' => false];
        $plan->save();

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => $tenant->name,
                'is_active' => true,
                'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d'),
                'subscription_ends_at' => $tenant->subscription_ends_at?->format('Y-m-d'),
                'subscription_plan_id' => $tenant->activeSubscription?->subscription_plan_id,
                'reseller_id' => null,
                'internal_notes' => null,
                'wholesale_pricing_override' => 'inherit',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertFalse(TenantFeatures::wholesalePricingEnabled($tenant));
        $this->assertNull(TenantFeatures::override($tenant, TenantFeatures::WHOLESALE_PRICING));
    }
}
