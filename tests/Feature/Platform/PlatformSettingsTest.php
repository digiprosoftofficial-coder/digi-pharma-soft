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
            ->put(route('platform.settings.update'), [
                'default_trial_days' => 21,
                'support_email' => 'support@pharmacy.test',
                'support_phone' => '+8801700000000',
                'sms_provider' => 'twilio',
                'sms_api_key' => 'secret-key',
                'feature_flags' => [
                    'pos' => true,
                    'reports' => true,
                    'stock_transfers' => false,
                ],
            ])
            ->assertRedirect(route('platform.settings.edit'));

        $settings = PlatformSettings::get();
        $this->assertSame(21, $settings['default_trial_days']);
        $this->assertSame('support@pharmacy.test', $settings['support_email']);
        $this->assertTrue($settings['sms_api_key_set']);
        $this->assertFalse($settings['feature_flags']['stock_transfers']);
    }

    public function test_tenant_owner_cannot_access_platform_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.settings.edit'))
            ->assertForbidden();
    }
}
