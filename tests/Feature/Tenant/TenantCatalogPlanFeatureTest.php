<?php

namespace Tests\Feature\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCatalogPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_import_and_advanced_catalog_default_on_when_plan_omits_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();

        $this->assertTrue(TenantFeatures::bulkImportEnabled($tenant));
        $this->assertTrue(TenantFeatures::advancedCatalogEnabled($tenant));
    }

    public function test_import_pages_blocked_when_bulk_import_disabled_by_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'bulk_import' => false];
        $plan->save();
        $tenant->refresh();

        $this->actingAs($user)->get('/catalog/import')->assertForbidden();
        $this->actingAs($user)->get('/catalog/import/sample')->assertForbidden();
    }

    public function test_advanced_fields_ignored_on_create_when_disabled_by_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'advanced_catalog' => false];
        $plan->save();
        $tenant->refresh();

        $this->actingAs($user)->post('/products', [
            'name' => 'Basic Plan Product',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'vat_percent' => 5,
            'short_description' => 'Should be ignored',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $product = Product::query()->where('name', 'Basic Plan Product')->firstOrFail();
        $this->assertNull($product->generic_name);
        $this->assertNull($product->strength);
        $this->assertNull($product->vat_percent);
        $this->assertNull($product->short_description);
    }

    public function test_advanced_fields_saved_when_enabled_by_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'advanced_catalog' => true];
        $plan->save();
        $tenant->refresh();

        $this->actingAs($user)->post('/products', [
            'name' => 'Advanced Plan Product',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'units' => [
                ['sell_unit' => 'strip', 'conversion_factor' => 1, 'purchase_price' => 20, 'sale_price' => 35, 'is_default' => true],
            ],
            'min_stock' => 0,
            'is_active' => true,
        ])->assertRedirect(route('tenant.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Advanced Plan Product',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
        ]);
    }

    public function test_super_admin_can_save_new_feature_flags_on_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post('/platform/plans', [
                'name' => 'Basic',
                'slug' => 'basic',
                'price_cents' => 1900,
                'trial_days' => 7,
                'features' => ['pos' => true, 'reports' => true, 'bulk_import' => false, 'advanced_catalog' => false],
            ])
            ->assertRedirect(route('platform.plans.index'));

        $plan = \App\Domain\Billing\Models\SubscriptionPlan::query()->where('slug', 'basic')->firstOrFail();
        $this->assertFalse($plan->features['bulk_import']);
        $this->assertFalse($plan->features['advanced_catalog']);
    }
}
