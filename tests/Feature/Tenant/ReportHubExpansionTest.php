<?php

namespace Tests\Feature\Tenant;

use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportHubExpansionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_new_report_levels(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        foreach ([
            '/reports/expiry' => 'Reports/ExpiryManagement',
            '/reports/suppliers' => 'Reports/SupplierReports',
            '/reports/customers' => 'Reports/CustomerReports',
            '/reports/finance' => 'Reports/FinancialReports',
            '/reports/user-activity' => 'Reports/UserActivityReports',
        ] as $uri => $component) {
            $this->actingAs($owner)
                ->get($uri)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component($component)
                    ->has('summary')
                );
        }

        $this->enableMultiBranch($tenant);

        $this->actingAs($owner)
            ->get('/reports/branches')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/BranchReports')
                ->has('summary')
            );
    }

    public function test_branch_reports_require_multi_branch_feature(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get('/reports/branches')
            ->assertForbidden();

        $this->actingAs($owner)
            ->get('/reports/branches/export?format=print')
            ->assertForbidden();
    }

    public function test_report_downloads_require_export_permission(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $user = $this->tenantUser($tenant, 'finance-only');
        $user->givePermissionTo('reports.finance');

        $this->actingAs($user)
            ->get('/reports/finance/export?format=pdf')
            ->assertForbidden();
    }

    public function test_branch_limited_user_sees_current_branch_report_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $manager = $this->tenantUser($tenant, 'branch-manager');
        $manager->assignRole('manager');
        $east = Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'East',
            'code' => 'EAST',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->enableMultiBranch($tenant);

        $this->actingAs($manager)
            ->post('/branches/switch', ['branch_id' => $east->getKey()])
            ->assertRedirect();

        $this->actingAs($manager)
            ->get('/reports/branches')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/BranchReports')
                ->where('canViewAllBranches', false)
                ->where('rows.data.0.branch_id', $east->getKey())
                ->has('rows.data', 1)
            );
    }

    private function tenantUser(Tenant $tenant, string $emailPrefix): User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

        $user = User::query()->create([
            'name' => str($emailPrefix)->headline()->toString(),
            'email' => $emailPrefix.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->getKey(),
            'is_platform_super_admin' => false,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function enableMultiBranch(Tenant $tenant): void
    {
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = array_merge($plan->features ?? [], ['multi_branch' => true]);
        $plan->limits = array_merge($plan->limits ?? [], ['max_branches' => 3]);
        $plan->save();
        $tenant->refresh();
    }
}
