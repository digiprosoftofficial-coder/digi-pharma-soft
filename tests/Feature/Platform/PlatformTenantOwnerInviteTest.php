<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantOwnerInvitationNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformTenantOwnerInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_invite_owner_by_email(): void
    {
        Notification::fake();

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $this->actingAs($admin)->post('/platform/tenants', [
            'name' => 'Invite Owner Pharmacy',
            'slug' => 'invite-owner-pharmacy',
            'subscription_plan_id' => $plan->getKey(),
            'add_owner_later' => true,
        ])->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'invite-owner-pharmacy')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.tenants.owner.store', $tenant), [
                'owner_name' => 'Invited Owner',
                'owner_email' => 'invited-owner@example.com',
                'owner_invite' => true,
            ])
            ->assertRedirect(route('platform.tenants.show', $tenant));

        $owner = User::query()->where('email', 'invited-owner@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertNull($owner->email_verified_at);

        Notification::assertSentTo($owner, TenantOwnerInvitationNotification::class);
    }

    public function test_super_admin_can_resend_owner_invitation(): void
    {
        Notification::fake();

        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $this->actingAs($admin);
        $this->post('/platform/tenants', [
            'name' => 'Resend Invite Pharmacy',
            'slug' => 'resend-invite-pharmacy',
            'subscription_plan_id' => $plan->getKey(),
            'add_owner_later' => true,
        ]);

        $tenant = Tenant::query()->where('slug', 'resend-invite-pharmacy')->firstOrFail();

        $this->post(route('platform.tenants.owner.store', $tenant), [
            'owner_name' => 'Resend Owner',
            'owner_email' => 'resend-owner@example.com',
            'owner_invite' => true,
        ]);

        $owner = User::query()->where('email', 'resend-owner@example.com')->firstOrFail();
        Notification::assertSentTo($owner, TenantOwnerInvitationNotification::class);

        $this->post(route('platform.tenants.owner.resend-invite', $tenant))
            ->assertRedirect(route('platform.tenants.show', $tenant));

        Notification::assertSentToTimes($owner, TenantOwnerInvitationNotification::class, 2);
    }
}
