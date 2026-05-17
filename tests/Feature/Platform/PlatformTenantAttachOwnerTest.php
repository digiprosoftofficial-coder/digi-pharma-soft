<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantAttachOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_attach_owner_to_tenant_without_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $this->actingAs($admin)->post('/platform/tenants', [
            'name' => 'Later Owner Pharmacy',
            'slug' => 'later-owner-attach',
            'subscription_plan_id' => $plan->getKey(),
            'add_owner_later' => true,
        ])->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'later-owner-attach')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.tenants.owner.store', $tenant), [
                'owner_name' => 'Late Owner',
                'owner_email' => 'late-owner@example.com',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('platform.tenants.show', $tenant));

        $owner = User::query()->where('email', 'late-owner@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertSame($tenant->getKey(), $owner->tenant_id);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId((int) $tenant->getKey());
        $this->assertTrue($owner->hasRole('pharmacy owner'));
    }

    public function test_cannot_attach_second_owner(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'demo-pharmacy')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.tenants.owner.store', $tenant), [
                'owner_name' => 'Duplicate Owner',
                'owner_email' => 'duplicate-owner@example.com',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('owner_email');
    }
}
