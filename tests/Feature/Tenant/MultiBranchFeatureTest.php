<?php

namespace Tests\Feature\Tenant;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\BranchContext;
use App\Support\Tenant\TenantFeatures;
use App\Support\Tenant\TenantLimits;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiBranchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_branch_disabled_by_default(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();

        $this->assertFalse(TenantFeatures::multiBranchEnabled($tenant));
        $this->assertSame(1, TenantLimits::maxBranches($tenant));
    }

    public function test_multi_branch_enabled_from_plan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = ['pos' => true, 'reports' => true, 'multi_branch' => true];
        $plan->limits = ['max_branches' => 3];
        $plan->save();
        $tenant->refresh();

        $this->assertTrue(TenantFeatures::multiBranchEnabled($tenant));
        $this->assertSame(3, TenantLimits::maxBranches($tenant));
    }

    public function test_platform_override_can_force_multi_branch_off(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $plan = $tenant->activeSubscription?->plan;
        $plan->features = ['pos' => true, 'reports' => true, 'multi_branch' => true];
        $plan->save();

        $settings = $tenant->settings ?? [];
        $settings['features'] = ['multi_branch' => false];
        $tenant->settings = $settings;
        $tenant->save();
        $tenant->refresh();

        $this->assertFalse(TenantFeatures::multiBranchEnabled($tenant));
    }

    public function test_owner_can_create_branch_when_feature_enabled(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->enableMultiBranch($tenant, maxBranches: 3);

        $this->actingAs($user)
            ->post('/branches', [
                'name' => 'Uptown',
                'code' => 'UP',
                'is_active' => true,
            ])
            ->assertRedirect(route('tenant.branches.index'));

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->getKey(),
            'name' => 'Uptown',
            'code' => 'UP',
        ]);
    }

    public function test_branch_limit_enforced_on_create(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->enableMultiBranch($tenant, maxBranches: 2);

        Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Second',
            'code' => 'SEC',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->from(route('tenant.branches.create'))
            ->post('/branches', [
                'name' => 'Third',
                'code' => 'THR',
                'is_active' => true,
            ])
            ->assertRedirect(route('tenant.branches.create'))
            ->assertSessionHasErrors('name');

        $this->assertSame(2, Branch::query()->withoutGlobalScopes()->where('tenant_id', $tenant->getKey())->count());
    }

    public function test_branch_switch_sets_session_active_branch(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->enableMultiBranch($tenant, maxBranches: 3);

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'East',
            'code' => 'EAST',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->post('/branches/switch', ['branch_id' => $branch->getKey()])
            ->assertRedirect();

        $this->assertSame($branch->getKey(), session('active_branch_id'));
    }

    public function test_product_batches_are_branch_scoped_in_queries(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $product = Product::query()->firstOrFail();

        $this->enableMultiBranch($tenant, maxBranches: 3);

        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'East',
            'code' => 'EAST',
            'is_active' => true,
            'is_default' => false,
        ]);

        ProductBatch::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'branch_id' => $east->getKey(),
            'product_id' => $product->getKey(),
            'batch_no' => 'EAST-01',
            'quantity_on_hand' => 50,
            'purchase_unit_cost' => 10,
        ]);

        $this->actingAs($user)
            ->post('/branches/switch', ['branch_id' => $main->getKey()])
            ->assertRedirect();

        app(BranchContext::class)->set($main);
        $visibleOnMain = ProductBatch::query()->where('product_id', $product->getKey())->pluck('batch_no')->all();
        $this->assertContains('B001', $visibleOnMain);
        $this->assertNotContains('EAST-01', $visibleOnMain);

        $this->actingAs($user)
            ->post('/branches/switch', ['branch_id' => $east->getKey()])
            ->assertRedirect();

        app(BranchContext::class)->set($east);
        $visibleOnEast = ProductBatch::query()->where('product_id', $product->getKey())->pluck('batch_no')->all();
        $this->assertContains('EAST-01', $visibleOnEast);
        $this->assertNotContains('B001', $visibleOnEast);
    }

    public function test_branches_index_forbidden_when_feature_disabled(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/branches')
            ->assertForbidden();
    }

    private function enableMultiBranch(Tenant $tenant, int $maxBranches = 3): void
    {
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = array_merge($plan->features ?? [], ['multi_branch' => true]);
        $plan->limits = array_merge($plan->limits ?? [], ['max_branches' => $maxBranches]);
        $plan->save();
        $tenant->refresh();
    }
}
