<?php

namespace Tests\Feature;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthBrandingAndPlatformLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_applies_tenant_theme_from_query_slug(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->where('is_active', true)->firstOrFail();
        $tenant->loadMissing('activeSubscription.plan');
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $features = $plan->features ?? [];
        $features['theme_templates'] = ['classic_blue', 'emerald', 'teal', 'indigo'];
        $plan->update(['features' => $features]);

        $tenant->settings = array_merge($tenant->settings ?? [], [
            'theme' => [
                'template' => 'emerald',
                'primary' => null,
            ],
        ]);
        $tenant->save();

        $this->get('/login?tenant='.$tenant->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Login')
                ->where('tenant.slug', $tenant->slug)
                ->where('theme.template', 'emerald')
                ->where('theme.primary', '#059669'));
    }

    public function test_platform_login_rejects_tenant_owner(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->from('/platform/login')
            ->post('/platform/login', [
                'email' => $owner->email,
                'password' => 'password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_platform_login_accepts_super_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->post('/platform/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/platform/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_guest_can_switch_locale_on_login(): void
    {
        $this->post('/locale', ['locale' => 'bn'])->assertRedirect();

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'bn'));
    }
}
