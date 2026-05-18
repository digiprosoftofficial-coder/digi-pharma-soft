<?php

namespace Tests\Feature\Platform;

use App\Domain\Sales\Models\Sale;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_dashboard_includes_aggregated_analytics(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        Sale::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->getKey(),
            'invoice_no' => 'NET-001',
            'sold_at' => now(),
            'subtotal' => 150,
            'discount' => 0,
            'tax' => 0,
            'total' => 150,
            'paid' => 150,
            'due' => 0,
            'status' => 'posted',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Dashboard')
                ->where('analytics.revenue_all_time', 150)
                ->where('analytics.revenue_this_month', 150)
                ->where('analytics.sales_count_this_month', 1)
                ->where('analytics.active_selling_tenants', 1)
                ->has('analytics.top_tenants_30d', 1)
                ->has('billing.mrr_cents')
                ->has('health.status'));
    }

    public function test_platform_health_page_lists_failed_jobs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'RuntimeException: demo failure for test',
            'failed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('platform.health.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Health/Index')
                ->where('health.failed_jobs', 1)
                ->where('health.status', 'degraded')
                ->has('health.recent_failed_jobs', 1));

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.health.index'))
            ->assertForbidden();
    }
}
