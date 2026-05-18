<?php

namespace Tests\Feature\Platform;

use App\Domain\Catalog\Models\Product;
use App\Domain\Platform\Models\CatalogTemplate;
use App\Domain\Platform\Models\CatalogTemplateItem;
use App\Domain\Platform\Models\PlatformAnnouncement;
use App\Domain\Platform\Models\Reseller;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformAnnouncementService;
use App\Domain\Billing\Models\SubscriptionPlan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformP3Test extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_resellers(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('platform.resellers.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('platform.resellers.store'), [
                'name' => 'Medi Partners',
                'slug' => 'medi-partners',
                'contact_email' => 'partner@example.com',
                'is_active' => true,
            ])
            ->assertRedirect(route('platform.resellers.index'));

        $this->assertDatabaseHas('resellers', ['slug' => 'medi-partners']);
    }

    public function test_new_tenant_inherits_platform_locale_defaults(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), array_merge($this->settingsPayload(), [
                'default_locale' => 'bn',
                'default_timezone' => 'Asia/Dhaka',
                'default_country_code' => 'BD',
            ]));

        $this->actingAs($admin)
            ->post(route('platform.tenants.store'), [
                'name' => 'Locale Pharmacy',
                'slug' => 'locale-pharmacy',
                'subscription_plan_id' => $plan->getKey(),
                'add_owner_later' => true,
            ])
            ->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'locale-pharmacy')->firstOrFail();
        $this->assertSame('bn', $tenant->settings['locale'] ?? null);
        $this->assertSame('BD', $tenant->settings['country_code'] ?? null);
    }

    public function test_catalog_template_apply_creates_tenant_products(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $template = CatalogTemplate::query()->create([
            'name' => 'Starter pack',
            'slug' => 'starter-pack',
            'is_published' => true,
        ]);

        CatalogTemplateItem::query()->create([
            'catalog_template_id' => $template->getKey(),
            'name' => 'Paracetamol 500mg',
            'sku' => 'PARA-500',
            'purchase_price' => 10,
            'sale_price' => 15,
            'unit' => 'pcs',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.catalog-templates.apply', $template), [
                'tenant_id' => $tenant->getKey(),
            ])
            ->assertRedirect();

        $this->assertTrue(
            Product::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenant->getKey())
                ->where('sku', 'PARA-500')
                ->exists()
        );
    }

    public function test_active_announcement_visible_to_tenant_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        PlatformAnnouncement::query()->create([
            'title' => 'Scheduled maintenance',
            'body' => 'POS may be slow tonight.',
            'severity' => 'warning',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $banner = PlatformAnnouncementService::activeBanner();
        $this->assertNotNull($banner);
        $this->assertSame('Scheduled maintenance', $banner['title']);

        $this->actingAs($owner)
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('networkAnnouncement.title', 'Scheduled maintenance'));
    }

    public function test_tenant_update_can_assign_reseller(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $reseller = Reseller::query()->create([
            'name' => 'Channel One',
            'slug' => 'channel-one',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => $tenant->name,
                'is_active' => true,
                'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d'),
                'subscription_ends_at' => $tenant->subscription_ends_at?->format('Y-m-d'),
                'subscription_plan_id' => $tenant->activeSubscription?->subscription_plan_id,
                'reseller_id' => $reseller->getKey(),
                'internal_notes' => null,
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame($reseller->getKey(), $tenant->reseller_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(): array
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
