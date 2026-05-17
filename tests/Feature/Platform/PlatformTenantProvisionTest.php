<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
