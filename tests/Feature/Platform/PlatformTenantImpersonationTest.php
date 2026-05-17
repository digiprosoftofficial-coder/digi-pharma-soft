<?php

namespace Tests\Feature\Platform;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_impersonate_tenant_with_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'demo-pharmacy')->firstOrFail();

        $this->actingAs($admin);
        $this->post(route('platform.tenants.impersonate', $tenant))
            ->assertRedirect(route('tenant.dashboard'))
            ->assertSessionHas('tenant_impersonation.tenant_id', $tenant->getKey());
        $this->get('/dashboard')->assertOk();
    }

    public function test_super_admin_can_stop_impersonation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'demo-pharmacy')->firstOrFail();

        $this->actingAs($admin);
        $this->post(route('platform.tenants.impersonate', $tenant));

        $this->post(route('platform.impersonation.destroy'))
            ->assertRedirect(route('platform.tenants.show', $tenant));

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('platform.dashboard'));
    }
}
