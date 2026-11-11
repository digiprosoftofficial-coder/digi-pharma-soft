<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\PlatformSetting;
use App\Models\User;
use App\Support\Platform\MarketingSiteSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketingSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shares_default_softopharma_branding(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Home')
                ->where('branding.site_name', 'SoftoPharma')
                ->where('branding.logo_url', '/brand/softopharma-logo.png')
                ->where('branding.icon_url', '/brand/softopharma-icon.png')
                ->where('branding.seo_title', fn ($title) => is_string($title) && str_contains($title, 'Pharmacy Management')));
    }

    public function test_super_admin_can_view_and_update_marketing_site_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('platform.marketing-settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/MarketingSettings/Edit')
                ->has('marketing'));

        $logo = UploadedFile::fake()->image('logo.png', 400, 200);
        $icon = UploadedFile::fake()->image('icon.png', 256, 256);

        $this->actingAs($admin)
            ->post(route('platform.marketing-settings.update'), [
                'site_name' => 'SoftoPharma BD',
                'tagline' => 'Pharmacy OS for Bangladesh',
                'product_of' => 'A Product of DigiProSoft',
                'seo_title' => 'SoftoPharma BD – Pharmacy Management Software',
                'seo_description' => 'Pharmacy POS, inventory, expiry and accounts for Bangladesh pharmacies.',
                'seo_keywords' => 'pharmacy management software, pharmacy POS Bangladesh',
                'logo' => $logo,
                'icon' => $icon,
            ])
            ->assertRedirect(route('platform.marketing-settings.edit'));

        $settings = MarketingSiteSettings::get();
        $this->assertSame('SoftoPharma BD', $settings['site_name']);
        $this->assertSame('Pharmacy OS for Bangladesh', $settings['tagline']);
        $this->assertStringContainsString('Pharmacy Management Software', $settings['seo_title']);
        $this->assertNotNull($settings['logo_path']);
        $this->assertNotNull($settings['icon_path']);
        Storage::disk('public')->assertExists($settings['logo_path']);
        Storage::disk('public')->assertExists($settings['icon_path']);

        $row = PlatformSetting::query()->firstOrFail();
        $this->assertSame('SoftoPharma BD', $row->marketing_settings['site_name'] ?? null);
    }

    public function test_tenant_owner_cannot_access_marketing_site_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.marketing-settings.edit'))
            ->assertAppDialogRedirect();
    }
}
