<?php

namespace Tests\Feature\Http;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFacingDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_forbidden_redirects_home_with_dialog_instead_of_403_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/branches')
            ->assertRedirect(route('tenant.dashboard'))
            ->assertSessionHas('app_dialog.message', __('branches.feature_disabled'));
    }

    public function test_missing_page_redirects_guests_to_login_with_dialog_instead_of_404_page(): void
    {
        $this->get('/this-page-is-missing')
            ->assertRedirect(route('login'))
            ->assertSessionHas('app_dialog.type', 'not_found');
    }

    public function test_json_clients_still_receive_http_403(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'is_platform_super_admin' => false,
        ]);

        $this->actingAs($user)
            ->getJson('/catalog/product-search?q=pa')
            ->assertForbidden();
    }

    public function test_expired_subscription_renders_notice_instead_of_403_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);
        $ended = now()->subDay();
        $tenant->update([
            'trial_ends_at' => $ended,
            'subscription_ends_at' => $ended,
            'is_active' => true,
            'suspended_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shared/AppNotice')
                ->where('dialog.message', __('platform.subscription_inactive')));
    }
}
