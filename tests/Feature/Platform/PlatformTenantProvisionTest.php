<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformTenantProvisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_provision_tenant_with_owner(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $response = $this->actingAs($admin)->post('/platform/tenants', [
            'name' => 'New Pharmacy',
            'slug' => 'new-pharmacy',
            'subscription_plan_id' => $plan->getKey(),
            'add_owner_later' => false,
            'owner_name' => 'Owner One',
            'owner_email' => 'owner-new@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $tenant = Tenant::query()->where('slug', 'new-pharmacy')->first();
        $this->assertNotNull($tenant);
        $response->assertRedirect(route('platform.tenants.show', $tenant));

        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertTrue(
            $tenant->subscription_ends_at->isAfter($tenant->trial_ends_at),
            'Default subscription end should be about one year ahead of trial end.',
        );

        $this->assertDatabaseHas('users', [
            'email' => 'owner-new@example.com',
            'tenant_id' => $tenant->getKey(),
        ]);
    }

    public function test_super_admin_can_provision_without_owner(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $this->actingAs($admin)->post('/platform/tenants', [
            'name' => 'Later Owner Pharmacy',
            'slug' => 'later-owner',
            'subscription_plan_id' => $plan->getKey(),
            'add_owner_later' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('tenants', ['slug' => 'later-owner']);
        $this->assertSame(0, User::query()->where('tenant_id', Tenant::query()->where('slug', 'later-owner')->value('id'))->count());
    }

    public function test_sales_can_provision_and_view_own_pharmacy_but_not_list_or_others(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $this->actingAs($admin)->post('/platform/admins', [
            'name' => 'Sales Person',
            'email' => 'sales-provision@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ])->assertRedirect();

        $sales = User::query()->where('email', 'sales-provision@example.com')->firstOrFail();
        $other = Tenant::query()->where('slug', 'demo-pharmacy')->first()
            ?? Tenant::query()->orderBy('id')->firstOrFail();

        $this->actingAs($sales)->get(route('platform.tenants.show', $other))->assertAppDialogRedirect();

        $this->actingAs($sales)
            ->get('/platform/tenants')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Tenants/Index')
                ->where('canCreate', true)
                ->where('tenants.data', []));

        $response = $this->actingAs($sales)->post('/platform/tenants', [
            'name' => 'Sales Pharmacy',
            'slug' => 'sales-pharmacy',
            'subscription_plan_id' => $plan->getKey(),
            'add_owner_later' => true,
        ]);

        $tenant = Tenant::query()->where('slug', 'sales-pharmacy')->first();
        $this->assertNotNull($tenant);
        $response->assertRedirect(route('platform.tenants.show', $tenant));

        $this->actingAs($sales)
            ->get('/platform/tenants')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Tenants/Index')
                ->has('tenants.data', 1)
                ->where('tenants.data.0.slug', 'sales-pharmacy'));

        $this->actingAs($sales)
            ->get(route('platform.tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Tenants/Show')
                ->where('canManage', false)
                ->where('canImpersonate', false)
                ->where('canAddOwner', true));

        $this->actingAs($sales)
            ->get(route('platform.tenants.edit', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Tenants/Edit')
                ->where('canManage', false)
                ->where('canManageOwner', true)
                ->where('canAddOwner', true));
    }

    public function test_sales_role_includes_tenants_create_permission(): void
    {
        $this->seed(DatabaseSeeder::class);

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);
        try {
            $sales = User::query()->create([
                'name' => 'Sales Check',
                'email' => 'sales-perm@example.com',
                'password' => 'password',
                'tenant_id' => null,
            ]);
            $sales->forceFill(['email_verified_at' => now()])->save();
            $sales->assignRole('sales');
            $this->assertTrue($sales->can('platform.tenants.create'));
            $this->assertTrue($sales->can('platform.payments.manage'));
            $this->assertFalse($sales->can('platform.tenants'));
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
