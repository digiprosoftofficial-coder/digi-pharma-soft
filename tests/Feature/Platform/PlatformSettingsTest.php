<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_update_platform_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('platform.settings.edit'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), $this->validSettingsPayload())
            ->assertRedirect(route('platform.settings.edit'));

        $settings = PlatformSettings::get();
        $this->assertSame(21, $settings['default_trial_days']);
        $this->assertSame('support@pharmacy.test', $settings['support_email']);
        $this->assertTrue($settings['sms_api_key_set']);
        $this->assertFalse($settings['feature_flags']['stock_transfers']);
        $this->assertTrue($settings['feature_flags']['package_sales']);
        $this->assertSame('BDT', $settings['default_currency']);
    }

    public function test_super_admin_can_change_default_currency_via_dropdown(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $payload = $this->validSettingsPayload();
        $payload['default_currency'] = 'USD';

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), $payload)
            ->assertRedirect(route('platform.settings.edit'));

        $this->assertSame('USD', PlatformSettings::defaultCurrency());
    }

    public function test_platform_settings_rejects_unsupported_currency(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $payload = $this->validSettingsPayload();
        $payload['default_currency'] = 'XYZ';

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), $payload)
            ->assertSessionHasErrors('default_currency');
    }

    public function test_tenant_owner_cannot_access_platform_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.settings.edit'))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function validSettingsPayload(): array
    {
        return [
            'default_trial_days' => 21,
            'support_email' => 'support@pharmacy.test',
            'support_phone' => '+8801700000000',
            'sms_provider' => 'twilio',
            'sms_api_key' => 'secret-key',
            'feature_flags' => [
                'pos' => true,
                'reports' => true,
                'stock_transfers' => false,
                'package_sales' => true,
            ],
            'audit_log_retention_days' => 365,
            'compliance_export_retention_days' => 7,
            'billing_grace_days' => 7,
            'auto_suspend_on_payment_failure' => true,
            'default_currency' => 'BDT',
            'default_locale' => 'en',
            'default_timezone' => 'Asia/Dhaka',
            'default_country_code' => 'BD',
        ];
    }
}
