<?php

namespace Tests\Feature\Platform;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PlatformTenantSuspendTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspend_blocks_tenant_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->whereKey($owner->tenant_id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.tenants.suspend', $tenant), ['reason' => 'Non-payment'])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertNotNull($tenant->suspended_at);

        app(TenantContext::class)->set($tenant);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertForbidden();

        $activity = Activity::query()
            ->where('event', 'tenant.suspended')
            ->where('subject_id', $tenant->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Non-payment', $activity->properties->get('reason'));
    }
}
