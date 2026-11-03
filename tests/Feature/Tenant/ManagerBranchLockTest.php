<?php

namespace Tests\Feature\Tenant;

use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ManagerBranchLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_assign_branch_to_manager(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);
        $this->enableMultiBranch($tenant);

        $east = Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'East',
            'code' => 'EAST',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->actingAs($owner)
            ->post('/team/users', [
                'name' => 'East Manager',
                'email' => 'east-manager@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'manager',
                'assigned_branch_id' => $east->getKey(),
            ])
            ->assertRedirect(route('tenant.team.users.index'));

        $manager = User::query()->where('email', 'east-manager@example.com')->firstOrFail();
        $this->assertSame((int) $east->getKey(), (int) $manager->assigned_branch_id);
        $this->assertTrue($manager->isLockedToAssignedBranch());
    }

    public function test_manager_without_assigned_branch_rejected_when_multi_branch(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($owner->tenant_id);
        $this->enableMultiBranch($tenant);

        $this->actingAs($owner)
            ->from(route('tenant.team.users.create'))
            ->post('/team/users', [
                'name' => 'No Branch Manager',
                'email' => 'no-branch-manager@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'manager',
                'assigned_branch_id' => null,
            ])
            ->assertRedirect(route('tenant.team.users.create'))
            ->assertSessionHasErrors('assigned_branch_id');
    }

    public function test_locked_manager_cannot_switch_branch_and_dashboard_stays_assigned(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $this->enableMultiBranch($tenant);

        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'East',
            'code' => 'EAST',
            'is_active' => true,
            'is_default' => false,
        ]);

        $manager = $this->createManager($tenant, $east->getKey());

        $this->actingAs($manager)
            ->post('/branches/switch', ['branch_id' => $main->getKey()])
            ->assertAppDialogRedirect(__('branches.switch_locked'));

        $this->assertSame((int) $east->getKey(), (int) session('active_branch_id'));

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

    private function createManager(Tenant $tenant, int $assignedBranchId): User
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->getKey());

        $manager = User::query()->create([
            'name' => 'Branch Manager',
            'email' => 'locked-manager@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->getKey(),
            'assigned_branch_id' => $assignedBranchId,
        ]);
        $manager->forceFill(['email_verified_at' => now()])->save();
        $manager->assignRole('manager');

        return $manager;
    }
}
