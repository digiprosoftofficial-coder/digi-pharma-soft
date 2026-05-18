<?php

namespace Tests\Feature\Platform;

use App\Domain\Tenant\Actions\SuspendTenantAction;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PlatformComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_export_tenant_data_zip(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('platform.tenants.export', $tenant));

        $response->assertOk();
        $response->assertDownload();

        $this->assertDatabaseHas('activity_log', [
            'event' => 'tenant.data_exported',
        ]);
    }

    public function test_super_admin_can_purge_suspended_tenant(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        app(SuspendTenantAction::class)->execute($tenant, $admin, 'Compliance test');

        $this->actingAs($admin)
            ->post(route('platform.tenants.purge', $tenant), [
                'confirm_slug' => $tenant->slug,
                'reason' => 'Customer requested erasure',
            ])
            ->assertRedirect(route('platform.tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->getKey()]);
        $this->assertDatabaseMissing('users', ['id' => $owner->getKey()]);
        $this->assertDatabaseHas('activity_log', [
            'event' => 'tenant.data_purged',
        ]);
    }

    public function test_purge_rejected_when_tenant_not_suspended(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.tenants.purge', $tenant), [
                'confirm_slug' => $tenant->slug,
                'reason' => 'Should fail',
            ])
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->getKey()]);
    }

    public function test_retention_command_purges_old_audit_logs(): void
    {
        $this->seed(DatabaseSeeder::class);

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'Old entry',
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        Artisan::call('platform:purge-compliance-retention');

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Old entry',
        ]);
    }

    public function test_super_admin_can_update_compliance_retention_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), [
                'default_trial_days' => 14,
                'support_email' => null,
                'support_phone' => null,
                'sms_provider' => null,
                'feature_flags' => [
                    'pos' => true,
                    'reports' => true,
                    'stock_transfers' => true,
                ],
                'audit_log_retention_days' => 180,
                'compliance_export_retention_days' => 14,
                'billing_grace_days' => 7,
                'auto_suspend_on_payment_failure' => true,
                'default_currency' => 'BDT',
                'default_locale' => 'en',
                'default_timezone' => 'Asia/Dhaka',
                'default_country_code' => 'BD',
            ])
            ->assertRedirect(route('platform.settings.edit'));

        $this->assertDatabaseHas('platform_settings', [
            'audit_log_retention_days' => 180,
            'compliance_export_retention_days' => 14,
        ]);
    }
}
