<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformPlanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_plan(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post('/platform/plans', [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_cents' => 9900,
                'trial_days' => 7,
                'features' => ['pos' => true, 'reports' => false],
            ])
            ->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseHas('subscription_plans', ['slug' => 'starter']);
    }

    public function test_tenant_owner_cannot_access_platform_plans(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get('/platform/plans')
            ->assertForbidden();
    }
}
