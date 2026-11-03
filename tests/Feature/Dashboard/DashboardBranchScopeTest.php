<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_defaults_to_all_branches_and_can_filter_one(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);
        $this->enableMultiBranch($tenant);

        $product = Product::query()->firstOrFail();
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
            'batch_no' => 'EAST-DASH-01',
            'quantity_on_hand' => 50,
            'purchase_unit_cost' => 10,
        ]);

        $allStock = (float) DB::table('product_batches')
            ->where('tenant_id', $tenant->getKey())
            ->sum(DB::raw('quantity_on_hand * purchase_unit_cost'));
        $eastStock = 500.0;

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Dashboard')
                ->where('branchScope.key', 'all')
                ->where('branchScope.all', true)
                ->where('branchScope.can_view_all', true)
                ->where('kpis.stockValue', fn ($value) => (float) $value === $allStock)
                ->has('branchScope.options', 3));

        $this->get('/dashboard?branch_id='.$east->getKey())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('branchScope.key', (string) $east->getKey())
                ->where('branchScope.all', false)
                ->where('kpis.stockValue', fn ($value) => (float) $value === $eastStock)
                ->where('kpis.inStockProductCount', 1)
                ->has('branchPerformance', 1)
                ->where('branchPerformance.0.id', $east->getKey()));

        $this->get('/dashboard?branch_id=999999')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('branchScope.key', 'all')
                ->where('kpis.stockValue', fn ($value) => (float) $value === $allStock));
    }

    public function test_manager_cannot_open_all_branches_dashboard_scope(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $this->enableMultiBranch($tenant);
        $manager = $this->createManager($tenant);
        $east = Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'East',
            'code' => 'EAST',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($manager)
            ->post('/branches/switch', ['branch_id' => $east->getKey()])
            ->assertRedirect();

        $this->get('/dashboard?branch_id=all')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('branchScope.can_view_all', false)
                ->where('branchScope.all', false)
                ->where('branchScope.key', (string) $east->getKey()));
    }

    private function enableMultiBranch(Tenant $tenant, int $maxBranches = 3): void
    {
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = array_merge($plan->features ?? [], ['multi_branch' => true]);
        $plan->limits = array_merge($plan->limits ?? [], ['max_branches' => $maxBranches]);
        $plan->save();
        $tenant->refresh();

        $this->assertTrue(TenantFeatures::multiBranchEnabled($tenant));
    }

    private function createManager(Tenant $tenant): User
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->getKey());

        $manager = User::query()->create([
            'name' => 'Branch Manager',
            'email' => 'manager-dash@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->getKey(),
        ]);
        $manager->forceFill(['email_verified_at' => now()])->save();
        $manager->assignRole('manager');

        return $manager;
    }
}
